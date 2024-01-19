<?php
/**
 * Rdb settings are defined karmabunny/rdb/RdbConfig.php
 *
 * A quick run-down:
 *
 * - host
 * - prefix
 * - adapter: predis (default) | php-redis | credis
 * - chunk_size: 50
 * - lock_sleep: 5 - milliseconds
 * - options: [] - adapter options
 */

$config['default'] = [
    'host' => 'localhost',
    'prefix' => 'sprout:',
];
