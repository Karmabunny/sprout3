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
use karmabunny\interfaces\JobInterface;
use karmabunny\interfaces\QueueInterface;
use karmabunny\kb\Configure;
use karmabunny\pdb\DataBinders\ConcatDataBinder;
use karmabunny\pdb\Exceptions\QueryException;
use karmabunny\pdb\Pdb as PdbInstance;
use Kohana;
use Sprout\Exceptions\WorkerJobException;
use Symfony\Component\Process\Process;

use Throwable;

/**
* Functions to update and report on worker status
**/
class WorkerCtrl
{

    /**
     * Get a queue instance for use with worker jobs.
     *
     * @param string $group
     * @return QueueInterface
     */
    public static function getQueue(string $group = 'default'): QueueInterface
    {
        $config = Kohana::config('queue.' . $group, false, false);

        if ($config === null) {
            throw new InvalidArgumentException('Queue group not found: ' . $group);
        }

        $config['class'] ??= WorkerQueue::class;
        $config['channel'] ??= $group;

        $queue = Sprout::instance($config['class'], QueueInterface::class);
        Configure::update($queue, $config);

        return $queue;
    }


    /**
     * Push a job to the queue.
     *
     * Options are specific to the queue implementation, default is {@see WorkerQueue}.
     *
     * - timeout: in seconds (default 300)
     * - priority: smaller numbers are executed first (default 100)
     *
     * An additional 'channel' option will change the target queue.
     *
     * @param JobInterface $job
     * @param array $options
     * @throws WorkerJobException If the job failed to start
     * @return string
     */
    public static function push(JobInterface $job, array $options = []): string
    {
        $channel = $options['channel'] ?? 'default';
        return self::getQueue($channel)->push($job, $options);
    }


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
        /** @var WorkerInterface $inst */
        $inst = Sprout::instance($class_name, WorkerInterface::class);

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

        $job = $pdb->get('worker_jobs', $job_id);
        self::execute($job);

        return [
            'job_id' => $job_id,
            'log_url' => 'admin/edit/worker_job/' . $job_id
        ];
    }


    /**
     * Run the worker queue for a given channel.
     *
     * The channel must use an instance of {@see WorkerQueue}.
     *
     * @param string $channel
     * @param int $timeout in seconds (default 10)
     * @param callable|null $logger
     * @return bool
     */
    public static function runQueue(string $channel, int $timeout = 10, ?callable $logger = null): bool
    {
        $queue = self::getQueue($channel);

        if (!$queue instanceof WorkerQueue) {
            throw new InvalidArgumentException('Channel must use an instance of WorkerQueue');
        }

        $mutex = Mutex::create('worker:queue:' . $channel);

        $log = function(string $message) use ($logger) {
            if ($logger) {
                $ts = date('Y-m-d H:i:s');
                $logger("[{$ts}] {$message}");
            }
        };

        if (!$mutex->acquire()) {
            $log('Worker queue already running');
            return false;
        }

        $pdb = self::getPdb();

        while (true) {
            $log('Waiting for a job...');
            $job = $queue->pop($timeout);

            if (!$job) {
                $log("No jobs, exiting after {$timeout} seconds");
                break;
            }

            $id = (int) $job->getId();
            $log("Running job: #{$id}...");

            try {
                $row = $pdb->get('worker_jobs', $id);
                $process = self::execute($row);

                $exit = $process->wait();
                $log("Exit code: {$exit}");

                if ($exit != 0) {
                    $ex = new WorkerJobException("Exit code: {$exit}");
                    $ex->cmd = $process->getCommandLine();
                    throw $ex;
                }

                $update_data = [];
                $update_data['status'] = 'Success';
                $update_data['pid'] = 0;
                $update_data['log'] = new ConcatDataBinder('Done.');
                $update_data['date_modified'] = $pdb->now();
                $update_data['date_success'] = $pdb->now();

                $pdb->update('worker_jobs', $update_data, ['id' => $id]);

            } catch (Throwable $exception) {
                Kohana::logException($exception);

                $error = 'EXCEPTION ' . get_class($exception) . "\n";
                $error .= "Message:  {$exception->getMessage()}\n";
                $error .= "File:     {$exception->getFile()}\n";
                $error .= "Line:     {$exception->getLine()}\n";
                $error .= "\n";
                $error .= $exception->getTraceAsString();

                $log($error);

                $update_data = [];
                $update_data['status'] = 'Failed';
                $update_data['pid'] = 0;
                $update_data['log'] = new ConcatDataBinder($error);
                $update_data['date_modified'] = $pdb->now();
                $update_data['date_failure'] = $pdb->now();

                $pdb->update('worker_jobs', $update_data, ['id' => $id]);
            }

            sleep(1);
        }

        $mutex->release();

        return true;
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

        $process = new Process($args, getcwd(), $env, timeout: $job['timeout'] ?: null);
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
    public static function findPhp()
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


