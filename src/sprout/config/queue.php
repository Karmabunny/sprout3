<?php

use Sprout\Helpers\WorkerQueue;

$config['default'] = [
    'class' => WorkerQueue::class,
    'channel' => 'default',
    'timeout' => 300,
];
