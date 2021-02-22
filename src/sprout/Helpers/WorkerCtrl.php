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
    * @param mixed ... Additional arguments are passed to the `run` call
    * @return array Job details
    **/
    public static function start($class_name)
    {
        $inst = Sprout::instance($class_name);

        if (!($inst instanceof WorkerBase)) {
            throw new InvalidArgumentException('Provided class is not a subclass of "Worker".');
        }

        $args = func_get_args();
        array_shift($args);

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

        // Set up an environment for the worker task
        putenv('PHP_S_WORKER=1');
        putenv('PHP_S_HTTP_HOST=' . $_SERVER['HTTP_HOST']);
        putenv('PHP_S_PROTOCOL=' . Request::protocol());
        putenv('PHP_S_WEBDIR=' . Kohana::config('core.site_domain'));

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

        // Run
        $arg = escapeshellarg("worker_job/run/{$job_id}/{$job_code}");
        $cmd = "{$php} -d 'safe_mode=0' index.php {$arg} >/dev/null 2>/dev/null & echo $!";
        $pid = exec($cmd);

        if ($pid == 0) {
            $ex = new WorkerJobException('Failed to start process');
            $ex->cmd = $cmd;
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
            $err = "Process isn't running (failed {$num_checks}x status checks)";
            $ex = new WorkerJobException($err);
            $ex->cmd = $cmd;
            throw $ex;
        }

        return [
            'job_id' => $job_id,
            'log_url' => 'admin/edit/worker_job/' . $job_id
        ];
    }


    /**
    * Looks in a few places for a PHP binary
    **/
    private static function findPhp()
    {
        $paths = array(
            '/usr/bin/php-cli',
            '/usr/bin/php',
            '/usr/local/bin/php-cli',
            '/usr/local/bin/php',
            'php-cli',
            'php',
        );

        // Try various paths, both absolute and relying on $PATH
        foreach ($paths as $p) {
            $version = @shell_exec($p . ' --version');
            if ($version) {
                return array($p, $version);
            }
        }

        return null;
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


