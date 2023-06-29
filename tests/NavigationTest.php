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
use Sprout\Helpers\Enc;
use Sprout\Helpers\Navigation;
use Sprout\Helpers\Pdb;


class NavigationTest extends TestCase
{

    // pages.id, pages.parent_id, pages.active, pages.name, page_revisions.type, page_revisions.status
    private static $fake_pages = [
        [1, 0, 1,  'Top level one - std live', 'standard', 'live'],
        [2, 0, 1,  'Top level two - std live', 'standard', 'live'],
        [3, 0, 0,  'Top level three - inactive', 'standard', 'live'],
        [4, 0, 1,  'Top level four - unpublished', 'standard', 'wip'],
    ];

    /**
     * Duplicate pages tables and then inject some fake data
     */
    public static function setUpBeforeClass(): void
    {
        try {
            Pdb::query("SELECT 1", [], 'null');
        } catch (ConnectionException $ex) {
            self::markTestSkipped('mysql is not available right now');
        }

        $rand = mt_rand(0,9999);

        $q = "CREATE TEMPORARY TABLE unit_test_{$rand}_pages SELECT * FROM ~pages WHERE 0";
        Pdb::q($q, [], 'null');

        $q = "CREATE TEMPORARY TABLE unit_test_{$rand}_page_revisions SELECT * FROM ~page_revisions WHERE 0";
        Pdb::q($q, [], 'null');

        foreach (self::$fake_pages as $pg) {
            $status = array_pop($pg);
            $type = array_pop($pg);

            $pg[4] = Enc::urlname($pg[3]);
            $q = "INSERT INTO unit_test_{$rand}_pages SET id = ?, subsite_id = 1, parent_id = ?, active = ?, name = ?, slug = ?";
            Pdb::q($q, $pg, 'null');

            if ($type and $status) {
                $q = "INSERT INTO unit_test_{$rand}_page_revisions SET page_id = ?, type = ?, status = ?";
                Pdb::q($q, [$pg[0], $type, $status], 'null');
            }
        }

        Pdb::setTablePrefixOverride('pages', "unit_test_{$rand}_");
        Pdb::setTablePrefixOverride('page_revisions', "unit_test_{$rand}_");
    }

    /**
     * Quick and dirty check that the INSERTs above actually worked
     */
    public function testDatabaseSetupWorked()
    {
        $q = "SELECT * FROM ~pages WHERE parent_id = 0";
        $top_pages = Pdb::query($q, [], 'arr');
        $this->assertCount(4, $top_pages);
        $this->assertEquals('Top level one - std live', $top_pages[0]['name']);
        $this->assertEquals('Top level two - std live', $top_pages[1]['name']);
        $this->assertEquals('Top level three - inactive', $top_pages[2]['name']);
        $this->assertEquals('Top level four - unpublished', $top_pages[3]['name']);
    }

    /**
     * Put the prefixes back
     */
    public static function tearDownAfterClass(): void
    {
        Pdb::setTablePrefixOverride('pages', Pdb::prefix());
        Pdb::setTablePrefixOverride('page_revisions', Pdb::prefix());
    }


    /**
     * Admin loading should bring in all pages, including inactive and unpublished
     */
    public function testLoadPageTree__Admin()
    {
        $root = Navigation::loadPageTree(1, true, false);
        $this->assertCount(4, $root->children);
        $this->assertEquals('Top level one - std live', $root->children[0]['name']);
        $this->assertEquals('Top level two - std live', $root->children[1]['name']);
        $this->assertEquals('Top level three - inactive', $root->children[2]['name']);
        $this->assertEquals('Top level four - unpublished', $root->children[3]['name']);
    }

    /**
     * Front-end loading should not bring in inactive pages
     */
    public function testLoadPageTree__FrontEnd()
    {
        $root = Navigation::loadPageTree(1, false, false);
        $this->assertCount(2, $root->children);
        $this->assertEquals('Top level one - std live', $root->children[0]['name']);
        $this->assertEquals('Top level two - std live', $root->children[1]['name']);
    }


    public static function dataCustomBreadcrumb()
    {
        return array(
            array(array('one'), '<a href="SITE/">Home</a> &raquo; <span>one</span>'),
            array(array('aaa' => 'one'), '<a href="SITE/">Home</a> &raquo; <span>one</span>'),

            array(array('one', 'two'), '<a href="SITE/">Home</a> &raquo; <a href="">one</a> &raquo; <span>two</span>'),
            array(array('aaa' => 'one', 'two'), '<a href="SITE/">Home</a> &raquo; <a href="aaa">one</a> &raquo; <span>two</span>'),
            array(array('one', 'aaa' => 'two'), '<a href="SITE/">Home</a> &raquo; <a href="">one</a> &raquo; <span>two</span>'),
            array(array('aaa' => 'one', 'bbb' => 'two'), '<a href="SITE/">Home</a> &raquo; <a href="aaa">one</a> &raquo; <span>two</span>'),

            array(array('aaa' => 'o&e', 'two'), '<a href="SITE/">Home</a> &raquo; <a href="aaa">o&amp;e</a> &raquo; <span>two</span>'),
            array(array('aaa' => 'o&e', 't&o'), '<a href="SITE/">Home</a> &raquo; <a href="aaa">o&amp;e</a> &raquo; <span>t&amp;o</span>'),
            array(array('aaa' => 'one', 't&o'), '<a href="SITE/">Home</a> &raquo; <a href="aaa">one</a> &raquo; <span>t&amp;o</span>'),

            array(array('a&a' => 'one', 'two'), '<a href="SITE/">Home</a> &raquo; <a href="a&amp;a">one</a> &raquo; <span>two</span>'),
        );
    }


    /**
    * @dataProvider dataCustomBreadcrumb
    **/
    public function testCustomBreadcrumb($crumbs, $expected)
    {
        $actual = Navigation::customBreadcrumb($crumbs);
        $this->assertEquals($expected, $actual);
    }

}
