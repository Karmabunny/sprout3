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

use \DOMNode;
use \DOMDocument;

/**
 * Helper that strictly validates and sanitises user submitted HTML
 * Intended for use with front-end instances of TinyMCE to ensure XSS is impossible.
 *
 * @note Operates in a whitelist mode; if a tag or attribute doesn't appear on the list it won't appear on the output.
 */
final class RichTextSanitiser
{
    const ATTR_TYPE_TEXT = 0;
    const ATTR_TYPE_URL = 1;
    const ATTR_TYPE_CLASS = 2;
    const ATTR_TYPE_SRC = 3;
    const ATTR_TYPE_STYLE = 4;

    private $dom_doc;
    private $errors = [];
    private $permitted_tags;

    /**
     * @var bool Permit only local (i.e. on the current server) resources in src attributes
     */
    private $local_resources = false;

    /**
     * The default set of tags (and attributes) that are permitted
     */
    public static $default_permitted_tags = [
        'div' => [
            'class' => self::ATTR_TYPE_CLASS,
        ],
        'p' => [
            'class' => self::ATTR_TYPE_CLASS,
            'style' => self::ATTR_TYPE_STYLE
        ],

        'span' => [
            'class' => self::ATTR_TYPE_CLASS,
        ],
        'strong' => null,
        'em' => null,

        'h1' => [
            'class' => self::ATTR_TYPE_CLASS,
        ],
        'h2' => [
            'class' => self::ATTR_TYPE_CLASS,
        ],
        'h3' => [
            'class' => self::ATTR_TYPE_CLASS,
        ],
        'h4' => [
            'class' => self::ATTR_TYPE_CLASS,
        ],
        'h5' => [
            'class' => self::ATTR_TYPE_CLASS,
        ],
        'h6' => [
            'class' => self::ATTR_TYPE_CLASS,
        ],
        'h7' => [
            'class' => self::ATTR_TYPE_CLASS,
        ],

        'img' => [
            'src' => self::ATTR_TYPE_SRC,
            'alt' => self::ATTR_TYPE_TEXT
        ],
        'a' => [
            'class' => self::ATTR_TYPE_CLASS,
            'href' => self::ATTR_TYPE_URL,
            'title' => self::ATTR_TYPE_TEXT
        ],

        'ul' => [
            'class' => self::ATTR_TYPE_CLASS
        ],
        'ol' => [
            'class' => self::ATTR_TYPE_CLASS
        ],

        'li' => [
            'class' => self::ATTR_TYPE_CLASS
        ],
    ];

    /**
     * Construct the sanitiser given a string of HTML
     *
     * @param string $richtextData The HTML to sanitise
     * @param array $permitted_tags An optional array of permitted tags to override the defaults
     */
    public function __construct($richtextData, $permitted_tags = null)
    {
        // PHP-8+ deprecated this because it's disabled by default.
        if (PHP_VERSION_ID < 80000) {
            libxml_disable_entity_loader();
        }

        $this->dom_doc = new DOMDocument();
        if (!@$this->dom_doc->loadHTML($richtextData, LIBXML_NOCDATA)) {
            $this->errors[] = 'There were errors in parsing the given HTML.';
        }

        if ($permitted_tags) {
            $this->permitted_tags = $permitted_tags;
        } else {
            $this->permitted_tags = static::$default_permitted_tags;
        }
    }


    /**
     * Gets a sanitised copy of the HTML
     *
     * @return string HTML with any elements or attributes not appearing on the whitelist removed
     */
    public function sanitise()
    {
        ob_start();

        $this->sanitiseNode($this->dom_doc);

        return trim(ob_get_clean());
    }


    /**
     * Checks whether any errors occurred during sanitising
     *
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }


    /**
     * Get the list of errors produced during @see RichTextSanitiser::sanitise
     *
     * @return array An array of error messages. Obviously, you must HTML encode each entry for them to be safe
     *               for web use as they may contain values from the DOM.
     */
    public function getErrors()
    {
        return $this->errors;
    }


    /**
     * Set whether to only allow local resources to be referenced by automatically fetching attributes
     * e.g. the src attribute on an <img> tag. Does not apply to the href attribute as that won't be
     * automatically fetched by a browser.
     *
     * @param bool $local True if only local resources are allowed, false if any resource is permitted
     * @return void
     */
    public function setLocalResources($local)
    {
        $this->local_resources = (bool)$local;
    }


