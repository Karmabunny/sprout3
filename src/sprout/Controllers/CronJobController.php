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

namespace Sprout\Controllers;

use Kohana;
use Sprout\Controllers\Admin\ManagedAdminController;
use Sprout\Helpers\Mutex;
use Sprout\Helpers\Register;
use Sprout\Helpers\Sprout;
use Symfony\Component\Process\Process;

/**
 * Runs scheduled tasks assigned by {@see Register::cronJob} on behalf of the UN*X cron utility
 */
class CronJobController extends Controller
{

    public function __construct()
    {
        if (PHP_SAPI !== 'cli') {
            die('Cron jobs must be run via CLI');
        }
        Kohana::closeBuffers();
        $_ENV['CRON'] = 1;
    }


    /**
     * Run a given cron schedule
     *
     * Each individual job is run independently of the others using a php sub-process
     * This means that *any* failure of a given job will be isolated and other
     * jobs will continue to run
     *
     * @param string $schedule Cron job schedule, e.g. 'daily' or 'weekly'
     * @return void Terminates script with exit status of number of failed jobs (0 = no failures)
     */
    public function run($schedule)
    {
        if (PHP_SAPI !== 'cli') {
            fwrite(STDERR, 'Cron jobs must be run via CLI' . PHP_EOL);
            exit(1);
        }

        $mutex = Mutex::create('cron_job:' . $schedule);

        if (!$mutex->acquire()) {
            fwrite(STDERR, "Cron schedule [{$schedule}] already running" . PHP_EOL);
            exit(0);
        }

        $jobs = Register::getCronJobs($schedule);
        echo 'Num jobs: ', count($jobs), PHP_EOL;

        $failed = 0;
        foreach ($jobs as [$class, $func]) {
            echo PHP_EOL, $class . '::' . $func, PHP_EOL;

            $args = [
                $_SERVER['_'] ?? PHP_BINARY,
                WEBROOT . KOHANA,
                'cron_job/runJob',
                $class,
                $func,
            ];

            $process = new Process($args, timeout: null);
            $process->start();

            if (!$process->isRunning()) {
                echo ' !! Failed to start process', PHP_EOL;
                continue;
            }

            echo "    PID: {$process->getPid()}", PHP_EOL;
            echo '    ';

            $exit = $process->wait(function($type, $buffer) {
                echo str_replace("\n", "\n    ", $buffer);
                flush();
            });

            echo PHP_EOL;
            echo '    EXIT ', $exit, PHP_EOL;

            if ($exit !== 0) {
                $failed++;
            }
        }

        $mutex->release();

        echo PHP_EOL, 'Failures: ', $failed, PHP_EOL;
        exit($failed);
    }


    /**
     * Internal job runner for cron jobs
     *
     * Just creates an instance of the class (must be a controller), then calls the method
     *
     * Parameters are passed by CLI argv array; 2 => class, 3 => function
     */
    public function runJob()
    {
        $class = $_SERVER['argv'][2];
        $func = $_SERVER['argv'][3];

        if (is_subclass_of($class, ManagedAdminController::class)) {
            require_once APPPATH . 'admin_load.php';
        }

        $inst = Sprout::instance(
            $class,
            ['Sprout\\Controllers\\Controller']
        );

        call_user_func([$inst, $func]);
    }

}
