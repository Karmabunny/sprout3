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

namespace SproutModules\Karmabunny\Demo\Helpers;

use Sprout\Helpers\Sprout;
use Sprout\Helpers\Worker;
use Sprout\Helpers\WorkerBase;


/**
 * A basic demonstration of how worker jobs run, using {@see WorkerBase}
 */
class DemoWorker extends WorkerBase
{
    protected $job_name = 'Demo worker';
    protected $metric_names = [
        1 => 'First run',
        2 => 'Second run',
        3 => 'Final run',
    ];

    /**
     * Do the work which a worker must do
     */
    public function run($a, $b, $c)
    {
        Worker::message("It's running! {$a}");
        for ($i = 1; $i <= $a; ++$i) {
            usleep(10000);
            Worker::metric(1, $i);
        }

        Worker::message("Still running!");
        Worker::message('$_SERVER:');
        Worker::message(print_r($_SERVER, true));
        for ($i = 1; $i <= $b; ++$i) {
            usleep(10000);
            Worker::metric(2, $i);
        }

        Worker::message("Almost there!");
        Worker::message('Sprout::absRoot():');
        Worker::message(Sprout::absRoot());
        for ($i = 1; $i <= $c; ++$i) {
            usleep(10000);
            Worker::metric(3, $i);
        }

        Worker::message('Finished');
        Worker::success();
    }

}
