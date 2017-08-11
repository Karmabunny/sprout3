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

use Sprout\Helpers\Register;
use Sprout\Helpers\Sprout;


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
        $jobs = Register::getCronJobs($schedule);
        echo 'Num jobs: ', count($jobs), PHP_EOL;

        $failed = 0;
        foreach ($jobs as $j) {
            echo PHP_EOL, $j[0] . '::' . $j[1], PHP_EOL;

            // Build the shell command string using current interpreter name
            $php = escapeshellcmd($_SERVER['_']);
            $script = escapeshellarg($_SERVER['argv'][0]);
            $class = escapeshellarg($j[0]);
            $func = escapeshellarg($j[1]);
            $cmd = implode(' ', [$php, $script, 'cron_job/runJob', $class, $func, '2>&1']);

            // Start process
            $spec= [
                1 => ['pipe', 'w']
            ];
            $pipes = [];
            $proc = proc_open($cmd, $spec, $pipes);
            if (!is_resource($proc)) {
                echo ' !! Failed to start process', PHP_EOL;
                continue;
            }

            // Output the PID, which may be useful to someone
            $status = proc_get_status($proc);
            echo '    PID ', $status['pid'], PHP_EOL;

            // Stream the output pipe, with an indent of 4x spaces
            echo '    ';
            stream_set_blocking($pipes[1], 0);
            while (!feof($pipes[1])) {
                $response = fgets($pipes[1], 4096);
                $response = str_replace("\n", "\n    ", $response);
                echo $response;
                flush();
            }
            echo PHP_EOL;

            // Wait for process to exit
            fclose($pipes[1]);
            $return = proc_close($proc);
            echo '    EXIT ', $return, PHP_EOL;

            if ($return !== 0) {
                $failed++;
            }
        }

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

        $inst = Sprout::instance(
            $class,
            ['Sprout\\Controllers\\Controller']
        );

        call_user_func([$inst, $func]);
    }

}
