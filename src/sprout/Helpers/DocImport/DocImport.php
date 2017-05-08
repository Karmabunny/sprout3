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

namespace Sprout\Helpers\DocImport;

use DOMDocument;
use Exception;

use Sprout\Helpers\Enc;
use Sprout\Helpers\File;
use Sprout\Helpers\Register;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\Treenode;


/**
* Abstract class with additional stuff for the document importer system
*
* The document importer loads docuemnts and returns XML files
* These files can then be processed by other parts of the CMS.
* The term "xmldoc" is referring to the intermediate XML file produced by the
* import libraries
**/
abstract class DocImport {

    /**
    * The main load function for a document.
    * Throw an exception on error.
    *
    * @param string $filename The file. The file will exist, but may not be valid
    * @return string|DOMDocument $data Resultant XML data as a string or DOMDocument element
    **/
    abstract public function load($filename);


    /**
    * Return a `DocImport` object instance for converting a file with a given original name
    * Throws an exception on error
    **/
    public static function instance($orig_filename)
    {
        $ext = strtolower(File::getExt($orig_filename));
        if (! $ext) {
            throw new Exception('Filename not valid');
        }

        $doc_imports = Register::getDocImports();
        if (! $doc_imports[$ext]) {
            throw new Exception("Unsupported file extension: {$ext}");
        }

        return Sprout::instance($doc_imports[$ext][0]);
    }


    /**
    * For a given XML doc file, return the HTML version.
    *
    * @param string $filename The file to load; this can also be passed as a DOMDocument object
    * @param array $images Mapping of rel => filename for images
    * @param array $headings Mapping of old to new level for headings (e.g. 3 => 2 for H3 -> H2)
    * @return string The HTML, or NULL on error
    **/
    public static function getHtml($filename, $images = array(), $headings = array()) {
        if ($filename instanceof DOMDocument) {
            $xml = @simplexml_import_dom($filename);
        } else {
            $xml = @simplexml_load_string(file_get_contents($filename));
        }

        if (! $xml) return null;

        // Re-map images, or remove if no mapping exists
        $img_tags = $xml->xpath('//img');
        foreach ($img_tags as $tag) {
            if ((string)$tag['width'] == 0) unset($tag['width']);
            if ((string)$tag['height'] == 0) unset($tag['height']);

            if ($tag['error']) {
                $width = (string)$tag['width'];
                $height = (string)$tag['height'];

                if (!$width or !$height) {
                    $width = 300;
                    $height = 50;
                }

                $tag->addAttribute('src', 'http://placehold.it/' . $width . 'x' . $height . '&text=' . Enc::url((string)$tag['error']));
                unset($tag['error']);
                unset($tag['rel']);

            } else {
                if (isset($images[(string)$tag['rel']])) {
                    $tag->addAttribute('src', $images[(string)$tag['rel']]);
                    unset($tag['rel']);
                } else {
                    unset($tag[0]);
                }
            }
        }

        // Get as XML and do some XML -> HTML mods
        $html = $xml->body->asXML();
        $html = str_replace(array('<body>', '</body>', '<body/>'), '', $html);
        $html = str_replace(array('<br/>', '<br />'), '<br>', $html);
        $html = str_replace('/>', '>', $html);

        // Re-map headings, or remove if no mapping exists
        arsort($headings);
        foreach ($headings as $old => $new) {
            $html = preg_replace("!<h{$old}>([^<]+)</h{$old}>!", "<h{$new}>\$1</h{$new}>", $html);
        }

        return $html;
    }


    /**
    * For a given XML doc file, return an array of resources, in name => data format.
    *
    * @param string $filename The file to load
    * @return array The resources
    **/
    public static function getResources($filename)
    {
        $xml = @simplexml_load_string(file_get_contents($filename));
        if (! $xml) return null;

        $res = array();
        foreach ($xml->res as $row) {
            $res[(string)$row['name']] = base64_decode((string)$row);
        }

        return $res;
    }



    /**
    * For a given XML document, return a tree of headings
    *
    * Any heading level past the max_depth option is treated like body text.
    *
    * @param string $filename The XML file to load; this can also be passed as a DOMDocument object
    * @param int $max_depth The maximum depth of headings to return
    * @param bool $include_body True to include the body XML as a parameter on the node. Default false
    * @return Treenode The tree of headings
    **/
    public static function getHeadingsTree($filename, $max_depth, $include_body = false)
    {
        if ($filename instanceof DOMDocument) {
            $dom = $filename;
        } else {
            $dom = new DOMDocument();
            $dom->loadXML(file_get_contents($filename));
        }

        $body = $dom->getElementsByTagName('body');
        $body = $body->item(0);

        $root = new Treenode();
        $curr = array($root);
        $node = $root;

        foreach ($body->childNodes as $elem) {
            if (preg_match('/^[hH][1-6]$/', $elem->tagName) and ($level = (int)($elem->tagName[1])) and $level <= $max_depth) {
                $node = new Treenode();
                $node['name'] = $elem->textContent;
                $node['level'] = $level;
                $node['body'] = '';

                $parent = null;
                for ($i = $level-1; !$parent; $i--) {
                    $parent = @$curr[$i];
                }

                $parent->children[] = $node;
                $node->parent = $parent;

                $curr[$level] = $node;

            } else if ($include_body) {
                $node['body'] .= $dom->saveXML($elem);
            }
        }

        return $root;
    }
}
