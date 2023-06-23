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

use Sprout\Helpers\DocImport\DocImport;
use Sprout\Helpers\Treenode;


/**
* Unit tests
**/
class docImportTest extends PHPUnit_Framework_TestCase
{

    /**
    * Data for testGetHtmlBasic
    **/
    public static function htmlBasicData()
    {
        return array(
            array('', ''),
            array('<p>Hello</p>', '<p>Hello</p>'),
            array('<p>Hello<br/>world</p>', '<p>Hello<br>world</p>'),
            array('<p>Hello<br />world</p>', '<p>Hello<br>world</p>'),
        );
    }


    /**
    * Basic getHtml tests
    *
    * @dataProvider htmlBasicData
    **/
    public function testGetHtmlBasic($xml, $expect)
    {
        $dom = new DOMDocument();
        $dom->loadXML('<doc><body>' . $xml . '</body></doc>');

        $got = DocImport::getHtml($dom);

        $this->assertEquals($expect, $got);
    }


    /**
    * Data for testGetHtmlImages
    **/
    public static function htmlImagesData()
    {
        return array(
            array('<img rel="aaa"/>', '<img src="image_aaa.jpg">'),
            array('<p>Hello</p><img rel="aaa"/>', '<p>Hello</p><img src="image_aaa.jpg">'),
            array('<img rel="aaa"/><p>Hello</p>', '<img src="image_aaa.jpg"><p>Hello</p>'),
            array('<img rel="aaa"/><img rel="bbb"/>', '<img src="image_aaa.jpg"><img src="image_bbb.jpg">'),
            array('<img rel="aaa"/><img rel="bbb"/><img rel="ccc"/>', '<img src="image_aaa.jpg"><img src="image_bbb.jpg">'),

            array('<img rel="aaa" width="100" height="150"/>', '<img width="100" height="150" src="image_aaa.jpg">'),
            array('<img rel="aaa" width="" height=""/>', '<img src="image_aaa.jpg">'),
            array('<img rel="aaa" width="0" height="0"/>', '<img src="image_aaa.jpg">'),

            array('<img rel="aaa" error="invalid" width="100" height="150"/>', '<img width="100" height="150" src="http://placehold.it/100x150&amp;text=invalid">'),
            array('<img rel="aaa" error="invalid" width="0" height="0"/>', '<img src="http://placehold.it/300x50&amp;text=invalid">'),
            array('<img rel="aaa" error="invalid" width="" height=""/>', '<img src="http://placehold.it/300x50&amp;text=invalid">'),
            array('<img rel="aaa" error="invalid"/>', '<img src="http://placehold.it/300x50&amp;text=invalid">'),
        );
    }


    /**
    * Image getHtml tests
    *
    * @dataProvider htmlImagesData
    **/
    public function testGetHtmlImages($xml, $expect)
    {
        $imgs = array(
            'aaa' => 'image_aaa.jpg',
            'bbb' => 'image_bbb.jpg',
        );

        $dom = new DOMDocument();
        $dom->loadXML('<doc><body>' . $xml . '</body></doc>');

        $got = DocImport::getHtml($dom, $imgs);

        $this->assertEquals($expect, $got);
    }


    /**
    * Data for testGetHtmlHeadings
    **/
    public static function htmlHeadingsData()
    {
        return array(
            array('<h1>aaa</h1>', '<h2>aaa</h2>'),
        );
    }

    /**
    * Image getHtml tests
    *
    * @dataProvider htmlHeadingsData
    **/
    public function testGetHtmlHeadings($xml, $expect)
    {
        $headings = array(
            1 => 2
        );

        $dom = new DOMDocument();
        $dom->loadXML('<doc><body>' . $xml . '</body></doc>');

        $got = DocImport::getHtml($dom, array(), $headings);

        $this->assertEquals($expect, $got);
    }


