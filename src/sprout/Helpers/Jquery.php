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

use Kohana;


/**
 * Simple class to ensure uniform jQuery version(s) used
 */
class Jquery {

    /**
     * Gets a <script> tag to include jQuery or jQuery UI
     * @param string $lib Library to include: 'jquery' or 'jqueryui'
     * @param string $loc Location: 'front' or 'admin'
     * @param string $min Specify 'min' to get a minified version
     */
    public static function script($lib, $loc, $min = 'min')
    {
        if (!in_array($loc, ['front', 'admin'])) {
            throw new \Exception('Invalid $loc');
        }

        $lib = strtolower($lib);
        if (!in_array($lib, ['jquery', 'jqueryui'])) {
            throw new \Exception('Invalid $lib');
        }

        $version = Kohana::config("sprout.{$lib}_{$loc}");

        $file = preg_replace('/ui$/', '-ui', $lib);
        $ext = ($min == 'min' ? '.min.js' : '.js');

        $script = '<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/';
        $script .= $lib . '/' . $version . '/' . $file . $ext . '"></script>';

        return $script;
    }

}
