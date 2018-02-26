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

use Exception;

use Kohana;


/**
* Interface the display of a richtext field
**/
class TinyMCE4RichText extends RichText
{
    /**
     * Loads the required TinyMCE JS library
     * @return void
     */
    public static function needs()
    {
        // Use non-minified, local TinyMCE in dev environment if possible
        if (!IN_PRODUCTION and file_exists(DOCROOT . '/media/tinymce4/tinymce.js')) {
            Needs::addJavascriptInclude('ROOT/media/tinymce4/tinymce.js');
        } else {
            Needs::addJavascriptInclude('//cdnjs.cloudflare.com/ajax/libs/tinymce/4.6.3/tinymce.min.js');
        }
        Needs::module('tinymce4');
    }

    /**
    * Shows a richtext field. Should output content directly
    *
    * @param string $field_name The field name
    * @param string $content The content of the richtext field, in HTML
    * @param int $width The width of the field, in pixels
    * @param int $height The height of the field, in pixels
    * @param string $config_group Specific configuration to use instead of the default
    **/
    protected function drawInternal($field_name, $content, $width = 600, $height = 300, $config_group = null)
    {
        self::needs();

        $subsite = SubsiteSelector::$subsite_id;

        $field_name_html = Enc::html($field_name);
        $field_name_class = 'mce4-' . Enc::id($field_name);

        // Set up options
        $options = array();
        $options['selector'] = '.' . $field_name_class;
        $options['height'] = $height - 110;        // Toolbars and status aren't included in 'height' for some reason
        $options['resize'] = true;
        $options['plugins'] = 'anchor code fullscreen image link paste searchreplace table lists visualblocks fullscreen contextmenu stylebuttons media';
        $options['menubar'] = false;
        $options['relative_urls'] = true;
        $options['branding'] = false;
        $options['external_plugins'] = [
            'sprout_gallery' => Subsites::getAbsRootAdmin() . 'media/js/sprout_gallery.js',
        ];

        if (Router::$controller == 'admin') {
            $subsite = @$_SESSION['admin']['active_subsite'];
            $options['document_base_url'] = rtrim(Subsites::getAbsRootAdmin(), '/') . '/';
        } else {
            $options['document_base_url'] = rtrim(Subsites::getAbsRoot($subsite), '/') . '/';
        }

        //$options['paste_word_valid_elements'] = 'b,strong,i,em,h1,h2,h3,h4';
        $options['paste_webkit_styles'] = 'none';
        $options['paste_retain_style_properties'] = 'none';
        $options['object_resizing'] = 'img';
        $options['element_format'] = 'html';
        $options['object_resizing'] = 'img';
        $options['media_live_embeds'] = true;

        // Require "class" for SPAN elements, there isn't any point of a span otherwise
        // This also fixes a webkit bug in lists
        $options['extended_valid_elements'] = 'span[!class]';

        // Image float fun
        $options['formats'] = array(
            'alignleft' => array(array('selector' => 'img', 'collapsed' => false, 'classes' => 'left')),
            'alignright' => array(array('selector' => 'img', 'collapsed' => false, 'classes' => 'right')),
        );

        // If a config group isn't specified, use one from the config
        if ($config_group === null) {
            $config_group = Kohana::config('tinymce4.default_group', false, false);
        }

        // If still no config found (e.g. no config file), fall-back to a default
        if ($config_group === null) {
            $options['toolbar'] = array(
                'bold italic strikethrough subscript superscript link unlink anchor | removeformat | code fullscreen',
                'styleselect | style-h2 style-h3 style-h4 style-p | bullist numlist indent outdent | alignleft alignright | image media table'
            );
        } else {
            $config_options = Kohana::config('tinymce4.' . $config_group, false, false);
            if ($config_options === null) {
                throw new Exception('Invalid config group "' . $config_group . '"');
            } else {
                $options += $config_options;
            }
        }

        // Formats dropdown menu
        if (empty($options['style_formats'])) {
            $options['style_formats'] = array(
                array('title' => 'Headings', 'items' => array(
                    array('title' => 'Heading 2', 'format' => 'h2'),
                    array('title' => 'Heading 3', 'format' => 'h3'),
                    array('title' => 'Heading 4', 'format' => 'h4'),
                )),
                array('title' => 'Block', 'items' => array(
                    array('title' => 'Paragraph', 'format' => 'p'),
                    array('title' => 'Blockquote', 'format' => 'blockquote'),
                )),
                array('title' => 'Inline', 'items' => array(
                    array('title' => 'Bold', 'format' => 'bold'),
                    array('title' => 'Italic', 'format' => 'italic'),
                )),
                array('title' => 'Wrappers', 'items' => array(
                    array('title' => 'Expando', 'block' => 'div', 'classes' => 'expando', 'wrapper' => true),
                    array('title' => 'Highlight', 'block' => 'div', 'classes' => 'highlight', 'wrapper' => true),
                    array('title' => 'Highlight to the right', 'block' => 'div', 'classes' => 'highlight--right', 'wrapper' => true),
                    array('title' => 'Highlight to the left', 'block' => 'div', 'classes' => 'highlight--left', 'wrapper' => true),
                )),
            );
        }

        // CSS file: richtext.css, content.css
        $options['content_css'] = array();
        $options['content_css'][] = Sprout::absRoot() . 'media/css/richtext.css?ts=' . time();
        if (file_exists(DOCROOT . 'skin/' . Subsites::getCode($subsite) . '/css/richtext.css')) {
            $options['content_css'][] = Sprout::absRoot() . 'skin/' . Subsites::getCode($subsite) . '/css/richtext.css?ts=' . time();
        } else {
            $options['content_css'][] = Sprout::absRoot() . 'skin/' . Subsites::getCode($subsite) . '/css/content.css?ts=' . time();
        }

        // Javascript which makes the field work
        $out = '<script type="text/javascript">TinyMCE4.init(' . json_encode($options) . ');</script>';

        // Include the field itself
        $out .= "<div>";
        $out .= "<textarea name=\"{$field_name_html}\" style=\"height: {$height}px;\"";
        $out .= " class=\"richtext-editor tinymce4-editor {$field_name_class}\" data-field-name=\"{$field_name_html}\">";
        $out .= Enc::html($content);
        $out .= "</textarea>\n";
        $out .= "</div>\n";

        return $out;
    }

}


