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
use karmabunny\kb\Shell;
use karmabunny\pdb\Exceptions\QueryException;
use Kohana;

use Sprout\Exceptions\WorkerJobException;


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

        // Do some self cleanup
        $q = "DELETE FROM ~worker_jobs WHERE DATE_ADD(date_modified, INTERVAL 6 MONTH) < NOW()";
        Pdb::query($q, [], 'null');

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
        $job_id = Pdb::insert('worker_jobs', $update_fields);

        // If this is called from within a cronjob, let the cron know
        if (isset($_ENV['CRON'])) {
            Cron::message("Starting worker job # {$job_id}, class '{$class_name}'.");
        }

        // Look for a PHP binary
        $php = self::findPhp();
        list($php, $version) = $php;
        if (! $php) {
            Pdb::update('worker_jobs', ['php_bin' => 'NOT FOUND'], ['id' => $job_id]);
            throw new WorkerJobException('Unable to find working PHP binary, which is needed for executing background tasks');
        }

        // Confirm it's a CLI binary; CGI binaries are no good
        if (strpos($version, 'cli') === false) {
            Pdb::update('worker_jobs', ['php_bin' => 'Unuseable (CGI): ' . $php], ['id' => $job_id]);
            throw new WorkerJobException('Found a PHP binary, but it\'s a CGI binary; CLI binary required for executing background tasks');
        }

        Pdb::update('worker_jobs', ['php_bin' => $php], ['id' => $job_id]);

        $shell = Shell::run([
            'cmd' => "{$php} -d {0} {1} {2}",
            'args' => [
                'safe_mode=0',
                WEBROOT . KOHANA,
                "worker_job/run/{$job_id}/{$job_code}",
            ],
            'env' => [
                'PHP_S_WORKER' => 1,
                'PHP_S_HTTP_HOST' => $_SERVER['HTTP_HOST'],
                'PHP_S_PROTOCOL' => Request::protocol(),
                'PHP_S_WEBDIR' => Kohana::config('core.site_domain'),
            ],
        ]);

        if ($shell->pid == 0) {
            $ex = new WorkerJobException('Failed to start process');
            $ex->cmd = $shell->config->getCommand();
            throw $ex;
        }

        // Do several status checks
        $num_checks = 20;
        $status = null;
        for ($i = 0; $i < $num_checks; $i++) {
            usleep(1000 * 50);

            $q = "SELECT status FROM ~worker_jobs WHERE id = ?";
            $status = Pdb::query($q, [$job_id], 'val');

            if ($status != 'Prepared') {
                break;
            }
        }

        // If it's still not running after all the checks, complain
        if ($status == 'Prepared') {
            $cmd = $shell->config->getCommand();
            $output = $shell->readAll();

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

        return [
            'job_id' => $job_id,
            'log_url' => 'admin/edit/worker_job/' . $job_id
        ];
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

        if (getenv('SITES_PHP_BIN')) {
            array_unshift($paths, getenv('SITES_PHP_BIN'));
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
    * Returns an array of ['status', 'metric1val', 'metric2val', 'metric3val'], or NULL on error.
    **/
    public static function getStatus($job_id)
    {
        $q = "SELECT status, metric1val, metric2val, metric3val FROM ~worker_jobs WHERE id = ?";
        return Pdb::query($q, [$job_id], 'row');
    }

}


