<?php
/*
 * Copyright (C) 2025 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */
use PHPUnit\Framework\TestCase;
use Sprout\Helpers\Pdb;

/**
 *
 */
class CronTest extends TestCase
{


    public static function dataIterations()
    {
        return [
            [1],
            [2],
            [3],
        ];
    }

    /** @dataProvider dataIterations */
    public function testCron($iteration)
    {
        // Hack fix the interpreter which comes up as 'composer' instead of 'php'.
        putenv('_=' . PHP_BINARY);

        Pdb::delete('cron_jobs', ['name' => 'Test Cron']);

        // Align to the nearest second.
        $time = microtime(true);
        usleep(intval((1 + floor($time) - $time) * 1000000));

        $cmd = WEBROOT . KOHANA . ' cron_job/run/test';

        $res = shell_exec("exec php {$cmd} &");
        $this->assertNotFalse($res);

        $res = shell_exec("exec php {$cmd} &");
        $this->assertNotFalse($res);

        sleep(3);

        $jobs = Pdb::find('cron_jobs')
            ->where(['name' => 'Test Cron'])
            ->orderBy('id ASC')
            ->limit(2)
            ->all();

        $this->assertCount(2, $jobs);

        $this->assertEquals('Test Cron', $jobs[0]['name']);
        $this->assertEquals('Test Cron', $jobs[1]['name']);

        $this->assertEquals('Success', $jobs[0]['status']);
        $this->assertEquals('Success', $jobs[1]['status']);

        // This becomes our reference.
        $time = strtotime($jobs[0]['date_added']);


        $added = date('Y-m-d H:i:s', $time);
        $this->assertEquals($added, $jobs[0]['date_added']);

        // Modified date isn't great, but it's what we've got for now.
        $finished = date('Y-m-d H:i:s', strtotime('+1 second', $time));
        $this->assertEquals($finished, $jobs[0]['date_modified']);

        $added = date('Y-m-d H:i:s', strtotime('+1 second', $time));
        $this->assertEquals($added, $jobs[1]['date_added']);

        $finished = date('Y-m-d H:i:s', strtotime('+2 second', $time));
        $this->assertEquals($finished, $jobs[1]['date_modified']);
    }
}
