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

use cebe\markdown\GithubMarkdown;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Traversable;


class ProjectDocs
{

    const DOCS_PATH = BASE_PATH . 'docs/';


    /**
     *
     * @return string[]
     */
    public static function listDocs(): array
    {

        if (!is_dir(self::DOCS_PATH)) {
            return [];
        }

        /** @var Traversable<SplFileInfo> $iterator */
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::DOCS_PATH, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $paths = [];

        foreach ($iterator as $item) {
            $path = $item->getRealPath();
            if ($item->isDir()) continue;
            if ($item->getExtension() != 'md') continue;
            if ($item->getBasename() == 'DEPLOY.md') continue;

            $path = strtr($path, [
                self::DOCS_PATH => '',
                '.md' => ''
            ]);

            $paths[] = $path;
        }

        return $paths;
    }



    /**
     * Render a markdown document.
     *
     * @param mixed $path
     * @return string|null
     * @throws Exception
     */
    public static function renderDoc(string $path): ?string
    {
        $markdown = new GithubMarkdown();
        $markdown->html5 = true;

        if (strpos($path, '..') !== false) {
            return null;
        }

        $doc = @file_get_contents(self::DOCS_PATH . $path . '.md');

        if (!$doc) {
            return null;
        }

        $html = $markdown->parse($doc);
        return $html;
    }


    /**
     *
     * @param string $html
     * @return string
     */
    public static function parseTitle(string $html): string
    {
        $title = 'Untitled';

        if (preg_match('/<h[123]>(.*?)<\/h[123]>/', $html, $matches)) {
            $title = $matches[1];
        }

        return $title;
    }

}