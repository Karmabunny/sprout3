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

use karmabunny\pdb\Exceptions\ConnectionException;
use PHPUnit\Framework\TestCase;
use Sprout\Helpers\Constants;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Page;
use Sprout\Helpers\Pdb;
use Sprout\Models\PageModel;
use Sprout\Models\PageRevisionModel;

class PageTest extends TestCase
{

    /** @var PageModel */
    public static $root;

    /** @var PageModel */
    public static $child;


    public static function setUpBeforeClass(): void
    {
        try {
            Pdb::query("SELECT 1", [], 'null');
        } catch (ConnectionException $ex) {
            self::markTestSkipped('mysql is not available right now');
        }

        Pdb::delete('pages', ['slug' => ['sprout-test', 'child-sprout']]);

        self::$root = self::createPage('Sprout test');
        self::$child = self::createPage('Child sprout');

        self::$child->parent_id = self::$root->id;
        self::$child->save();
    }


    public static function createPage($name)
    {
        $page = new PageModel();
        $page->name = $name;
        $page->meta_description = 'test page';
        $page->parent_id = 0;
        $page->active = true;
        $page->slug = Enc::urlname($name, '-');
        $page->show_in_nav = true;
        $page->subsite_id = 1;
        $page->modified_editor = 'admin';
        $page->alt_template = '';
        $page->menu_group = 0;
        $page->admin_perm_type = Constants::PERM_INHERIT;
        $page->user_perm_type = Constants::PERM_INHERIT;
        $page->hit_count = 0;
        $page->stale_reminder_sent = '0000-00-00';
        $page->save();

        $revision = new PageRevisionModel();
        $revision->page_id = $page->id;
        $revision->type = 'standard';
        $revision->changes_made = 'New page';
        $revision->status = 'live';
        $revision->modified_editor = 'admin';
        $revision->operator_id = 0;
        $revision->approval_operator_id = 0;
        $revision->approval_code = '';
        $revision->controller_entrance = '';
        $revision->controller_argument = '';
        $revision->redirect = '';
        $revision->save();

        return $page;
    }


    public function testUrl()
    {
        $pages = Pdb::lookup('pages');

        if (count($pages) === 0) {
            $this->markTestSkipped('Cannot test page URLs without any pages in the database');
        }

        $integer = Page::url((int) key($pages));
        $this->assertTrue(is_string($integer));

        $string = Page::url((string) key($pages));
        $this->assertTrue(is_string($string));

        $this->assertTrue($integer == $string);

        $url = Page::url(2362728);
        $this->assertEquals('page/view_by_id/2362728', $url);

        $url = Page::url('2362728');
        $this->assertEquals('page/view_by_id/2362728', $url);

        $url = Page::url('abcde');
        $this->assertNull($url);

        $url = Page::url(array());
        $this->assertNull($url);

        $url = Page::url(self::$root->id);
        $this->assertNotNull($url);
        $this->assertEquals('sprout-test', $url);

        $url = Page::url(self::$child->id);
        $this->assertNotNull($url);
        $this->assertEquals('sprout-test/child-sprout', $url);
    }

}