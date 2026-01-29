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

namespace Sprout\Helpers;

use karmabunny\interfaces\ArrayableInterface;
use karmabunny\interfaces\JobInterface;
use karmabunny\interfaces\QueueInterface;
use karmabunny\interfaces\ConfigurableInterface;
use karmabunny\kb\Configure;
use karmabunny\kb\UpdateTrait;
use Kohana;
use Sprout\Exceptions\WorkerJobException;
use Symfony\Component\Process\Process;

/**
 * A queue of worker jobs.
 *
 * This executes jobs one-by-one, in the order they were added.
 *
 * @see WorkerJob
 * @see WorkerCtrl::push()
 */
class WorkerQueue implements ConfigurableInterface, QueueInterface
{
    use UpdateTrait;

    /**
     * A channel group for jobs.
     *
     * This must be unique. If not specified, uses the group name (i.e. default).
     *
     * @var string
     */
    public $channel;

    /**
     * Start the queue immediately on push().
     *
     * Otherwise the queue must be managed externally by a service, e.g. supervisord, systemd, etc.
     *
     * @var bool
      */
    public $immediate = true;

    /**
     * A default timeout.
     *
     * Overriden by push($job, ['timeout' => ...]);
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * A default priority.
     *
     * Smaller numbers are executed first.
     *
     * Overriden by push($job, ['priority' => ...]).
     *
     * @var int
     */
    public $priority = 100;


    /** @inheritdoc */
    public function push(JobInterface $job, array $options = []): string
    {
        $options['timeout'] ??= $this->timeout;
        $job_id = self::insertJob($job, $options);

        if ($this->immediate) {
            $this->executeQueue();
        }

        return (string) $job_id;
    }


    /** @inheritdoc */
    public function pop(int $timeout = 0): ?JobInterface
    {
        $pdb = WorkerCtrl::getPdb();

        $expires = strtotime("+{$timeout} seconds");

        do {
            $row = $pdb->find('worker_jobs')
                ->select([
                    'id',
                    'code',
                    'class_name',
                    'args',
                ])
                ->where([
                    'channel' => $this->channel,
                    'status' => 'Prepared',
                ])
                ->orderBy([
                    'priority' => 'ASC',
                    'date_added' => 'DESC',
                    'id' => 'DESC',
                ])
                ->throw(false)
                ->one();

            if ($row) {
                $class = $row['class_name'];
                $args = Json::decode($row['args']);

                if (is_subclass_of($class, WorkerJobInterface::class)) {
                    $inst = $class::fromJson($args);
                } else {
                    $inst = Sprout::instance($class, JobInterface::class);
                    Configure::update($inst, $args);
                }

                if ($inst instanceof WorkerJob) {
                    $inst->id = $row['id'];
                    $inst->code = $row['code'];
                    $inst->channel = $this->channel;
                }

                return $inst;
            }

            sleep(1);
        } while ($timeout === 0 or $expires > time());

        return null;
    }


    /**
     * Insert a job into the database.
     *
     * @param JobInterface $job
     * @param array $options
     * @return int
     */
    protected function insertJob(JobInterface $job, array $options = []): int
    {
        [$php, $version] = WorkerCtrl::findPhp();

        if (!$php or strpos($version, 'cli') === false) {
            throw new WorkerJobException('Unable to find working PHP binary');
        }

        $job_code = Security::randStr(8);

        $metric_names = [];

        if ($job instanceof WorkerInterface) {
            $job_name = $job->getName();
            $metric_names = $job->getMetricNames();
        } else {
            $job_name = basename(str_replace('\\', '/', $job::class));
        }

        $args = $job instanceof ArrayableInterface
            ? $job->toArray()
            : get_object_vars($job);


        if ($job instanceof WorkerJob) {
            unset($args['id']);
            unset($args['code']);
            unset($args['channel']);
        }

        $pdb = WorkerCtrl::getPdb();

        $update_fields = [];
        $update_fields['name'] = $job_name;
        $update_fields['code'] = $job_code;
        $update_fields['class_name'] = $job::class;
        $update_fields['args'] = json_encode($args);
        $update_fields['status'] = 'Prepared';
        $update_fields['metric1name'] = $metric_names[1];
        $update_fields['metric2name'] = $metric_names[2];
        $update_fields['metric3name'] = $metric_names[3];
        $update_fields['php_bin'] = $php;
        $update_fields['date_added'] = $pdb->now();
        $update_fields['date_modified'] = $pdb->now();

        // Queue-specific fields.
        $update_fields['channel'] = $this->channel;
        $update_fields['timeout'] = $options['timeout'] ?? 0;
        $update_fields['priority'] = $options['priority'] ?? 0;

        // TODO in future, some more advanced options:
        // $update_fields['delay'] = $options['delay'] ?? 0;
        // $update_fields['retry'] = $options['retry'] ?? 0;

        $job_id = $pdb->insert('worker_jobs', $update_fields);
        return $job_id;
    }


    /**
     * Run the queue immediately.
     *
     * @return void
     * @throws WorkerJobException
     */
    protected function executeQueue()
    {
        [$php, $version] = WorkerCtrl::findPhp();

        if (!$php or strpos($version, 'cli') === false) {
            throw new WorkerJobException('Unable to find working PHP binary');
        }

        $args = [
            $php,
            '-d',
            'safe_mode=0',
            WEBROOT . KOHANA,
            "worker_job/queue/{$this->channel}",
        ];

        $env = [
            'PHP_S_WORKER' => 1,
            'PHP_S_HTTP_HOST' => $_SERVER['HTTP_HOST'],
            'PHP_S_PROTOCOL' => Request::protocol(),
            'PHP_S_WEBDIR' => Kohana::config('core.site_domain'),
        ];

        $process = new Process($args, getcwd(), $env, timeout: null);
        $process->start();

        if (!$process->isRunning()) {
            $ex = new WorkerJobException('Failed to start process');
            $ex->cmd = $process->getCommandLine();
            throw $ex;
        }
    }

}
