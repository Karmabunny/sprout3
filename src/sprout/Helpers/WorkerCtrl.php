<?php
/*
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

namespace Sprout\Helpers;

use InvalidArgumentException;
use karmabunny\pdb\Exceptions\QueryException;
use karmabunny\pdb\Pdb as PdbInstance;
use Kohana;
use Sprout\Exceptions\WorkerJobException;
use Symfony\Component\Process\Process;


/**
* Functions to update and report on worker status
**/
class WorkerCtrl
{


    /**
    * Starts a new worker
    * Workers are run in their own process (using the PHP CLI)
    *
    * The first argument is the class name
    * Additional arguments can be provided directly in the function call
    *
    * Return value is an array with the following keys
    *   job_id    The ID of the new job
    *   log_url   URL to view ongoing status and log
    *
    * @throws InvalidArgumentException The class is not valid
    * @throws QueryException The insert of the job details could not be completed
    * @throws WorkerJobException If the job failed to start
    * @param string $class_name
    * @param mixed $args Additional arguments are passed to the `run` call
    * @return array Job details
    **/
    public static function start($class_name, ...$args)
    {
        $inst = Sprout::instance($class_name);

        if (!($inst instanceof WorkerBase)) {
            throw new InvalidArgumentException('Provided class is not a subclass of "Worker".');
        }

        $pdb = self::getPdb();

        // Do some self cleanup
        $deleted_date = date('Y-m-d H:i:is', strtotime('-6 months'));
        $pdb->delete('worker_jobs', [['date_modified', '<=', $deleted_date]]);

        $metric_names = $inst->getMetricNames();
        $job_code = Security::randStr(8);

        // Create job record
        $update_fields = array();
        $update_fields['name'] = $inst->getName();
        $update_fields['code'] = $job_code;
        $update_fields['class_name'] = $class_name;
        $update_fields['args'] = json_encode($args);
        $update_fields['log'] = '';
        $update_fields['status'] = 'Prepared';
        $update_fields['metric1name'] = $metric_names[1];
        $update_fields['metric2name'] = $metric_names[2];
        $update_fields['metric3name'] = $metric_names[3];
        $update_fields['date_added'] = Pdb::now();
        $update_fields['date_modified'] = Pdb::now();
        $job_id = $pdb->insert('worker_jobs', $update_fields);

        // If this is called from within a cronjob, let the cron know
        if (isset($_ENV['CRON'])) {
            Cron::message("Starting worker job # {$job_id}, class '{$class_name}'.");
        }

        // Look for a PHP binary
        $php = self::findPhp();
        list($php, $version) = $php;
        if (! $php) {
            $pdb->update('worker_jobs', ['php_bin' => 'NOT FOUND'], ['id' => $job_id]);
            throw new WorkerJobException('Unable to find working PHP binary, which is needed for executing background tasks');
        }

        // Confirm it's a CLI binary; CGI binaries are no good
        if (strpos($version, 'cli') === false) {
            $pdb->update('worker_jobs', ['php_bin' => 'Unuseable (CGI): ' . $php], ['id' => $job_id]);
            throw new WorkerJobException('Found a PHP binary, but it\'s a CGI binary; CLI binary required for executing background tasks');
        }

        $pdb->update('worker_jobs', ['php_bin' => $php], ['id' => $job_id]);

        $job = $update_fields;
        $job['id'] = $job_id;
        $job['php_bin'] = $php;

        self::execute($job);

        return [
            'job_id' => $job_id,
            'log_url' => 'admin/edit/worker_job/' . $job_id
        ];
    }


    /**
     * Execute a worker job.
     *
     * @param array $job db row
     * @return Process
     */
    protected static function execute(array $job): Process
    {
        $args = [
            $job['php_bin'],
            '-d',
            'safe_mode=0',
            WEBROOT . KOHANA,
            "worker_job/run/{$job['id']}/{$job['code']}",
        ];

        $env = [
            'PHP_S_WORKER' => 1,
            'PHP_S_HTTP_HOST' => $_SERVER['HTTP_HOST'],
            'PHP_S_PROTOCOL' => Request::protocol(),
            'PHP_S_WEBDIR' => Kohana::config('core.site_domain'),
        ];

        $process = new Process($args, getcwd(), $env, timeout: null);
        $process->start();

        if (!$process->isRunning()) {
            $ex = new WorkerJobException('Failed to start process');
            $ex->cmd = $process->getCommandLine();
            throw $ex;
        }

        $pdb = self::getPdb();

        // Do several status checks
        $num_checks = 20;
        $status = null;
        for ($i = 0; $i < $num_checks; $i++) {
            usleep(1000 * 50);

            $q = "SELECT status FROM ~worker_jobs WHERE id = ?";
            $status = $pdb->query($q, [$job['id']], 'val');

            if ($status != 'Prepared') {
                break;
            }
        }

        // If it's still not running after all the checks, complain
        if ($status == 'Prepared') {
            $cmd = $process->getCommandLine();
            $output = $process->getOutput();

            $err = "Process isn't running (failed {$num_checks}x status checks)";

            if (PHP_SAPI === 'cli' OR !IN_PRODUCTION) {
                $err .= "\ncmd: " . $cmd;
                $err .= "\noutput: " . $output;
            }

            $ex = new WorkerJobException($err);
            $ex->cmd = $cmd;
            $ex->output = $output;
            throw $ex;
        }

        return $process;
    }


    /**
     * Get a Pdb instance for use with worker jobs.
     *
     * This is a separate instance to skirt past transactions.
     *
     * @return PdbInstance
     */
    public static function getPdb(): PdbInstance
    {
        static $pdb;
        $pdb ??= PdbInstance::create(Pdb::getConfig('default'));
        return $pdb;
    }


    /**
     * Looks in a few places for a PHP CLI binary
     *
     * @return array [path, version]
     */
    private static function findPhp()
    {
        // TODO Other frameworks like to use an environment variable to help
        // find the PHP binary.
        $paths = array(
            '/usr/bin/php-cli',
            '/usr/bin/php',
            '/usr/local/bin/php-cli',
            '/usr/local/bin/php',
            'php-cli',
            'php',
        );

        if (defined('WORKER_PHP_BIN')) {
            array_unshift($paths, constant('WORKER_PHP_BIN'));
        }

        // Try various paths, both absolute and relying on $PATH
        foreach ($paths as $p) {
            $version = @shell_exec($p . ' --version 2>/dev/null');

            // Doesn't exist.
            if (!$version) continue;

            // Must be a CLI binary.
            if (strpos($version, 'cli') === false) continue;

            // Good.
            return array($p, $version);
        }

        return [null, null];
    }


    /**
     * Return the status and metric values for a given worker job.
     *
     * Statuses are:
     *   'Prepared', 'Running', 'Success', 'Failed'.
     *
     * @param int $job_id
     * @return array ['status', 'metric1val', 'metric2val', 'metric3val']
     */
    public static function getStatus($job_id): array
    {
        $pdb = self::getPdb();
        $q = "SELECT status, metric1val, metric2val, metric3val FROM ~worker_jobs WHERE id = ?";
        return $pdb->query($q, [$job_id], 'row');
    }

}


