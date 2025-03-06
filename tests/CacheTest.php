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

use PHPUnit\Framework\TestCase;
use Sprout\Helpers\Drivers\Cache\File as FileDriver;
use Sprout\Helpers\Drivers\Cache\Rdb as RdbDriver;
use Sprout\Helpers\Drivers\CacheDriver;

/**
 * Test suite for the cache driver.
 *
 * Only testing file + rdb as these are the only that are maintained.
 * The others will be deleted soon, I hope.
 */
class CacheTest extends TestCase
{

    public ?FileDriver $file = null;
    public ?RdbDriver $rdb = null;


    public function setUp(): void
    {
        $this->dataCacheDrivers();

        // Clear caches before each test.
        exec('rm -rf ' . STORAGE_PATH . 'cache/*');
        $this->rdb->rdb->flushPrefix();
    }


    /**
     * @return array
     */
    public function dataCacheDrivers()
    {
        $this->file ??= new FileDriver(STORAGE_PATH . 'cache');
        $this->rdb ??= new RdbDriver([
            'host' => 'localhost',
            'prefix' => 'sprout-test-cache:',
        ]);

        return [
            'file' => [ $this->file ],
            'redis' => [ $this->rdb ],
        ];
    }

    /**
     * @dataProvider dataCacheDrivers
     * @param CacheDriver $cache
     */
    public function testBasics($cache)
    {
        $id = 'test-cache-id';
        $data = ['foo' => 'bar'];
        $tags = ['test-tag'];

        // Test setting cache
        $result = $cache->set($id, $data, $tags);
        $this->assertTrue($result, 'Cache set should return true.');

        // Test getting cache by tag
        $tagged = $cache->find('test-tag');
        $this->assertNotEmpty($tagged, 'Tagged cache should be found.');
        $this->assertEquals($data, $tagged[$id], 'Tagged cache data should match original.');

        // Test fetching cache
        $exists = $cache->get($id);
        $this->assertNotNull($exists, 'Cache file should exist.');
        $this->assertEquals($data, $exists);

        // Test deleting cache
        $cache->delete($id);
        $exists = $cache->get($id);
        $this->assertNull($exists, 'Cache should be deleted.');
    }


    /**
     * @dataProvider dataCacheDrivers
     * @param CacheDriver $cache
     */
    public function testLifetime($cache)
    {
        $id1 = 'test-cache-id';
        $id2 = 'test-cache-id-2';
        $data1 = ['foo' => 'bar'];
        $data2 = ['baz' => 'qux'];

        // Test setting cache with lifetime
        $result1 = $cache->set($id1, $data1, null, 1);
        $result2 = $cache->set($id2, $data2, null, 3);
        $this->assertTrue($result1);
        $this->assertTrue($result2);

        // Test getting cache before lifetime expires
        $exists = $cache->get($id1);
        $this->assertNotNull($exists, 'Cache should exist.');

        // Test getting cache before lifetime expires
        $exists = $cache->get($id2);
        $this->assertNotNull($exists, 'Cache should exist.');

        sleep(2);

        $exists = $cache->get($id1);
        $this->assertNull($exists, 'Cache should be deleted.');

        $exists = $cache->get($id2);
        $this->assertNotNull($exists, 'Cache should exist.');

        sleep(2);

        $exists = $cache->get($id2);
        $this->assertNull($exists, 'Cache should be deleted.');
    }


    /**
     * @dataProvider dataCacheDrivers
     * @param CacheDriver $cache
     */
    public function testTags($cache)
    {
        $id1 = 'test-cache-id-1';
        $id2 = 'test-cache-id-2';
        $id3 = 'test-cache-id-3';
        $data1 = ['foo' => 'bar'];
        $data2 = ['baz' => 'qux'];
        $data3 = ['quux' => 'corge'];
        $tag1 = 'test-tag-1';
        $tag2 = 'test-tag-2';

        // Set multiple cache items with different tags
        $result1 = $cache->set($id1, $data1, [$tag1]);
        $result2 = $cache->set($id2, $data2, [$tag1, $tag2]);
        $result3 = $cache->set($id3, $data3);

        $this->assertTrue($result1);
        $this->assertTrue($result2);
        $this->assertTrue($result3);

        // Test finding by first tag
        $tagged1 = $cache->find($tag1);
        $this->assertCount(2, $tagged1, 'First tag should have two items.');
        $this->assertEquals($data1, $tagged1[$id1], 'First tagged item should match.');
        $this->assertEquals($data2, $tagged1[$id2], 'Second tagged item should match.');

        // Test finding by second tag
        $tagged2 = $cache->find($tag2);
        $this->assertCount(1, $tagged2, 'Second tag should have one item.');
        $this->assertEquals($data2, $tagged2[$id2], 'Tagged item should match.');

        // Test deleting by tag
        $result = $cache->delete($tag2, true);
        $this->assertTrue($result, 'Tag deletion should return true.');

        $exists1 = $cache->get($id1);
        $exists2 = $cache->get($id2);
        $this->assertNotNull($exists1, 'First cache item should be deleted.');
        $this->assertNull($exists2, 'Second cache item should exist.');

        // Delete all items by tag.
        $result2 = $cache->set($id2, $data2, [$tag1, $tag2]);
        $result = $cache->delete($tag1, true);
        $this->assertTrue($result, 'Tag deletion should return true.');

        $exists1 = $cache->get($id1);
        $exists2 = $cache->get($id2);
        $exists3 = $cache->get($id3);
        $this->assertNull($exists1, 'First cache item should be deleted.');
        $this->assertNull($exists2, 'Second cache item should be deleted.');
        $this->assertNotNull($exists3, 'Third cache item should exist.');

        $tagged1 = $cache->find($tag1);
        $tagged2 = $cache->find($tag2);
        $this->assertEmpty($tagged1, 'First tag should have no items.');
        $this->assertEmpty($tagged2, 'Second tag should have no items.');
    }
}