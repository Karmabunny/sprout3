<?php

$config['default'] = [
    'driver' => 'pdb',
    'config' => [
        'autoRelease' => true,
        'uniqueLocks' => true,
        'releaseAllLocks' => false,
    ],

    // 'driver' => 'redis',
    // 'config' => [
    //     'autoRelease' => true,
    //     'prefix' => 'mutex:',
    //     'autoExpire' => 60,
    // ],
];
