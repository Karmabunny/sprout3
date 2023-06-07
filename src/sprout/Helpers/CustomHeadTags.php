<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
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
 * Management of custom HEAD tags. Often meta but can also be link, base, etc
 */
class CustomHeadTags
{

    /**
     * A list of possible tag combinations in the format of tag_type => [tags] => [values]
     *
     * NOTE: This can be customised by overriding the same setup in Kohana::config('sprout.custom_meta.available_tag_list')
     *
     * @var array
     */
    protected static $available_tag_list = [
        'meta' => [
            'name' => [
                'application-name' => [
                    'content' => 'text',
                ],
                'author' => [
                    'content',
                ],
                // 'description' => [
                //     'content',
                // ],
                'generator' => [
                    'content',
                ],
                'keywords' => [
                    'content',
                ],
                'viewport' => [
                    'content',
                ],
                'robots' => [
                    'content',
                ],
                'googlebot' => [
                    'content',
                ],
            ],
        ],
        'link' => [
            'rel' => [
                'alternate' => [
                    'type',
                    'title',
                    'href',
                    'hreflang',
                    'media',
                ],
                'canonical' => [
                    'href',
                ],
                // 'icon' => [
                //     'href',
                //     'sizes',
                // ],
                'stylesheet' => [
                    'href',
                    'type',
                    'media',
                ],
                'prev' => [
                    'href',
                ],
                'next' => [
                    'href',
                ],
                'amphtml' => [
                    'href',
                ],

            ],
        ],
        // Note that for scripts, the "content" is rendered as the script itself, not inside a "content" attribute
        'script' => [
            'type' => [
                'application/ld+json' => [
                    'content',
                ],
            ],
        ],
    ];


    /**
     * Get the list of available tags, allowing override form config if set
     *
     * @return array
     */
    public static function getAvailableTagList()
    {
        $config_override = Kohana::config('sprout.custom_meta.available_tag_list');

        if (!empty($config_override)) {
            static::$available_tag_list = $config_override;
        }

        return static::$available_tag_list;
    }


    /**
     * Get the list of available tags for the given tag type
     *
     * @param string $tag_type The tag type to get the list for
     * @return array
     */
    public static function getAvailableTags($tag_type)
    {
        $available_tags = static::getAvailableTagList();

        if (isset($available_tags[$tag_type])) {
            return $available_tags[$tag_type];
        }

        return [];
    }


    /**
     * Get the list of available attributes for the given tag type and tag
     *
     * @param string $tag_type The tag type to get the list for
     * @param string $tag The tag to get the list for
     * @return array
     */
    public static function getAvailableAttributes($tag_type, $tag)
    {
        $available_tags = static::getAvailableTags($tag_type);

        if (isset($available_tags[$tag])) {
            return $available_tags[$tag];
        }

        return [];
    }


    /**
     * Get the list of tags set for the current page
     *
     * @param int $page_id The page ID to get the tags for
     *
     * @return array
     */
    public static function getPageTags(int $page_id)
    {
        $q = "SELECT * FROM ~page_custom_tags WHERE page_id = ?";
        $tags = Pdb::query($q, [$page_id], 'arr');

        foreach ($tags as &$tag) {
            $tag['attr_values'] = json_decode($tag['attr_values'], true);
        }
        unset($tag);

        return $tags;
    }


    /**
     * Get the list of tags set for the current page
     *
     * @param int $page_id The page ID to get the tags for
     *
     * @return array
     */
    public static function getHomepageTags(int $homepage_id)
    {
        $q = "SELECT * FROM ~homepage_custom_tags WHERE homepage_id = ?";
        $tags = Pdb::query($q, [$homepage_id], 'arr');

        foreach ($tags as &$tag) {
            $tag['attr_values'] = json_decode($tag['attr_values'], true);
        }
        unset($tag);

        return $tags;
    }


    public static function renderTagsFormElementHome(int $homepage_id)
    {
        $available_tags = static::getAvailableTagList();
        $current_tags = static::getHomepageTags($homepage_id);

        $view = new PhpView('sprout/views/admin/custom_head_tag_edit');
        $view->available_tags = $available_tags;
        $view->current_tags = $current_tags;

        echo $view->render();
    }


    /**
     * Render the form element for the custom meta tags
     *
     * @param int $page_id The page ID to get the tags for
     *
     * @return string
     */
    public static function renderTagsFormElement(int $page_id)
    {
        $available_tags = static::getAvailableTagList();
        $current_tags = static::getPageTags($page_id);

        $view = new PhpView('sprout/views/admin/custom_head_tag_edit');
        $view->available_tags = $available_tags;
        $view->current_tags = $current_tags;

        return $view->render();
    }


