<?php

$config['default'] = [
    'driver' => 'pdb',
    'config' => [
        'autoRelease' => true,
        'uniqueLocks' => false,
        'releaseAllLocks' => true,
    ],

    // 'driver' => 'redis',
    // 'config' => [
    //     'autoRelease' => true,
    //     'prefix' => 'mutex:',
    //     'autoExpire' => 60,
    // ],
];
