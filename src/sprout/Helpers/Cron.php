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

use Kohana;


/**
 * Methods for initialising, recording the progress of, and finalising and cron scripts
 */
class Cron
{
    protected static $pdo;
    protected static $stmt_message;
    private static $job_id;
    private static $job_name;


    /**
    * Called at the beginning of a cron job.
    *
    * Checks for appropriate access permissions.
    * Creates a database record for the logging messages.
    *
    * Note that this function opens an additional database connection, allowing logging
    * to continue to work even if the main script is using transactions - which it should be.
    **/
    public static function start($job_name)
    {
        self::$job_name = $job_name;

        // Require admin auth for browser-based requests. These *should* be tunneled via
        // the CronJobAdminController's UI but it's possible to call the methods directly
        // via a route if the calling code doesn't need CSRF protection.
        if (PHP_SAPI !== 'cli') {
            $_ENV['CRON'] = 1;
            AdminAuth::checkLogin();
            Kohana::closeBuffers();
            header('Content-type: text/plain');
        }

        ini_set('memory_limit', '128M');

        set_exception_handler(array('Sprout\\Helpers\\Cron', 'exceptionHandler'));
        register_shutdown_function(array('Sprout\\Helpers\\Cron', 'shutdown'));

        $update_fields = array();
        $update_fields['name'] = $job_name;
        $update_fields['log'] = '';
        $update_fields['status'] = 'Running';
        $update_fields['date_added'] = Pdb::now();
        $update_fields['date_modified'] = Pdb::now();

        self::$job_id = Pdb::insert('cron_jobs', $update_fields);

        self::$pdo = Pdb::connect('default');
        $pf = Pdb::prefix();

        // Prepare a statement for message updating; this is lots faster than direct queries
        $q = "UPDATE {$pf}cron_jobs
            SET
                log = CONCAT(log, '[', :date, '] ', :message, '\n'), date_modified = NOW()
            WHERE
                id = " . self::$job_id;
        self::$stmt_message = self::$pdo->prepare($q);

        self::message('Starting job ' . $job_name);

        return true;
    }


    /**
    * Save and output log message for the currently running cron job
    *
    * @param string $message The message to log
    * @return void
    **/
    public static function message($message = '')
    {
        echo $message, "\n";
        flush();

        if (!self::$job_id) return;

        // Only do the splitting by newline if required, to save string copies and therefore RAM.
        if (strpos($message, "\n") === false) {
            self::$stmt_message->execute([
                ':date' => date('h:i:s a'),
                ':message' => $message,
            ]);
        } else {
            // Multiline messages are inserted multiple times as individual lines
            // Only compute the date and memory usage once though
            $args = [
                ':date' => date('h:i:s a'),
            ];
            foreach (explode("\n", $message) as $ln) {
                $args[':message'] = $ln;
                self::$stmt_message->execute($args);
            }
        }
    }


    /**
    * Report that a cron job failed.
    * Should be used with a return statement to end the execution of the job.
    **/
    public static function failure($message = '')
    {
        if ($message != '') {
            self::message($message);
        }

        if (self::$job_id) {
            $pf = Pdb::prefix();
            $q = "UPDATE {$pf}cron_jobs SET
                    status = 'Failed', date_modified = NOW()
                WHERE
                    id = " . self::$job_id;
            self::$pdo->query($q);
        }

        echo 'Cron job failed. See log entry #' . self::$job_id . ' for more info';
        exit(1);
    }


    /**
    * Report the successful completion of the cron job.
    **/
    public static function success()
    {
        self::message('Done.');

        if (self::$job_id) {
            $pf = Pdb::prefix();
            $q = "UPDATE {$pf}cron_jobs SET
                    status = 'Success', date_modified = NOW()
                WHERE
                    id = " . self::$job_id;
            self::$pdo->query($q);
        }

        exit(0);
    }


    /**
    * Exception and error handling for CRON jobs
    **/
    public static function exceptionHandler($exception)
    {
        $log_id = Kohana::logException($exception);
        self::message('EXCEPTION ' . get_class($exception));
        self::message('Log ID:   ' . $log_id);
        self::message('Message:  ' . $exception->getMessage());
        self::message('File:     ' . $exception->getFile());
        self::message('Line:     ' . $exception->getLine());
        self::message('');
        self::message($exception->getTraceAsString());
        self::failure();
    }


    /**
    * Shutdown function, for catching fatal errors
    **/
    public static function shutdown()
    {
        $error = error_get_last();
        if ($error['type'] == 1) {
            self::failure('FATAL ERROR: ' . $error['message']);
        }
    }

}

