<?php
/*
 * Copyright (C) 2017 Karmabunny Pty Ltd.
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

use Generator;
use Kohana;
use Kohana_Exception;

/**
 * Performance profiling.
 *
 * A profile item has several properties:
 *
 * - `token`: a message, usually the thing being profiled
 * - `category`: something to group by
 * - `trace`: stack trace, if enabled (config.max_trace)
 * - `time`: time at which the item was created
 * - `duration`: duration of the profile item
 * - `memory`: memory at the time of capture (end)
 * - `memory_delta`: memory difference over the profile
 * - `meta`: extra user data
 * - `request.tag`: a unique ID (SPROUT_REQUEST_TAG, uuid v4)
 * - `request.time`: when the request began (SPROUT_REQUEST_TIME)
 * - `request.duration`: duration of the whole request (until shutdown)
 * - `request.ip`: remote IP address
 * - `request.agent`: browser user agent
 * - `request.url`: URL request (from Router::current_uri)
 * - `request.index`: the profile item index of the current request
 */
class Profiling
{

    /**
     * Default config.
     */
    const DEFAULT = [
        'enabled' => false,
        'path' => STORAGE_PATH . 'logs/profile.log',
        'max_trace' => 10,
        'max_size' => 1024 * 1024 * 20, // 20mb
        'url_filter' => '!^admin|dbtools|_media!',
    ];

    /** @var array[] */
    protected static $logs = [];

    /** @var array[] [ key => array ] */
    protected static $active = [];

    /** @var null|bool */
    private static $_enabled;

    /** @var null|array */
    private static $_config;


    /**
     * Get the profiling config.
     *
     * @return array
     */
    protected static function getConfig(): array
    {
        if (self::$_config === null) {
            $config = Kohana::config('profiling', false, false) ?: [];
            $config = array_merge(self::DEFAULT, $config);

            self::$_config = $config;
        }

        return self::$_config;
    }


    /**
     * Initialise things - this registers the flush method on shutdown.
     *
     * @return bool is profiling enabled?
     */
    protected static function init(): bool
    {
        $enabled = self::isEnabled();

        if ($enabled) {
            register_shutdown_function(function() {
                self::flush();

                // Flush logs from shutdown functions.
                register_shutdown_function([self::class, 'flush']);
            });
        }

        return $enabled;
    }


    /**
     * Enable or disable the profiler.
     *
     * Note, this doesn't guarantee that the profiler will be enabled, as it
     * may be disabled by the 'url_filter' config.
     *
     * @param bool $enabled
     * @return bool
     */
    public static function setEnabled(bool $enabled)
    {
        self::$_config['enabled'] = $enabled;
        self::$_enabled = null;
        return self::isEnabled();
    }


    /**
     * Enable profiling for this session.
     *
     * This can override config settings to enable per-session profiling in
     * production environments. It _does not_ override URL filter rules.
     *
     * @param bool $enabled
     * @return void
     */
    public static function setEnabledSession($enabled)
    {
        Session::instance();
        $_SESSION['force_profiling'] = $enabled;
    }


    /**
     * Is the profiler enabled for this URL?
     *
     * @param string $url
     * @return bool
     */
    public static function isEnabledForUrl(string $url): bool
    {
        $config = self::getConfig();
        $enabled = !empty($config['enabled']);

        // Check the session. This lets us do per-session profiling
        // on production environments.
        Session::instance();
        $profiling = $_SESSION['force_profiling'] ?? null;

        if ($profiling !== null) {
            $enabled = $profiling;
        }

        // The URL filter can override everything.
        if (
            $enabled
            and $config['url_filter']
            and preg_match($config['url_filter'], $url)
        ) {
            $enabled = false;
        }

        return $enabled;
    }


    /**
     * Is the profiler enabled?
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        if (self::$_enabled === null) {
            self::$_enabled = self::isEnabledForUrl(Router::$current_uri);
        }
        return self::$_enabled;
    }


    /**
     * Write the log.
     *
     * Note, this is called automatically on shutdown.
     *
     * @return void
     * @throws Kohana_Exception
     */
    public static function flush()
    {
        if (empty(self::$logs)) return;

        $enabled = self::isEnabled();
        if (!$enabled) return;

        $config = self::getConfig();
        $time = microtime(true);

        $file = @fopen($config['path'], 'a+');
        if (!$file) return;

        try {
            // Write out a json lines format.
            foreach (self::$logs as $log) {
                // One last item.
                $log['request.duration'] = $time - SPROUT_REQUEST_TIME;

                $log = json_encode($log);
                fwrite($file, $log . PHP_EOL);
            }
        }
        finally {
            fclose($file);
        }

        if (filesize($config['path']) > $config['max_size'] * 1.5) {
            self::trim();
        }

        self::$logs = [];
    }


    /**
     * Trim the profiler log to the 'max_size' config.
     *
     * Note, this trims backwards - the new first item will be 100% mangled.
     *
     * @return void
     * @throws Kohana_Exception
     */
    public static function trim()
    {
        $config = self::getConfig();

        try {
            @rename($config['path'], $config['path'] . '.1');

            $read = @fopen($config['path'] . '.1', 'r');
            $write = @fopen($config['path'], 'w');

            if (!$read or !$write) return;

            fseek($read, -1 * $config['max_size'], SEEK_END);
            stream_copy_to_stream($read, $write);

            @unlink($config['path'] . '.1');
        }
        finally {
            if ($read) fclose($read);
            if ($write) fclose($write);
        }
    }


