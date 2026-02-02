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
 *
 * This class was originally from Kohana 2.3.4
 * Copyright 2007-2008 Kohana Team
 */
namespace Sprout\Helpers;


/**
 * Helper functions for outputting HTML elements.
 */
class Html
{

    // Enable or disable automatic setting of target="_blank"
    public static $windowed_urls = FALSE;


    /**
     * Convert a PHP class name to something that can be used in URLs
     * or HTML id/class.
     *
     * Example: SproutModules\\Controllers\\BlogPostController --> 'blog-post-controller'
     *
     * @param string|object $class
     * @return string
     */
    public static function className($class): string
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $class = Sprout::removeNs($class);
        $class = Text::camel2lc($class);
        $class = str_replace('_', '-', $class);
        return $class;
    }


    /**
     * Create HTML link anchors.
     *
     * @param   string $uri URL or URI string
     * @param   string|null $title Link text
     * @param   array $attributes HTML anchor attributes
     * @param   string|null $protocol Non-default protocol, eg: https
     * @param   bool $escape_title Option to escape the title that is output
     * @return  string
     */
    public static function anchor($uri, $title = NULL, $attributes = [], $protocol = NULL, $escape_title = TRUE)
    {
        if ($uri === '')
        {
            $site_url = Url::base(FALSE);
        }
        elseif (strpos($uri, '#') === 0)
        {
            // This is an id target link, not a URL
            $site_url = $uri;
        }
        elseif (strpos($uri, '://') === FALSE)
        {
            $site_url = Url::site($uri, $protocol);
        }
        else
        {
            if (Html::$windowed_urls === TRUE AND empty($attributes['target']))
            {
                $attributes['target'] = '_blank';
            }

            $site_url = $uri;
        }

        return
        // Parsed URL
        '<a href="'.Enc::html($site_url).'"'
        // Attributes empty? Use an empty string
        .(is_array($attributes) ? Html::attributes($attributes) : '').'>'
        // Title empty? Use the parsed URL
        .($escape_title ? Enc::html((($title === NULL) ? $site_url : $title), FALSE) : (($title === NULL) ? $site_url : $title)).'</a>';
    }

    /**
     * Generates an obfuscated version of an email address.
     *
     * @param   string $email Email address
     * @return  string
     */
    public static function email($email)
    {
        $safe = '';
        foreach (str_split($email) as $letter)
        {
            switch (($letter === '@') ? mt_rand(1, 2) : mt_rand(1, 3))
            {
                // HTML entity code
                case 1: $safe .= '&#'.ord($letter).';'; break;
                // Hex character code
                case 2: $safe .= '&#x'.dechex(ord($letter)).';'; break;
                // Raw (no) encoding
                case 3: $safe .= $letter;
            }
        }

        return $safe;
    }

    /**
     * Creates an email anchor.
     *
     * @param   string $email Email address to send to
     * @param   string|null $title Link text
     * @param   array|null $attributes HTML anchor attributes
     * @return  string
     */
    public static function mailto($email, $title = NULL, $attributes = NULL)
    {
        if (empty($email))
            return $title;

        // Remove the subject or other parameters that do not need to be encoded
        if (strpos($email, '?') !== FALSE)
        {
            // Extract the parameters from the email address
            list ($email, $params) = explode('?', $email, 2);

            // Make the params into a query string, replacing spaces
            $params = '?'.str_replace(' ', '%20', $params);
        }
        else
        {
            // No parameters
            $params = '';
        }

        // Obfuscate email address
        $safe = Html::email($email);

        // Title defaults to the encoded email address
        empty($title) and $title = $safe;

        // Parse attributes
        empty($attributes) or $attributes = Html::attributes($attributes);

        // Encoded start of the href="" is a static encoded version of 'mailto:'
        return '<a href="&#109;&#097;&#105;&#108;&#116;&#111;&#058;'.$safe.$params.'"'.$attributes.'>'.$title.'</a>';
    }

    /**
     * Creates a link tag.
     *
     * @param   string|array $href Filename
     * @param   string|array $rel Relationship
     * @param   string|array $type Mimetype
     * @param   string|false $suffix Specifies suffix of the file
     * @param   string|array|false $media Specifies on what device the document will be displayed
     * @param   bool $index Include the index_page in the link
     * @return  string
     */
    public static function link($href, $rel, $type, $suffix = FALSE, $media = FALSE, $index = FALSE)
    {
        $compiled = '';

        if (is_array($href))
        {
            foreach ($href as $_href)
            {
                $_rel   = is_array($rel) ? array_shift($rel) : $rel;
                $_type  = is_array($type) ? array_shift($type) : $type;
                $_media = is_array($media) ? array_shift($media) : $media;

                $compiled .= Html::link($_href, $_rel, $_type, $suffix, $_media, $index);
            }
        }
        else
        {
            if (strpos($href, '://') === FALSE)
            {
                // Make the URL absolute
                $href = Url::base($index).$href;
            }

            $length = strlen($suffix);

            if ( $length > 0 AND substr_compare($href, $suffix, -$length, $length, FALSE) !== 0)
            {
                // Add the defined suffix
                $href .= $suffix;
            }

            $attr = array
            (
                'rel' => $rel,
                'type' => $type,
                'href' => $href,
            );

            if ( ! empty($media))
            {
                // Add the media type to the attributes
                $attr['media'] = $media;
            }

            $compiled = '<link'.Html::attributes($attr).' />';
        }

        return $compiled."\n";
    }

    /**
     * Creates a script link.
     *
     * @param   string|array $script Filename
     * @param   bool $index Include the index_page in the link
     * @return  string
     */
    public static function script($script, $index = FALSE)
    {
        $compiled = '';

        if (is_array($script))
        {
            foreach ($script as $name)
            {
                $compiled .= Html::script($name, $index);
            }
        }
        else
        {
            if (strpos($script, '://') === FALSE)
            {
                // Add the suffix only when it's not already present
                $script = Url::base((bool) $index).$script;
            }

            if (substr_compare($script, '.js', -3, 3, FALSE) !== 0)
            {
                // Add the javascript suffix
                $script .= '.js';
            }

            $compiled = '<script type="text/javascript" src="'.$script.'"></script>';
        }

        return $compiled."\n";
    }

    /**
     * Creates a image link.
     *
     * @param   string|array|null $src Image source, or an array of attributes
     * @param   string|array|null $alt Image alt attribute, or an array of attributes
     * @param   bool $index Include the index_page in the link
     * @return  string
     */
    public static function image($src = NULL, $alt = NULL, $index = FALSE)
    {
        // Create attribute list
        $attributes = is_array($src) ? $src : array('src' => $src);

        if (is_array($alt))
        {
            $attributes += $alt;
        }
        elseif ( ! empty($alt))
        {
            // Add alt to attributes
            $attributes['alt'] = $alt;
        }

        if (strpos($attributes['src'], '://') === FALSE)
        {
            // Make the src attribute into an absolute URL
            $attributes['src'] = Url::base($index).$attributes['src'];
        }

        return '<img'.Html::attributes($attributes).' />';
    }

    /**
     * Compiles an array of HTML attributes into an attribute string.
     *
     * @param   string|array $attrs Array of attributes
     * @return  string
     */
    public static function attributes($attrs)
    {
        if (empty($attrs))
            return '';

        if (is_string($attrs))
            return ' '.$attrs;

        $compiled = '';
        foreach ($attrs as $key => $val)
        {
            $compiled .= ' '.Enc::html($key).'="'.Enc::html($val).'"';
        }

        return $compiled;
    }


    /**
     * Wrap a field name in a extra name.
     *
     * Basic (non-merge) examples:
     *  - `ns + name => ns[name]`
     *  - `ns + name[nest] => ns[name][nest]`
     *  - `ns[foo] + name[nest] => ns[foo][name][nest]`
     *  - `ns + ns => ns[ns]`
     *  - `ns + ns[name] => ns[ns][name]`
     *
     * Merging examples:
     *  - `ns + ns[name] => ns[name]` (prefix merge)
     *  - `ns[name] + ns[name] => ns[name]` (duplicate merge)
     *  - `ns[name] + ns[name][nest] => ns[name][nest]` (nested merge)
     *  - `ns[name] + foo[nest] => foo[name][nest]` (complex merge)
     *
     * @param string $namespace
     * @param string $name
     * @param bool $merge
     * @param bool $merge Merge the namespace with existing names
     *   - true: don't insert where the the name exists
     *   - false: always insert the namespace
     * @return string
     */
    public static function namespaceName(string $namespace, string $name, $merge = true): string
    {
        $parts = explode('[', $name);

        foreach ($parts as &$part) {
            $part = rtrim($part, ']');
            $part = "[{$part}]";
        }
        unset($part);

        if ($merge) {
            $name_parts = explode('[', $namespace);

            foreach ($name_parts as $index => $part) {
                $part = rtrim($part, ']');
                $part = "[{$part}]";

                if (($parts[$index] ?? false) === $part) {
                    unset($parts[$index]);
                }
            }
        }

        return $namespace . implode('', $parts);
    }


    /**
     * Wrap all element 'names' in a namespace.
     *
     * Anything that matches `<tag name=''>`.
     *
     * @param string $namespace
     * @param string $html
     * @param bool $merge Merge the namespace with existing names
     *   - true: don't insert where the the name exists
     *   - false: always insert the namespace (default)
     * @return string
     */
    public static function namespace(string $namespace, string $html, $merge = false): string
    {
        return preg_replace_callback(
            '#(<[^>]+\s*name\s*=\s*)([\'"])(.+?)(\2|>)#',
            function($match) use ($namespace, $merge) {
                [, $input, $quote, $name] = $match;

                $name = self::namespaceName($namespace, $name, $merge);
                return "{$input}{$quote}{$name}{$quote}";
            },
            $html
        );
    }

} // End html
