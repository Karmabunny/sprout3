<?php

use PHPUnit\Framework\TestCase;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\WorkerCtrl;
use Sprout\TestModules\TestModule\Jobs\TestJob;
use Sprout\TestModules\TestModule\Jobs\TestWorker;

/**
 *
 */
class WorkerTest extends TestCase
{

    public function testWorker()
    {
        $job = WorkerCtrl::start(TestWorker::class, 5, 10, 15);

        sleep(1);

        $job = Pdb::get('worker_jobs', $job['job_id']);

        $this->assertEquals(5, $job['metric1val']);
        $this->assertEquals(10, $job['metric2val']);
        $this->assertEquals(15, $job['metric3val']);

        $this->assertEquals('Success', $job['status']);
    }


    public function testJob()
    {
        $job = WorkerCtrl::start(TestJob::class, arg1: 5, arg2: 10, arg3: 15);

        sleep(1);

        $job = Pdb::get('worker_jobs', $job['job_id']);

        $this->assertEquals(5, $job['metric1val']);
        $this->assertEquals(10, $job['metric2val']);
        $this->assertEquals(15, $job['metric3val']);
    }


    public function testJobQueue()
    {
        $job = new TestJob();
        $job->arg1 = 5;
        $job->arg2 = 10;
        $job->arg3 = 15;

        $job_id1 = WorkerCtrl::push($job);

        $job = new TestJob();
        $job->arg1 = 3;
        $job->arg2 = 6;
        $job->arg3 = 9;

        $job_id2 = WorkerCtrl::push($job);

        sleep(5);

        $job1 = Pdb::get('worker_jobs', $job_id1);
        $job2 = Pdb::get('worker_jobs', $job_id2);

        $this->assertEquals(5, $job1['metric1val']);
        $this->assertEquals(10, $job1['metric2val']);
        $this->assertEquals(15, $job1['metric3val']);

        $this->assertEquals(3, $job2['metric1val']);
        $this->assertEquals(6, $job2['metric2val']);
        $this->assertEquals(9, $job2['metric3val']);

        $this->assertEquals($job1['date_added'], $job2['date_added']);
        $this->assertNotEquals($job1['date_started'], $job2['date_started']);
        $this->assertNotEquals($job1['date_success'], $job2['date_success']);
    }
}
