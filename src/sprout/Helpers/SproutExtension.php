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
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
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
            new TwigFilter('cc2kc', [$this, 'cc2kc']),
            new TwigFilter('kc2cc', [$this, 'kc2cc']),
            new TwigFilter('truncate', [$this, 'truncate']),
            new TwigFilter('jsdate', [$this, 'jsdate']),
            new TwigFilter('json_pretty', [$this, 'jsonPretty']),
            new TwigFilter('json_encode', 'json_encode'),
        ];
    }


    /** @inheritdoc */
    public function getFunctions() {
        return [
            new TwigFunction('redirect', [Web::class, 'redirect']),
            new TwigFunction('attr', [$this, 'attr'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('url', [$this, 'url'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('options', [$this, 'options'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('jquery', [Jquery::class, 'script'], [
                'is_safe' => ['html'],
            ]),
        ];
    }


    /**
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
     *
     * @param string|null $path
     * @param array|null $params
     * @return string
     */
    public function url(string $path = null, array $params = null)
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
}
