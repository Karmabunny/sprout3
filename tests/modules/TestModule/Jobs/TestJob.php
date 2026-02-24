<?php
/*
 * Copyright (C) 2026 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

namespace Sprout\TestModules\TestModule\Jobs;

use Sprout\Helpers\Sprout;
use Sprout\Helpers\WorkerJob;

/**
 * An updated worker job, with support for channels.
 */
class TestJob extends WorkerJob
{

    public $arg1;

    public $arg2;

    public $arg3;


    /** @inheritdoc */
    public function getMetricNames(): array
    {
        return [
            1 => 'First run',
            2 => 'Second run',
            3 => 'Final run',
        ];
    }


    /** @inheritdoc */
    public function run()
    {
        $this->log("It's running! ({$this->arg1})");

        for ($i = 1; $i <= $this->arg1; ++$i) {
            usleep(10000);
            $this->metric(1, $i);
        }

        $this->log("Still running! ({$this->arg2})");
        $this->log('$_SERVER:');
        $this->log(print_r($_SERVER, true));

        for ($i = 1; $i <= $this->arg2; ++$i) {
            usleep(10000);
            $this->metric(2, $i);
        }

        $this->log("Almost there! ({$this->arg3})");
        $this->log('Sprout::absRoot():');
        $this->log(Sprout::absRoot());

        for ($i = 1; $i <= $this->arg3; ++$i) {
            usleep(10000);
            $this->metric(3, $i);
        }

        $this->log('Finished');
    }

}
