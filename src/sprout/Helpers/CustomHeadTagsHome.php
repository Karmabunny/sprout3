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


/**
 * Management of custom HEAD tags for home pages. Often meta but can also be link, base, etc
 */
class CustomHeadTagsHome extends CustomHeadTags
{

    /**
     * Get the list of tags set for the current page
     *
     * @param int $page_id The page ID to get the tags for
     *
     * @return array
     */
    public static function getHomepageTags(int $homepage_id): array
    {
        $q = "SELECT * FROM ~homepage_custom_tags WHERE homepage_id = ?";
        $tags = Pdb::query($q, [$homepage_id], 'arr');

        foreach ($tags as &$tag) {
            $tag['attr_values'] = json_decode($tag['attr_values'], true);
        }
        unset($tag);

        return $tags;
    }


    /**
     * Render the form element for the custom meta tags on the homepage
     *
     * @param int $homepage_id The homepage ID to get the tags for
     *
     * @return void
     */
    public static function renderTagsFormElementHome(int $homepage_id): void
    {
        $available_tags = static::getAvailableTagList();
        $current_tags = static::getHomepageTags($homepage_id);

        $view = new PhpView('sprout/views/admin/custom_head_tag_edit');
        $view->available_tags = $available_tags;
        $view->current_tags = $current_tags;

        echo $view->render();
    }


    /**
     * Save the custom meta tags for the given page
     *
     * @param int $page_id The page ID to save the tags for
     * @param array $tags The tags to save
     *
     * @return void
     */
    public static function saveHomepageTags(int $page_id, array $tags): void
    {
        Pdb::delete('homepage_custom_tags', ['homepage_id' => $page_id]);

        $data = static::buildTagsData($tags);
        foreach ($data as $insert) {
            $insert['homepage_id'] = $page_id;
            Pdb::insert('homepage_custom_tags', $insert);
        }
    }


    /**
     * Render the custom meta tags for the current homepage
     *
     * @return void
     */
    public static function addHeadTagsHome(int $homepage_id): void
    {
        $tags = static::getHomepageTags($homepage_id);
        static::addTagNeeds($tags);
    }

}
