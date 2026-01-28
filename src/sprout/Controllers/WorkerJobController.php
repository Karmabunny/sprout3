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

use karmabunny\interfaces\JobInterface;
use karmabunny\interfaces\JsonDeserializable;
use karmabunny\kb\Configure;
use Kohana;
use Sprout\Helpers\Mutex;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\Worker;
use Sprout\Helpers\WorkerBase;
use Sprout\Helpers\WorkerCtrl;
use Sprout\Helpers\WorkerInterface;
use Sprout\Helpers\WorkerJobInterface;

/**
 * Runs worker jobs (i.e. Helpers which extend {@see WorkerBase})
 */
class WorkerJobController extends Controller
{

    /**
     * Get a job from the database.
     *
     * Limited to [class_name, args, status, pid].
     *
     * @param int $job_id
     * @param string $job_code
     * @return array
     */
    protected static function getJob(int $job_id, string $job_code): array
    {
        $q = "SELECT class_name, args, status, pid FROM ~worker_jobs WHERE id = ? AND code = ?";
        return Pdb::query($q, [$job_id, $job_code], 'row');
    }


    /**
     * Actually run a worker job
     * This method is almost always called from CLI
     *
     * @param int $job_id ID of the job to run
     * @param string $job_code Random string used to protect against unauthorised access
     */
    public function run($job_id, $job_code)
    {
        $job_id = (int) $job_id;
        $job = self::getJob($job_id, $job_code);

        if ($job['status'] !== 'Prepared') {
            echo "Job #{$job_id} is not prepared, status: {$job['status']}. Exiting.\n";
            exit(1);
        }

        $mutex = Mutex::create('worker:job:' . $job_id);

        Worker::start($job_id);

        $class = $job['class_name'];
        $args = json_decode($job['args'], true);

        if (is_subclass_of($class, WorkerJobInterface::class)) {
            /** @var class-string<JsonDeserializable> $class */
            $args['id'] = $job_id;
            $inst = $class::fromJson($args);
        } else {
            $inst = Sprout::instance($class, WorkerInterface::class);

            if (!$inst instanceof WorkerBase) {
                $args['id'] = $job_id;
                Configure::update($inst, $args);
            }
        }

        if (!$mutex->acquire()) {
            $job = self::getJob($job_id, $job_code);
            echo "Job #{$job_id} already running, pid: {$job['pid']}. Exiting.\n";
            exit(1);
        }

        try {
            if ($inst instanceof JobInterface) {
                $inst->run();
            } else {
                call_user_func_array(array($inst, 'run'), $args);
            }
        } finally {
            $mutex->release();
        }
    }


    /**
     * Run the worker queue for a given channel
     *
     * @param string $channel
     */
    public function runQueue(string $channel)
    {
        Kohana::closeBuffers(true);

        WorkerCtrl::runQueue($channel, function(string $message) {
            echo $message . PHP_EOL;
        });
    }
}