    /**
     * Recursively sanitises a node and its children, echoing any output in order of appearance
     *
     * @param DOMNode $node The node to sanitise
     */
    private function sanitiseNode(DOMNode $node)
    {
        switch ($node->nodeType) {
        case XML_ELEMENT_NODE:
        {
            // These get thrown in for free by DOMDocument::loadXML (thanks guys)
            // so just omit them and parse their children instead
            if ($node->nodeName === 'html' or $node->nodeName === 'body') {
                foreach ($node->childNodes as $child) {
                    $this->sanitiseNode($child);
                }

                return;
            }

            if (!array_key_exists($node->nodeName, $this->permitted_tags)) {
                $this->errors[] = "Disallowed tag '{$node->nodeName}'";

                return;
            }

            $attributes = [];
            if ($node->hasAttributes()) {
                $allowed_attrs = $this->permitted_tags[$node->nodeName] ?? null;

                if (!is_array($allowed_attrs)) {
                    $this->errors[] = "'{$node->nodeName}' elements are not permitted to contain any attributes";

                } else {
                    foreach ($node->attributes as $attr) {
                        if (!array_key_exists($attr->name, $allowed_attrs)) {
                            $this->errors[] = "Invalid attribute '{$attr->name}' for '{$node->nodeName}' element.";
                            continue;
                        }

                        $encoded = $this->encodeAttributeValue($allowed_attrs[$attr->name], $attr->value);
                        if (!empty($encoded)) {
                            $attributes[] = sprintf('%s="%s"', $attr->name, $encoded);
                        }
                    }
                }
            }


            echo "<{$node->nodeName}";

            if (count($attributes)) {
                echo ' ', implode(' ', $attributes);
            }

            if ($node->hasChildNodes()) {
                echo '>';

                foreach ($node->childNodes as $child) {
                    $this->sanitiseNode($child);
                }

                echo "</{$node->nodeName}>";
            } else {
                echo ' />';
            }

            break;
        }

        case XML_TEXT_NODE:
        {
            echo htmlspecialchars($node->nodeValue, ENT_COMPAT | ENT_HTML401 | ENT_DISALLOWED, 'UTF-8', false);
            break;
        }

        case XML_HTML_DOCUMENT_NODE:
        case XML_DOCUMENT_NODE:
        {
            foreach ($node->childNodes as $child) {
                $this->sanitiseNode($child);
            }

            break;
        }

        default:
            break;
        }
    }

    /**
     * Encodes the value of an attribute using the correct encoding based on type
     *
     * @param int $type The attribute type code, e.g. ATTR_TYPE_URL
     * @param string $value The attribute value as seen in the DOM
     * @return string|bool A safely encoded attribute value or false if the value fails the checks
     */
    private function encodeAttributeValue($type, $value)
    {
        $value = trim($value);

        switch ($type) {
        case self::ATTR_TYPE_URL:
        {
            $url = parse_url($value);

            if ($url == false or strcasecmp($url['scheme'], 'javascript') == 0) {
                return false;
            }

            break;
        }

        case self::ATTR_TYPE_SRC:
        {
            if ($this->local_resources) {
                $url = parse_url($value);

                if ($url == false) {
                    return false;
                }

                // See if the domain specified actually matches the server's primary domain
                if (!empty($url['host'])) {
                    // Unfortunately HTTP_HOST is the best option that isn't stupidly complicated or
                    // prone to breakage, e.g. Url::base().

                    // TODO: this needs to handle subsites and all that mess
                    $base_domain = $_SERVER['HTTP_HOST'];
                    if (strcasecmp($url['host'], $base_domain) !== 0) {
                        return false;
                    }
                }
            }

            break;
        }

        case self::ATTR_TYPE_STYLE:
        {
            if ($this->local_resources) {
                // TODO: style attributes are tricky; I can force a browser to fetch a remote resource with them
                //       so we either disallow (breaks TinyMCE styling) or parse and whitelist some CSS attributes

                return false;
            }

            break;
        }

        default:
            break;
        }

        return htmlspecialchars($value, ENT_COMPAT | ENT_HTML401 | ENT_DISALLOWED, 'UTF-8', false);
    }
}