    /**
     * Delete the profiler log.
     *
     * @return void
     * @throws Kohana_Exception
     */
    public static function clear()
    {
        $config = self::getConfig();
        @unlink($config['path']);
    }


    /**
     * Get a limited backtrace.
     *
     * @return array[] [ function, class, type, file, line ]
     */
    protected static function getTrace(): array
    {
        $config = self::getConfig();

        if (empty($config['max_trace'])) {
            return [];
        }

        $_trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        array_pop($_trace);
        array_shift($_trace);
        array_shift($_trace);

        $trace = [];

        foreach ($_trace as $frame) {
            if (count($trace) > $config['max_trace']) break;
            if (!isset($frame['file'])) continue;
            if (!isset($frame['line'])) continue;

            unset($frame['object']);
            unset($frame['args']);

            $trace[] = $frame;
        }

        return $trace;
    }


    /**
     * Begin a profile section.
     *
     * The begin/end pair are joined by a common token + category.
     *
     * @param string $token
     * @param string $category
     * @param array $meta optional extra data
     * @return void
     */
    public static function begin(string $token, string $category, array $meta = [])
    {
        $enabled = self::init();
        if (!$enabled) return;

        $key = sha1(json_encode($token) . ':' . $category);
        $memory = memory_get_usage();
        $trace = self::getTrace();

        self::$active[$key] = [
            'time' => microtime(true),
            'category' => $category,
            'token' => $token,
            'memory' => $memory,
            'trace' => $trace,
            'meta' => $meta,
        ];
    }


    /**
     * Commit a profile section.
     *
     * @param string $token
     * @param string $category
     * @return void
     */
    public static function end(string $token, string $category)
    {
        $enabled = self::init();
        if (!$enabled) return;

        $key = sha1(json_encode($token) . ':' . $category);
        $begin = self::$active[$key] ?? null;

        if ($begin) {
            unset(self::$active[$key]);

            $memory = memory_get_usage();
            $time = microtime(true);

            self::$logs[] = [
                'token' => $begin['token'],
                'category' => $begin['category'],
                'trace' => $begin['trace'],
                'time' => $begin['time'],
                'duration' => $time - $begin['time'],
                'memory' => $memory,
                'memory_delta' => $memory - $begin['memory'],
                'meta' => $begin['meta'],
                'request.tag' => SPROUT_REQUEST_TAG,
                'request.time' => SPROUT_REQUEST_TIME,
                'request.duration' => 0,
                'request.ip' => Request::userIp(),
                'request.agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'request.url' => Router::$current_uri,
                'request.index' => count(self::$logs),
            ];
        }
    }


    /**
     * Load the profiler log.
     *
     * TODO support for loading items in reverse.
     *
     * @return Generator<array>
     * @throws Kohana_Exception
     */
    public static function load()
    {
        $config = self::getConfig();

        $file = @fopen($config['path'], 'r');
        if (!$file) return;

        try {
            while (!feof($file)) {
                $item = fgets($file);
                if ($item === false) continue;

                $id = ftell($file) - strlen($item);

                $item = json_decode($item, true);
                if (!$item) continue;

                $item['id'] = $id;
                yield $id => $item;
            }
        }
        finally {
            fclose($file);
        }
    }


    /**
     * Load a single item from the profiler log.
     *
     * The 'index' is a pointer offset.
     *
     * @param int $index
     * @return array|null
     * @throws Kohana_Exception
     */
    public static function loadItem(int $index)
    {
        $config = self::getConfig();

        $file = @fopen($config['path'], 'r');
        if (!$file) return null;

        try {
            fseek($file, $index);
            if (feof($file)) return null;

            $item = fgets($file);
            if (!$item) return null;

            return json_decode($item, true);
        }
        finally {
            fclose($file);
        }
    }


    /**
     * Displays nice backtrace information.
     *
     * @see http://php.net/debug_backtrace
     *
     * @param array $backtrace generated by an exception or debug_backtrace
     * @return string
     */
    public static function backtrace(array $trace): string
    {
        // Final output
        $output = '';

        foreach ($trace as $entry) {
            $output .= '<li>';

            if (isset($entry['file'])) {
                if (IN_PRODUCTION) {
                    $entry['file'] = preg_replace('!^' . preg_quote(DOCROOT) . '!', '', $entry['file']);
                }

                $output .= '<tt>';
                $output .= Enc::html($entry['file']) . ' ';
                $output .= '<strong>' . Enc::html($entry['line']) . ':</strong>';
                $output .= '</tt>';
            }

            $output .= '<pre>';

            // Add class and call type
            if (isset($entry['class'])) {
                $output .= $entry['class'] . $entry['type'];
            }

            // Add function
            $output .= $entry['function'] . '( ';

            // Add function args
            if (isset($entry['args']) AND is_array($entry['args'])) {
                // Separator starts as nothing
                $sep = '';

                while ($arg = array_shift($entry['args'])) {
                    if (is_string($arg)) {
                        $arg = Enc::cleanfunky($arg);

                        // Remove docroot from filename
                        if (IN_PRODUCTION and is_file($arg)) {
                            $arg = preg_replace('!^'.preg_quote(DOCROOT).'!', '', $arg);
                        }
                    }

                    $output .= $sep . '<span>' . Enc::html(print_r($arg, TRUE)) . '</span>';

                    // Change separator to a comma
                    $sep = ', ';
                }
            }

            $output .= ' )</pre></li>';
            $output .= "\n";
        }

        return $output;
    }
}
