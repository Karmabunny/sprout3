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

use Exception;
use PDOStatement;
use Throwable;

use Kohana;
use Sprout\Exceptions\WorkerJobException;

/**
* Functions called by worker libraries to indicate status, etc
**/
class Worker
{

    /** @var PDOStatement */
    protected static $stmt_message;

    /** @var PDOStatement */
    protected static $stmt_metric;

    /** @var int */
    public static $job_id;

    /** @var int */
    public static $starttime;


    /**
    * Called just before the worker thread is started.
    *
    * @param int $job_id
    * @return true
    * @throws Exception
    **/
    public static function start($job_id)
    {
        $pdb = WorkerCtrl::getPdb();

        self::$job_id = $job_id;
        self::$starttime = time();

        if (PHP_SAPI !== 'cli') {
            throw new WorkerJobException('Worker jobs MUST be run in CLI mode.');
        }

        ini_set('memory_limit', '32M');

        set_exception_handler(array('Sprout\\Helpers\\Worker', 'exceptionHandler'));
        register_shutdown_function(array('Sprout\\Helpers\\Worker', 'shutdown'));

        $update_fields = array();
        $update_fields['date_started'] = $pdb->now();
        $update_fields['date_modified'] = $pdb->now();
        $update_fields['pid'] = getmypid();
        $update_fields['log'] = '';
        $update_fields['memuse'] = memory_get_usage(true);
        $update_fields['status'] = 'Running';

        $pdb->update('worker_jobs', $update_fields, array('id' => $job_id));

        // Prepare a statement for message updating; this is lots faster than direct queries
        $q = "UPDATE ~worker_jobs
            SET
                log = CONCAT(log, '[', :date, '] ', :message, '\n'), memuse = :memuse, date_modified = :now
            WHERE
                id = :id";
        self::$stmt_message = $pdb->prepare($q);

        // There are three metrics, so prepare three statements
        for ($num = 1; $num <= 3; $num++) {
            $q = "UPDATE ~worker_jobs
                SET
                    metric{$num}val = :value, date_modified = :now
                WHERE
                    id = :id";
            self::$stmt_metric[$num] = $pdb->prepare($q);
        }

        self::message('Starting job');

        return true;
    }


    /**
    * Save and output log message for the currently running worker job
    *
    * @param string $message The message to log
    * @return void
    **/
    public static function message($message)
    {
        echo $message, "\n";
        flush();

        if (!self::$job_id) return;

        $pdb = WorkerCtrl::getPdb();

        // Multiline messages are inserted multiple times as individual lines
        // Only compute the date and memory usage once though
        $args = [
            ':now' => $pdb->now(),
            ':date' => date('h:i:s a'),
            ':memuse' => memory_get_usage(true),
            ':id' => self::$job_id,
        ];

        $line = strtok($message, "\n");

        while ($line !== false) {
            $args[':message'] = $line;
            $pdb->execute(self::$stmt_message, $args, 'null');
            $line = strtok("\n");
        }
    }


    /**
    * Set a metric
    *
    * @param int $num The metric index; 1, 2, or 3
    * @param int $value The metric value
    * @return void
    **/
    public static function metric($num, $value)
    {
        if (!self::$job_id) return;

        $pdb = WorkerCtrl::getPdb();
        $pdb->execute(self::$stmt_metric[$num], [
            ':now' => $pdb->now(),
            ':value' => $value,
            ':id' => self::$job_id,
        ], 'null');
    }


    /**
    * Report that a worker job failed.
    * Terminates the script.
    *
    * @param string $message Optional message to be logged
    * @return never Terminates script
    **/
    public static function failure($message = '')
    {
        if ($message != '') {
            self::message($message);
        }

        if (self::$job_id) {
            $pdb = WorkerCtrl::getPdb();
            $pdb->update('worker_jobs', [
                'status' => 'Failed',
                'date_failure' => $pdb->now(),
                'date_modified' => $pdb->now(),
                'pid' => 0,
            ], [
                'id' => self::$job_id,
            ]);
        }

        self::message('Worker terminated');
        exit(1);
    }


    /**
    * Report the successful completion of the worker job.
    * Terminates the script.
    *
    * @return never Terminates script
    **/
    public static function success()
    {
        self::message('Done.');

        $jobtime = round(time() - self::$starttime);
        $peakmem = File::humanSize(memory_get_peak_usage());

        self::message('');
        self::message('Total time:       ' . $jobtime . ' second' . ($jobtime == 1 ? '' : 's'));
        self::message('Peak memory use:  ' . $peakmem);

        if (self::$job_id) {
            $pdb = WorkerCtrl::getPdb();
            $pdb->update('worker_jobs', [
                'status' => 'Success',
                'date_success' => $pdb->now(),
                'date_modified' => $pdb->now(),
                'pid' => 0,
            ], [
                'id' => self::$job_id,
            ]);
        }

        echo "\n";
        exit(0);
    }


    /**
    * Exception and error handling for worker jobs
    *
    * @param Throwable $exception
    * @return never
    **/
    public static function exceptionHandler($exception)
    {
        Kohana::logException($exception);

        self::message('EXCEPTION ' . get_class($exception));
        self::message('Message:  ' . $exception->getMessage());
        self::message('File:     ' . $exception->getFile());
        self::message('Line:     ' . $exception->getLine());
        self::message('');
        self::message($exception->getTraceAsString());
        self::failure();
    }

    /**
    * Shutdown function, for catching fatal errors
    *
    * @return void|never
    **/
    public static function shutdown()
    {
        $error = error_get_last();
        if ($error and $error['type'] == 1) {
            self::failure('FATAL ERROR: ' . $error['message']);
        }
    }

}