    /**
    * Test treenode building from headings
    **/
    public function testGetHeadingsTreeBasics()
    {
        $dom = new DOMDocument();

        $dom->loadXML('<doc><body><h1>Test</h1><p>Test</p></body></doc>');
        $tree = DocImport::getHeadingsTree($dom, 1);
        $this->assertTrue($tree instanceof Treenode);
        $this->assertTrue(count($tree->children) == 1);
        $this->assertTrue($tree->children[0] instanceof Treenode);
        $this->assertTrue($tree->children[0]['name'] == 'Test');

        $dom->loadXML('<doc><body><h1>One</h1><p>Test</p><h1>Two</h1></body></doc>');
        $tree = DocImport::getHeadingsTree($dom, 1);
        $this->assertTrue(count($tree->children) == 2);
        $this->assertTrue($tree->children[0]['name'] == 'One');
        $this->assertTrue($tree->children[1]['name'] == 'Two');

        $dom->loadXML('<doc><body><h1>One</h1><h1>Two</h1></body></doc>');
        $tree = DocImport::getHeadingsTree($dom, 1);
        $this->assertTrue(count($tree->children) == 2);
        $this->assertTrue($tree->children[0]['name'] == 'One');
        $this->assertTrue($tree->children[1]['name'] == 'Two');

        $dom->loadXML('<doc><body><h1><b>One</b></h1><h1>Two</h1></body></doc>');
        $tree = DocImport::getHeadingsTree($dom, 1);
        $this->assertTrue(count($tree->children) == 2);
        $this->assertTrue($tree->children[0]['name'] == 'One');
        $this->assertTrue($tree->children[1]['name'] == 'Two');
    }


    /**
    * Data for testGetHeadingsTreeThreeLevels
    **/
    public static function getHeadingsTreeThreeLevelsData()
    {
        return array(
            array('', array()),
            array('<h0>Zero</h0>', array()),
            array('<p>Para</p>', array()),

            // H1
            array('<h1>One</h1>', array(  'One' => array()  )),
            array('<h1>One1</h1><h1>One2</h1>', array(  'One1' => array(), 'One2' => array()  )),

            // H1 -> H2
            array('<h1>One1</h1><h2>Two1</h2><h1>One2</h1>', array(  'One1' => array('Two1'=>array()), 'One2' => array()  )),
            array('<h1>One1</h1><h2>Two1</h2><h1>One2</h1><h2>Two2</h2>', array(  'One1' => array('Two1'=>array()), 'One2' => array('Two2'=>array())  )),
            array('<h1>One1</h1><h2>Two1</h2><h2>Two2</h2><h1>One2</h1>', array(  'One1' => array('Two1'=>array(),'Two2'=>array()), 'One2' => array()  )),
            array('<h1>One1</h1><h2>Two1</h2><h2>Two2</h2><h1>One2</h1><h2>Two3</h2>', array(  'One1' => array('Two1'=>array(),'Two2'=>array()), 'One2' => array('Two3'=>array())  )),

            // H1 -> H2 -> H3
            array('<h1>One1</h1><h2>Two1</h2><h3>Three1</h3><h1>One2</h1>', array(  'One1' => array('Two1'=>array('Three1'=>null)), 'One2' => array()  )),
            array('<h1>One1</h1><h2>Two1</h2><h3>Three1</h3><h3>Three2</h3><h1>One2</h1>', array(  'One1' => array('Two1'=>array('Three1'=>null,'Three2'=>null)), 'One2' => array()  )),
            array('<h1>One1</h1><h2>Two1</h2><h3>Three1</h3><h3>Three2</h3><h4>Four</h4><h1>One2</h1>', array(  'One1' => array('Two1'=>array('Three1'=>null,'Three2'=>null)), 'One2' => array()  )),

            // H1 -> H3
            array('<h1>One1</h1><h3>Three1</h3>', array(  'One1' => array('Three1'=>array())  )),

            // H2
            array('<h2>Two1</h2>', array(  'Two1' => array()  )),
            array('<h2>Two1</h2><h3>Three1</h3>', array(  'Two1' => array('Three1' => array())  )),

            // H1/H2 -> H4
            array('<h1>One1</h1><h4>Three1</h4>', array(  'One1' => array()  )),
            array('<h2>One1</h2><h4>Three1</h4>', array(  'One1' => array()  )),

            // Going up instead of down
            array('<h2>Two1</h2><h1>One1</h1>', array(  'Two1' => array(), 'One1' => array()  )),
            array('<h3>Three1</h3><h2>Two1</h2><h1>One1</h1>', array(  'Three1' => array(), 'Two1' => array(), 'One1' => array()  )),
            array('<h3>Three1</h3><h1>One1</h1>', array(  'Three1' => array(), 'One1' => array()  )),

            // Up then down
            array('<h2>Two1</h2><h1>One1</h1><h2>Two2</h2>', array(  'Two1' => array(), 'One1' => array('Two2' => array())  )),
        );
    }


