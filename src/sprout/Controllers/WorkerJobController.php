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

use Sprout\Helpers\Pdb;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\Worker;


/**
 * Runs worker jobs (i.e. Helpers which extend {@see WorkerBase})
 */
class WorkerJobController extends Controller
{

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

        $q = "SELECT class_name, args FROM ~worker_jobs WHERE id = ? AND code = ?";
        $job = Pdb::query($q, [$job_id, $job_code], 'row');

        Worker::start($job_id);

        $inst = Sprout::instance($job['class_name'], 'Sprout\Helpers\WorkerBase');

        $args = json_decode($job['args'], true);

        call_user_func_array(array($inst, 'run'), $args);
    }

}
