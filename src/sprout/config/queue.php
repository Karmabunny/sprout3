<?php

use Sprout\Helpers\WorkerQueue;

/**
 * A queue implementation, defaults to WorkerQueue.
 * The options below are specific to this implementation, read the class doc for more details.
 *
 * Multiple channels can be configured with the same queue implementation.
 */
$config['default'] = [
    'class' => WorkerQueue::class,

    'immediate' => true,
    'channel' => 'default',
    'timeout' => 300,
    'priority' => 100,
];