    /**
    * Test treenode building - three levels
    * @dataProvider getHeadingsTreeThreeLevelsData
    **/
    public function testGetHeadingsTreeThreeLevels($xml, $expect)
    {
        $dom = new DOMDocument();
        $dom->loadXML('<doc><body>' . $xml . '</body></doc>');
        $node0 = DocImport::getHeadingsTree($dom, 3);
        unset($dom);

        $this->assertTrue($node0 instanceof Treenode);
        $this->assertTrue(count($node0->children) == count($expect));

        $idx1 = 0;
        foreach ($expect as $name => $level1) {
            $node1 = $node0->children[$idx1];
            $this->assertTrue($node1 instanceof Treenode);
            $this->assertTrue($node1['name'] == $name);
            $this->assertTrue(count($node1->children) == count($level1));

            $idx2 = 0;
            foreach ($level1 as $name => $level2) {
                $node2 = $node1->children[$idx2];
                $this->assertTrue($node2 instanceof Treenode);
                $this->assertTrue($node2['name'] == $name);
                $this->assertTrue(count($node2->children) == count($level2));
                $idx2++;
            }

            $idx1++;
        }
    }


    /**
    * Test treenode building - include body
    **/
    public function testGetHeadingsTreeBody()
    {
        $dom = new DOMDocument();

        $dom->loadXML('<doc><body><h1>Heading</h1><p>Body</p></body></doc>');
        $tree = DocImport::getHeadingsTree($dom, 1, true);
        $this->assertTrue($tree instanceof Treenode);
        $this->assertTrue(count($tree->children) == 1);
        $this->assertTrue($tree->children[0] instanceof Treenode);
        $this->assertTrue($tree->children[0]['name'] == 'Heading');
        $this->assertTrue($tree->children[0]['body'] == '<p>Body</p>');

        $dom->loadXML('<doc><body><h1>Heading</h1><p>Body</p><p><b>Body</b></p></body></doc>');
        $tree = DocImport::getHeadingsTree($dom, 1, true);
        $this->assertTrue($tree instanceof Treenode);
        $this->assertTrue(count($tree->children) == 1);
        $this->assertTrue($tree->children[0] instanceof Treenode);
        $this->assertTrue($tree->children[0]['name'] == 'Heading');
        $this->assertTrue($tree->children[0]['body'] == '<p>Body</p><p><b>Body</b></p>');

        $dom->loadXML('<doc><body><h1>One</h1><p>Body one</p><h1>Two</h1><p>Body two</p></body></doc>');
        $tree = DocImport::getHeadingsTree($dom, 1, true);
        $this->assertTrue($tree instanceof Treenode);
        $this->assertTrue(count($tree->children) == 2);
        $this->assertTrue($tree->children[0] instanceof Treenode);
        $this->assertTrue($tree->children[0]['name'] == 'One');
        $this->assertTrue($tree->children[0]['body'] == '<p>Body one</p>');
        $this->assertTrue($tree->children[1] instanceof Treenode);
        $this->assertTrue($tree->children[1]['name'] == 'Two');
        $this->assertTrue($tree->children[1]['body'] == '<p>Body two</p>');
    }

}
