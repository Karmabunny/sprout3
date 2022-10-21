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

use Sprout\Helpers\Tags;


class tagsTest extends PHPUnit_Framework_TestCase
{

    public function dataSplitupTags()
    {
        return [
            ['', []],
            ['hey', ['hey']],
            ['HEY', ['hey']],
            ['!!hey!!', ['hey']],
            ['hi,bye', ['hi', 'bye']],
            [' hi,bye', ['hi', 'bye']],
            ['hi, bye', ['hi', 'bye']],
            ['hi ,bye', ['hi', 'bye']],
            ['hi,bye ', ['hi', 'bye']],
            ['hi,bye, ', ['hi', 'bye']],
            [' , hi , bye , ', ['hi', 'bye']],
            ['hi,hi', ['hi']],
            ['hi,,bye', ['hi', 'bye']],
        ];
    }

    /**
     * @dataProvider dataSplitupTags
     */
    public function testSplitupTags($string, $expect)
    {
        $this->assertEquals($expect, Tags::splitupTags($string));
    }

}
