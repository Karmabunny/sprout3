<?php
/**
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

use cebe\markdown\GithubMarkdown;
use cebe\markdown\Markdown;
use cebe\markdown\MarkdownExtra;


$config['flavors'] = [
    'original' => Markdown::class,
    'gfm' => GithubMarkdown::class,
    'extra' => MarkdownExtra::class,
];

$config['options'] = [
    'html5' => true,
    'flavor' => 'original',
];
