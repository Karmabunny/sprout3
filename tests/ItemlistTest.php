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
use Sprout\Helpers\ColModifierDate;
use Sprout\Helpers\ColModifierMoney;
use Sprout\Helpers\I18n;
use Sprout\Helpers\Itemlist;


class ItemlistTest extends TestCase
{

    public function testStringVal() {
        $data = [
            ['key' => '*value*'],
        ];

        $itemlist = new Itemlist();
        $itemlist->main_columns = [
            'Key' => 'key',
        ];
        $itemlist->items = $data;
        $result = $itemlist->render();

        $this->assertStringContainsString('*value*', $result);
        $this->assertStringNotContainsString('<tr class="main-list--aggregate">', $result);
    }

    public function testColModifierVal() {
        $data = [
            ['key' => '2013-05-01'],
        ];

        $itemlist = new Itemlist();
        $itemlist->main_columns = [
            'Key' => [new ColModifierDate(), 'key'],
        ];
        $itemlist->items = $data;
        $result = $itemlist->render();

        $this->assertStringContainsString('01/05/2013', $result);
    }

    public function testClosure() {
        $data = [
            [],
        ];

        $itemlist = new Itemlist();
        $itemlist->main_columns = [
            'Key' => function($row){ return '*value*'; },
        ];
        $itemlist->items = $data;
        $result = $itemlist->render();

        $this->assertStringContainsString('*value*', $result);
    }

    public function testActions() {
        $itemlist = new Itemlist();
        $itemlist->main_columns = [
            'ID' => 'id',
        ];
        $itemlist->items = [['id' => 1]];
        $itemlist->addAction('*aaa*', '*bbb*');
        $result = $itemlist->render();

        $this->assertStringContainsString('*aaa*', $result);
        $this->assertStringContainsString('*bbb*', $result);
    }

    public function testActionsClass() {
        $itemlist = new Itemlist();
        $itemlist->main_columns = [
            'ID' => 'id',
        ];
        $itemlist->items = [['id' => 1]];
        $itemlist->addAction('*aaa*', '*bbb*', '*ccc*');
        $result = $itemlist->render();

        $this->assertStringContainsString('*aaa*', $result);
        $this->assertStringContainsString('*bbb*', $result);
        $this->assertStringContainsString('*ccc*', $result);
    }

    public function testActionsShowFunc() {
        $itemlist = new Itemlist();
        $itemlist->main_columns = [
            'ID' => 'id',
        ];
        $itemlist->items = [['id' => 1]];
        $itemlist->addAction('*shown*', 'Shown', '', function($row){ return true; });
        $itemlist->addAction('*hidden*', 'Hidden', '', function($row){ return false; });
        $result = $itemlist->render();

        $this->assertStringContainsString('*shown*', $result);
        $this->assertStringNotContainsString('*hidden*', $result);
    }

    public function testAggregateTotal() {
        $itemlist = new Itemlist();
        $itemlist->main_columns = [
            'Val' => 'val',
        ];
        $itemlist->addAggregateColumn('Val', 'sum');
        $itemlist->items = [['val' => 10], ['val' => 20]];
        $result = $itemlist->render();
        $this->assertStringContainsString('<tr class="main-list--aggregate">', $result);
        $this->assertStringContainsString('<td>30</td>', $result);
    }

    public function testAggregateTotalColModifier() {
        I18n::init('AUS');
        $itemlist = new Itemlist();
        $itemlist->main_columns = [
            'Val' => 'val',
        ];
        $itemlist->addAggregateColumn('Val', 'sum', new ColModifierMoney('AUS'));
        $itemlist->items = [['val' => 10], ['val' => 20]];
        $result = $itemlist->render();
        $this->assertStringContainsString('<td>$30.00</td>', $result);
    }

    public function testAggregateCount() {
        $itemlist = new Itemlist();
        $itemlist->main_columns = [
            'Val' => 'val',
        ];
        $itemlist->addAggregateColumn('Val', 'count');
        $itemlist->items = [['val' => 10], ['val' => 10], ['val' => 10]];
        $result = $itemlist->render();
        $this->assertStringContainsString('<td>3</td>', $result);
    }

    public function testAggregateAvg() {
        $itemlist = new Itemlist();
        $itemlist->main_columns = [
            'Val' => 'val',
        ];
        $itemlist->addAggregateColumn('Val', 'avg');
        $itemlist->items = [['val' => 10], ['val' => 20]];
        $result = $itemlist->render();
        $this->assertStringContainsString('<td>15</td>', $result);
    }

    public function testAggregateValue() {
        $itemlist = new Itemlist();
        $itemlist->main_columns = [
            'Val' => 'val',
        ];
        $itemlist->addAggregateValue('Val', '*agg*');
        $itemlist->items = [['val' => 10]];
        $result = $itemlist->render();
        $this->assertStringContainsString('<td>*agg*</td>', $result);
    }

}