    /**
     * Save the custom meta tags for the given page
     *
     * @param int $page_id The page ID to save the tags for
     * @param array $tags The tags to save
     *
     * @return void
     */
    public static function savePageTags(int $page_id, array $tags)
    {
        Pdb::delete('page_custom_tags', ['page_id' => $page_id]);

        $data = static::buildTagsData($tags);
        foreach ($data as $insert) {
            $insert['page_id'] = $page_id;
            Pdb::insert('page_custom_tags', $insert);
        }
    }


    /**
     * Save the custom meta tags for the given page
     *
     * @param int $page_id The page ID to save the tags for
     * @param array $tags The tags to save
     *
     * @return void
     */
    public static function saveHomepageTags(int $page_id, array $tags)
    {
        Pdb::delete('homepage_custom_tags', ['homepage_id' => $page_id]);

        $data = static::buildTagsData($tags);
        foreach ($data as $insert) {
            $insert['homepage_id'] = $page_id;
            Pdb::insert('homepage_custom_tags', $insert);
        }
    }


    /**
     * Build the data array for saving the tags, excluding the (home)page ID
     *
     * @param array $tags The tags to save
     *
     * @return array
     */
    private static function buildTagsData(array $tags)
    {
        $available_tags = static::getAvailableTagList();

        $data = [];
        foreach ($tags as $tag) {
            if (empty($tag['attr_values'])) continue;

            // Security validation to stop arbitrary tag adding
            if (!isset($available_tags[$tag['tag_type']][$tag['tag']])) continue;

            $data[] = [
                'tag_type' => $tag['tag_type'],
                'tag' => $tag['tag'],
                'attribute' => $tag['attribute'],
                'attr_values' => json_encode($tag['attr_values']),
            ];
        }

        return $data;
    }


    /**
     * Render the custom meta tags for the current page
     *
     * @return void
     */
    public static function addHeadTags()
    {
        $node = Navigation::getMatchedNode();
        if (!$node) return null;

        $tags = static::getPageTags($node['id']);
        static::addTagNeeds($tags);
    }


    /**
     * Render the custom meta tags for the current homepage
     *
     * @return void
     */
    public static function addHeadTagsHome(int $homepage_id)
    {
        $tags = static::getHomepageTags($homepage_id);
        static::addTagNeeds($tags);
    }


    /**
     * Return a canonical URL for the current page if set in custom meta
     *
     * @param int $page_id
     * @return string|null
     */
    public static function getCanonicalURL(int $page_id)
    {
        $tags = static::getPageTags($page_id);

        foreach ($tags as $tag) {
            // Canonical handled in Page Controller
            if ($tag['tag_type'] == 'link' and $tag['attribute'] == 'canonical') {
                return $tag['attr_values']['href'] ?? '';
            }
        }

        return null;
    }


    /**
     * Build a single generic (non-script tag)
     *
     * @param array $tag The tag contents array
     *
     * @return string The completed HTML tag string
     */
    private static function renderGenericTag(array $tag)
    {
        $tag_type = Enc::html($tag['tag_type']);
        $tag_tag = Enc::html($tag['tag']);
        $attribute = Enc::html($tag['attribute']);


        $html_tag = "<{$tag_type} {$tag_tag}=\"{$attribute}\" ";

        foreach ($tag['attr_values'] as $attr => $value) {
            $attr = Enc::html($attr);
            $value = Enc::html($value);
            $html_tag .= "{$attr}=\"{$value}\" ";
        }
        $html_tag .= "/>";

        return $html_tag;
    }


    /**
     * Build a single script tag
     *
     * Note this works a bit differently as the first attr value is the content inside the tag core
     *
     * @param array $tag The tag contents array
     *
     * @return string The completed HTML tag string
     */
    private static function renderScriptTag(array $tag)
    {
        $tag_type = Enc::html($tag['tag_type']);
        $tag_tag = Enc::html($tag['tag']);
        $attribute = Enc::html($tag['attribute']);

        $value = reset($tag['attr_values']);

        return "<{$tag_type} {$tag_tag}=\"{$attribute}\">{$value}</{$tag_type}>";
    }


    /**
     * Perform the actual echoing of the tags
     *
     * @return void
     */
    private static function addTagNeeds(array $tags)
    {
        foreach ($tags as $tag) {
            // Canonical handled in Page Controller
            if ($tag['tag_type'] == 'link' and $tag['attribute'] == 'canonical') continue;

            if ($tag['tag_type']== 'script') {
                $html_tag = CustomHeadTags::renderScriptTag($tag);
                Needs::addNeed($html_tag);
                return;
            }

            $html_tag = static::renderGenericTag($tag);
            Needs::addNeed($html_tag);
        }
    }

}
