<?php
/*
 * Copyright (C) 2021 Karmabunny Pty Ltd.
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

use Kohana_Exception;
use Exception;
use Sprout\Exceptions\FileMissingException;
use Twig\Error\LoaderError;
use Twig\Loader\LoaderInterface;
use Twig\Source;

/**
 * File loader for twig templates.
 *
 * This obeys the same path rules as PHP views:
 *  - using the unavailable skin
 *  - using module/ sprout/ skin/ prefixes
 *
 * TODO Some actual caching.
 */
class TwigSkinLoader implements LoaderInterface
{

    /**
     * File path cache.
     *
     * @var string[] [ name => path ]
     */
    protected $cache = [];


    /**
     * Template cache.
     *
     * @var string[] [ name => template]
     */
    protected $templates = [];


    /**
     * Find a template for the current subsite skin.
     *
     * @param mixed $name
     * @return string
     * @throws Kohana_Exception
     * @throws Exception
     * @throws FileMissingException
     */
    protected function findTemplate(string $name)
    {
        if ($path = $this->cache[$name] ?? null) {
            return $path;
        }

        $path = Skin::findTemplate($name, '.twig');
        $this->cache[$name] = $path;
        return $path;
    }


    /** @inheritdoc */
    public function getSourceContext(string $name): Source
    {
        try {
            $path = DOCROOT . $this->findTemplate($name);
            $template = file_get_contents($path);
            return new Source($template, $name);
        }
        catch (Exception $exception) {
            throw new LoaderError($exception->getMessage(), -1, null, $exception);
        }
    }


    /** @inheritdoc */
    public function getCacheKey(string $name): string
    {
        return $name;
    }


    /** @inheritdoc */
    public function isFresh(string $name, int $time): bool
    {
        $this->findTemplate($name);
        return true;
    }


    /** @inheritdoc */
    public function exists(string $name)
    {
        try {
            $this->findTemplate($name);
            return true;
        }
        catch (FileMissingException $e) {
            return false;
        }
    }
}
