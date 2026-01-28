<?php

use PHPUnit\Framework\TestCase;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\WorkerCtrl;
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


}
