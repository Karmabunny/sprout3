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

use DateTimeImmutable;
use karmabunny\kb\Arrays;
use karmabunny\kb\Inflector as KbInflector;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * This is a set of extension functions/filters for Twig templates.
 *
 * A list of core twig filters + function can be found here:
 * - https://twig.symfony.com/doc/3.x/filters/index.html
 * - https://twig.symfony.com/doc/3.x/functions/index.html
 *
 * This extension also provides core global variables for sprout:
 * - IN_PRODUCTION
 * - DOCROOT
 * - sprout {@see SproutVariable}
 * - now
 *
 */
final class SproutExtension
    extends AbstractExtension
    implements GlobalsInterface
{

    /** @inheritdoc */
    public function getGlobals(): array
    {
        return [
            'IN_PRODUCTION' => IN_PRODUCTION,
            'DOCROOT' => DOCROOT,

            'sprout' => new SproutVariable(),
            'now' => new DateTimeImmutable('now'),
        ];
    }


    /** @inheritdoc */
    public function getFilters() {
        return [
            // Built-ins
            new TwigFilter('unique', 'array_unique'),
            new TwigFilter('values', 'array_values'),
            new TwigFilter('intersect', 'array_intersect'),
            new TwigFilter('diff', 'array_diff'),
            new TwigFilter('ucwords', 'ucwords'),
            new TwigFilter('ucfirst', 'ucfirst'),
            new TwigFilter('lcfirst', 'lcfirst'),
            new TwigFilter('json_encode', 'json_encode'),
            new TwigFilter('json_decode', 'json_decode'),
            new TwigFilter('integer', 'intval'),
            new TwigFilter('float', 'floatval'),
            new TwigFilter('string', 'strval'),

            // External
            new TwigFilter('flatten', [Arrays::class, 'flatten']),
            new TwigFilter('normalize', [Arrays::class, 'normalizeOptions']),
            new TwigFilter('query', [Arrays::class, 'value']),

            new TwigFilter('markdown', [Markdown::class, 'parse'], ['is_safe' => ['html']]),
            new TwigFilter('md', [Markdown::class, 'parse'], ['is_safe' => ['html']]),

            // Theses ones are a little more flexible.
            // TODO Should update the core ones eventually.
            new TwigFilter('camel', [KbInflector::class, 'camelize']),
            new TwigFilter('underscore', [KbInflector::class, 'underscore']),
            new TwigFilter('kebab', [KbInflector::class, 'kebab']),
            new TwigFilter('humanize', [KbInflector::class, 'humanize']),

            new TwigFilter('plural', [Inflector::class, 'plural']),
            new TwigFilter('singular', [Inflector::class, 'singular']),

            // Custom
            new TwigFilter('cc2kc', [$this, 'cc2kc']),
            new TwigFilter('kc2cc', [$this, 'kc2cc']),
            new TwigFilter('truncate', [$this, 'truncate']),
            new TwigFilter('jsdate', [$this, 'jsdate']),
            new TwigFilter('json_pretty', [$this, 'jsonPretty']),
            new TwigFilter('push', [$this, 'push'], ['is_variadic' => true]),
            new TwigFilter('unshift', [$this, 'unshift'], ['is_variadic' => true]),
            new TwigFilter('shuffle', [$this, 'shuffle']),
            new TwigFilter('busty', [$this, 'bustyUrl']),
        ];
    }


    /** @inheritdoc */
    public function getFunctions() {
        return [
            // Built-ins
            new TwigFunction('class', 'get_class'),
            new TwigFunction('floor', 'floor'),
            new TwigFunction('ceil', 'ceil'),
            new TwigFunction('combine', 'array_combine'),

            // External
            new TwigFunction('redirect', [Url::class, 'redirect']),
            new TwigFunction('jquery', [Jquery::class, 'script'], [
                'is_safe' => ['html'],
            ]),

            // Custom
            new TwigFunction('attr', [$this, 'attr'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('url', [$this, 'url'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('options', [$this, 'options'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('busty', [$this, 'bustyUrl']),
        ];
    }


    /** @inheritdoc */
    public function getTests()
    {
        return [
            new TwigTest('numeric', 'is_numeric'),
            new TwigTest('string', 'is_string'),
            new TwigTest('bool', 'is_bool'),
            new TwigTest('boolean', 'is_bool'),
            new TwigTest('scalar', 'is_scalar'),
            new TwigTest('array', 'is_array'),
            new TwigTest('object', 'is_object'),
            new TwigTest('int', 'is_int'),
            new TwigTest('integer', 'is_int'),
            new TwigTest('float', 'is_float'),
            new TwigTest('callable', 'is_callable'),
            new TwigTest('countable', 'is_countable'),

            new TwigTest('instance of', function($value, $class) {
                return $value instanceof $class;
            }),
            new TwigTest('instanceof', function($value, $class) {
                return $value instanceof $class;
            }),

            new TwigTest('true', function ($value) {
                return $value === true;
            }),
            new TwigTest('false', function($value) {
                return $value === false;
            }),
        ];
    }

    /**
     * Render hash keys into a string, depending on the truthiness of the value.
     *
     * Example:
     * ```
     * # options({abc: 123, def: '', ghi: 'foo'})
     * > 'abc ghi'
     * ```
     *
     * All values are html escaped.
     *
     * @param array $options
     * @return string
     */
    public function options(array $options)
    {
        $out = [];
        foreach ($options as $key => $value) {
            if (!$value) continue;
            $out[] = Enc::html($key);
        }
        return implode(' ', $out);
    }


    /**
     * Render a hash keys into an attribute string.
     *
     * Special rules apply for these types:
     * - `null|false` are excluded from the output
     * - `true|empty` only includes the key, without the value
     * - numeric values are always included
     *
     * Example:
     * ```
     * # attr({abc: 0, def: '', ghi: 'foo', hjk: null})
     * > 'abc="0" def ghi="foo"'
     * ```
     *
     * All keys and values are html escaped.
     *
     * @param array $config
     * @return string
     */
    public function attr(array $config)
    {
        $out = '';
        foreach ($config as $name => $value) {
            if ($value === null or $value === false) continue;

            $out .= Enc::html($name);

            if (
                $value !== true and
                (is_numeric($value) or !empty($value))
            ) {
                $value = Enc::html($value);
                $out .= "=\"{$value}\"";
            }
            $out .= " \n";
        }
        return trim($out);
    }


    /**
     * Build a URL on the absolute root.
     *
     * Provide 'params' to build a query string.
     *
     * The result is URL safe.
     *
     * @param string|null $path
     * @param array|null $params
     * @return string
     */
    public function url(?string $path = null, ?array $params = null)
    {
        $out = Sprout::absRoot();
        if ($path) {
            $out .= '/' . ltrim(Enc::url($path), '/');
        }
        else {
            $out .= Router::$current_uri;
        }

        if ($params) {
            $out .= '?' . http_build_query($params);
        }

        return $out;
    }


    /**
     * Trim a string if it's too long. Adds a ... ellipsis character.
     *
     * @param null|string $text
     * @param int $length
     * @return string
     */
    public function truncate(?string $text, int $length): string
    {
        if (empty($text)) {
            return '';
        }
        else if (mb_strlen($text) > $length) {
            return mb_substr($text, 0, $length - 1) . '…';
        }
        else {
            return $text;
        }
    }


    /**
     * Shorthand pretty print JSON.
     *
     * @param mixed $data
     * @return string
     */
    public function jsonPretty($data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }


    /**
     * Changes camelCase to kebab-case
     *
     * @param string $var
     * @return string
     */
    public function cc2kc(?string $var)
    {
        if (is_string($var) && strlen($var))
        {
            $var = preg_replace_callback('/(^|[a-z])([A-Z])/', function($matches) {
                return strtolower(strlen("\\1") ? "$matches[1]-$matches[2]" : "\\2");
            },
            $var);
        }

        return $var;
    }


     /**
     * Changes kebab-case to camelCase
     *
     * @param string $var
     * @return string
     */
    public function kc2cc(?string $var)
    {
        if (is_string($var) && strlen($var))
        {
            $var = preg_replace_callback('/(^|[a-z])([-])([a-z])/', function($matches) {
                return strlen("\\1") ? "$matches[1]" . ucfirst("$matches[3]") : "\\3";
            },
            $var);
        }

        return $var;
    }


    /**
     * A wrapper around strtotime. Pretty handy.
     *
     * @param mixed $value
     * @return string
     */
    public function jsdate($value): string
    {
        $date = strtotime($value) * 1000;
        return $date ? "new Date({$date})" : 'null';
    }


    /**
     *
     * @param array $array
     * @param mixed $items
     * @return array
     */
    public function push(array $array, ...$items): array
    {
        array_push($array, ...$items);
        return $array;
    }


    /**
     *
     * @param array $array
     * @param mixed $items
     * @return array
     */
    public function unshift(array $array, ...$items): array
    {
        array_unshift($array, ...$items);
        return $array;
    }


    /**
     *
     * @param array $array
     * @param bool $preserve_keys
     * @return array
     */
    public function shuffle(iterable $array, bool $preserve_keys = false): array
    {
        if (!is_array($array)) {
            $array = iterator_to_array($array, $preserve_keys);
        }

        if ($preserve_keys) {
            $keys = array_keys($array);
            shuffle($keys);

            $new = [];
            foreach ($keys as $key) {
                $new[$key] = $array[$key];
            }

            return $new;
        }
        else {
            shuffle($array);
            return $array;
        }
    }


    /**
     * Create a URL with a timestamp for cache busting purposes.
     *
     * @param string $url The URL as provided by the Twig template.
     * @return string
     */
    public function bustyUrl($url): string
    {
        $normalized_docroot_path = rtrim(DOCROOT, DIRECTORY_SEPARATOR);
        $normalized_url = trim(Needs::replacePathsString($url), DIRECTORY_SEPARATOR);

        $url_query_separator_pos = strrpos($normalized_url, '?');

        if ($url_query_separator_pos !== false) {
            $normalized_url = substr($normalized_url, 0, $url_query_separator_pos);
        }

        $file = $normalized_docroot_path . DIRECTORY_SEPARATOR . $normalized_url;

        if ($file) {
            $mtime = @filemtime($file) ?: null;
        }

        return Url::withParams($url, ['_v' => $mtime ?? 0 ]);
    }

}
