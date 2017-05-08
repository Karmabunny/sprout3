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

namespace Sprout\Controllers;

use Kohana;

use Sprout\Helpers\AdminAuth;


/**
* - No description yet -
**/
class MediaMushController extends Controller
{

    /**
    * Does all of the mushing of media
    **/
    public function mush($skin = 'default')
    {
        Kohana::closeBuffers();

        AdminAuth::checkLogin();
        header('Content-type: text/plain');

        $timestamp = time();

        // CSS
        echo "Mushing CSS\n";
        flush();
        $css = file_get_contents(DOCROOT . "skin/{$skin}/cache/css_files.txt");
        $css = explode("\n", $css);
        $css = $this->mushFiles($css);
        $res = @file_put_contents(DOCROOT . "skin/{$skin}/cache/site_{$timestamp}.css", trim($css));
        if (! $res) {
            echo "  Unable to save file\n";
        }

        // JS
        echo "\nMushing JavaScript\n";
        flush();
        $js = file_get_contents(DOCROOT . "skin/{$skin}/cache/js_files.txt");
        $js = explode("\n", $js);
        $js = $this->mushFiles($js);
        $res = @file_put_contents(DOCROOT . "skin/{$skin}/cache/site_{$timestamp}.js", trim($js));
        if (! $res) {
            echo "  Unable to save file\n";
        }

        // PHP
        echo "\nGenerating mush reference script\n";
        flush();
        $php  = "<link href=\"SKIN/cache/site_{$timestamp}.css\" rel=\"stylesheet\" type=\"text/css\">\n";
        $php .= "<script src=\"SKIN/cache/site_{$timestamp}.js\" type=\"text/javascript\"></script>";
        $res = @file_put_contents(DOCROOT . "skin/{$skin}/cache/mush.php", $php);
        if (! $res) {
            echo "  Unable to save file\n";
        }
    }


    /**
    * Glues a bunch of files together
    **/
    private function mushFiles(array $files)
    {
        $out = '';

        foreach ($files as $filename) {
            $filename = trim($filename);
            if ($filename == '') continue;

            $content = file_get_contents(DOCROOT . $filename);

            if (preg_match('/\.css$/', $filename)) {
                $content = $this->preprocessCss($content);

            } else if (preg_match('/\.min\.js$/', $filename)) {
                $content = $this->preprocessJs($content);

            } else if (preg_match('/\.js$/', $filename)) {
                $content = $this->preprocessJsMinify($content);
            }

            $out .= "\n/* {$filename} */\n";
            $out .= $content;

            echo "  {$filename}\n";
            flush();
        }

        return $out;
    }


    /**
    * CSS minify
    **/
    private function preprocessCss($content)
    {
        $content = preg_replace('!/\*(.*?)\*/!sm', '', $content);    // nuke multi-line comment
        $content = preg_replace('!\s+!', ' ', $content);            // nuke multiple spaces
        $content = trim($content);

        return $content;
    }


    /**
    * JS minify (scripts not yet minified)
    **/
    private function preprocessJsMinify($content)
    {
        if (! IN_PRODUCTION) return $content;

        $ch = curl_init('http://closure-compiler.appspot.com/compile');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'output_info=compiled_code&output_format=text&compilation_level=WHITESPACE_ONLY&js_code=' . urlencode($content));
        $content = curl_exec($ch);
        curl_close($ch);

        return $content;
    }


    /**
    * JS minify (minor mods)
    **/
    private function preprocessJs($content)
    {
        $content = preg_replace('!\n\n!', "\n", $content);
        $content = trim($content);

        return $content;
    }


}


