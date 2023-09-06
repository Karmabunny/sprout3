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
use PDO;
use PDOStatement;
use Throwable;

/**
* Functions called by worker libraries to indicate status, etc
**/
class Worker
{
    /** @var PDO */
    protected static $pdo;

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
        self::$job_id = $job_id;
        self::$starttime = time();

        if ($_SERVER['DOCUMENT_ROOT']) {
            throw new Exception('Worker jobs MUST be run in CLI mode.');
        }

        ini_set('memory_limit', '32M');

        set_exception_handler(array('Sprout\\Helpers\\Worker', 'exceptionHandler'));
        register_shutdown_function(array('Sprout\\Helpers\\Worker', 'shutdown'));

        $update_fields = array();
        $update_fields['date_started'] = Pdb::now();
        $update_fields['date_modified'] = Pdb::now();
        $update_fields['pid'] = getmypid();
        $update_fields['log'] = '';
        $update_fields['memuse'] = memory_get_usage(true);
        $update_fields['status'] = 'Running';

        Pdb::update('worker_jobs', $update_fields, array('id' => $job_id));

        self::$pdo = Pdb::connect('default');
        $pf = Pdb::prefix();

        // Prepare a statement for message updating; this is lots faster than direct queries
        $q = "UPDATE {$pf}worker_jobs
            SET
                log = CONCAT(log, '[', :date, '] ', :message, '\n'), memuse = :memuse, date_modified = NOW()
            WHERE
                id = " . self::$job_id;
        self::$stmt_message = self::$pdo->prepare($q);

        // There are three metrics, so prepare three statements
        for ($num = 1; $num <= 3; $num++) {
            $q = "UPDATE {$pf}worker_jobs
                SET
                    metric{$num}val = :value, date_modified = NOW()
                WHERE
                    id = " . self::$job_id;
            self::$stmt_metric[$num] = self::$pdo->prepare($q);
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

        // Only do the splitting by newline if required, to save string copies and therefore RAM.
        if (strpos($message, "\n") === false) {
            self::$stmt_message->execute([
                ':date' => date('h:i:s a'),
                ':message' => $message,
                ':memuse' => memory_get_usage(true),
            ]);
        } else {
            // Multiline messages are inserted multiple times as individual lines
            // Only compute the date and memory usage once though
            $args = [
                ':date' => date('h:i:s a'),
                ':memuse' => memory_get_usage(true),
            ];
            foreach (explode("\n", $message) as $ln) {
                $args[':message'] = $ln;
                self::$stmt_message->execute($args);
            }
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

        self::$stmt_metric[$num]->execute([
            ':value' => $value,
        ]);
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
            $pf = Pdb::prefix();
            $q = "UPDATE {$pf}worker_jobs SET
                    status = 'Failed', date_failure = NOW(), date_modified = NOW(), pid = 0
                WHERE
                    id = " . self::$job_id;
            self::$pdo->query($q);
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
            $pf = Pdb::prefix();
            $q = "UPDATE {$pf}worker_jobs SET
                    status = 'Success', date_success = NOW(), date_modified = NOW(), pid = 0
                WHERE
                    id = " . self::$job_id;
            self::$pdo->query($q);
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


