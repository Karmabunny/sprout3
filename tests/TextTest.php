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
use Sprout\Helpers\Text;


class TextTest extends TestCase
{

    public static function dataLimitWordsHtml()
    {
        return array(
            array('no tags at all', 5, 'no tags at all'),
            array('no tags no tags no tags', 5, 'no tags no tags no...'),

            array('<p>hi there</p>', 5, '<p>hi there</p>'),
            array('<p>hi there hi hi hi</p>', 5, '<p>hi there hi hi hi</p>'),
            array('<p>hi there hi hi hi hi</p>', 5, '<p>hi there hi hi hi...</p>'),

            array('<P>hi there</P>', 5, '<P>hi there</p>'),
            array('<P>hi there hi hi hi</P>', 5, '<P>hi there hi hi hi</p>'),
            array('<P>hi there hi hi hi hi</P>', 5, '<P>hi there hi hi hi...</p>'),

            array('<P>hi there</p>', 5, '<P>hi there</p>'),
            array('<P>hi there hi hi hi hi</p>', 5, '<P>hi there hi hi hi...</p>'),

            array('Hi <!-- there --> how are you', 5, 'Hi how are you'),
            array('Hi <!-- there --> how are you', 4, 'Hi how are you'),
            array('Hi <!-- there --> how are you', 3, 'Hi how are...'),

            array('Hi <br> how are you', 5, 'Hi <br> how are you'),
            array('Hi <br/> how are you', 4, 'Hi <br/> how are you'),
            array('Hi <br /> how are you', 3, 'Hi <br /> how are...'),

            array('Hi <img src=""> how are you', 5, 'Hi <img src=""> how are you'),
            array('Hi <img> how are you', 4, 'Hi <img> how are you'),
            array('Hi <img src="" /> how are you', 3, 'Hi <img src="" /> how are...'),
            array('Hi <img src=""/> how are you', 3, 'Hi <img src=""/> how are...'),
            array('Hi <img/> how are you', 3, 'Hi <img/> how are...'),

            array('Hi <img src="AaA"> how are you', 5, 'Hi <img src="AaA"> how are you'),
            array('Hi <ImG src="AaA"> how are you', 5, 'Hi <ImG src="AaA"> how are you'),
            array('Hi <a href="AaA"> how</a> are you', 5, 'Hi <a href="AaA"> how</a> are you'),
            array('Hi <A href="AaA"> how</a> are you', 5, 'Hi <A href="AaA"> how</a> are you'),
            array('Hi <a href="AaA"> how</A> are you', 5, 'Hi <a href="AaA"> how</a> are you'),
            array('Hi <a HREF="AaA"> how</a> are you', 5, 'Hi <a HREF="AaA"> how</a> are you'),

            array("Hi <!--\n there --> how are you", 3, 'Hi how are...'),
            array("Hi <!-- there \n--> how are you", 3, 'Hi how are...'),
            array("Hi <!----> how are you", 3, 'Hi how are...'),
            array("Hi <!--\n--> how are you", 3, 'Hi how are...'),

            array('<h1>hi there</h1>', 5, '<h1>hi there</h1>'),
            array('<h1>hi there hi hi hi</h1>', 5, '<h1>hi there hi hi hi</h1>'),
            array('<h1>hi there hi hi hi hi</h1>', 5, '<h1>hi there hi hi hi...</h1>'),

            array('<p>hi there <b>hi</b> hi hi hi</p>', 5, '<p>hi there <b>hi</b> hi hi...</p>'),
            array('<p>hi there hi hi he<b>llo</b></p>', 5, '<p>hi there hi hi he...</p>'),
            array('<p>hi there hi hi <b>hi hi</b> hi</p>', 5, '<p>hi there hi hi <b>hi...</b></p>'),
            array('<p>hi there hi hi <b>hi</b> hi</p>', 5, '<p>hi there hi hi <b>hi</b>...</p>'),

            array(
                "<ul>\n<li>4 star accommodation</li>\n<li>3 Bedroom</li>\n<li>Queen Bed/Singles/Bunk Bed</li>\n<li>All linen provided</li></ul>",
                6,
                "<ul>\n<li>4 star accommodation</li>\n<li>3 Bedroom</li>\n<li>Queen...</li></ul>"
            ),
        );
    }


    /**
    * @dataProvider dataLimitWordsHtml
    **/
    public function testLimitWordsHtml($html, $limit, $expect)
    {
        $out = Text::limitWordsHtml($html, $limit);
        $this->assertEquals($expect, $out);
    }


    public static function dataCensor()
    {
        return [
            [' cat ', ' cat ', false],
            [' snaKe ', ' ***** ', false],
            ['Sussex University', 'Sus*** University', false],
            ['Sussex University', 'Sussex University', true],
            ['Clowns clown around with their clowny friends', '*****s ***** around with their *****y friends', false],
            ['Clowns clown around with their clowny friends', 'Clowns ***** around with their clowny friends', true],
            ['Hate leads to the dark side', '***e leads to the dark side', false],
            ['Hate leads to the dark side', '**** leads to the dark side', true],
            ['Big deal', '********', false],
            ['Big deal', '********', true],
            ['Big deals', '********s', false],
            ['Big deals', 'Big deals', true],
            ['Great wad of cash.', 'Great wad of cash.', false],
            ['Great wad of cash/', 'Great wad of cash/', true],
            ['Great big wad of cash?', 'Great big wad of cash?', false],
            ['Great big wad of cash$', 'Great big wad of cash$', true],
            ['Greatbigwad', '***********', false],
            ['Greatbigwad', '***********', true],
        ];
    }

    /**
     * @dataProvider dataCensor
     */
    public function testCensor($original, $expect, $partial)
    {
        $bad_words = ['sex', 'Snake', '*hat*', 'clown', 'big deal', 'great*wad'];
        $out = Text::censor($original, $bad_words, '*', $partial);
        $this->assertEquals($expect, $out);
    }


    public static function dataPlain()
    {
        return array(
            array('Hello world how are you?', 10, 'Hello world how are you?'),
            array('Hello world how are you?', 3, 'Hello world how...'),
            array('Hello world how are you?', 0, 'Hello world how are you?'),

            array('<p>Hello <b>world</b> how are you?</p>', 10, 'Hello world how are you?'),
            array('<p>Hello <b>world</b> how are you?</p>', 3, 'Hello world how...'),
            array('<p>Hello <b>world</b> how are you?</p>', 0, 'Hello world how are you?'),

            array('<p>Hello<br>How are you?</p>', 10, "Hello\nHow are you?"),
            array('<p>Hello<br/>How are you?</p>', 3, "Hello\nHow are..."),
            array('<p>Hello<br />How are you?</p>', 0, "Hello\nHow are you?"),
            array('<p>Hello<BR>How are you?</p>', 0, "Hello\nHow are you?"),
            array('<p>Hello<Br>How are you?</p>', 0, "Hello\nHow are you?"),

            array("<p>Hello<br>\nHow are you?</p>", 10, "Hello\nHow are you?"),
            array("<p>Hello<br/>\nHow are you?</p>", 3, "Hello\nHow are..."),
            array("<p>Hello<br />\rHow are you?</p>", 0, "Hello\nHow are you?"),
            array("<p>Hello<BR>\nHow are you?</p>", 0, "Hello\nHow are you?"),
            array("<p>Hello<Br>\nHow are you?</p>", 0, "Hello\nHow are you?"),

            array("<p>Hello\n<br>\nHow are you?\r</p>", 10, "Hello\nHow are you?"),
            array("<p>Hello\n<br/>\nHow\rare you?\n</p>", 3, "Hello\nHow are..."),
            array("<p>Hello\n<br />\nHow are\ryou?\n</p>", 0, "Hello\nHow are you?"),
            array("\n<p>\nHello\n<BR>\rHow\nare\nyou?\n</p>\n", 0, "Hello\nHow are you?"),
            array("<p>\nHello\n<Br>\nHow are you?</p>", 0, "Hello\nHow are you?"),

            array('<p>Hello</p><p>How are you?</p>', 10, "Hello\n\nHow are you?"),
            array('<p>Hello</p><p>How are you?</p>', 3, "Hello\n\nHow are..."),
            array('<p>Hello</p><p>How are you?</p>', 0, "Hello\n\nHow are you?"),
            array('<p>Hello</P><P>How are you?</p>', 0, "Hello\n\nHow are you?"),
            array('<p class="bob">Hello</p><p class="steve">How are you?</p>', 0, "Hello\n\nHow are you?"),

            array("<p>Hello</p>\n<p>How \n are you?</p>", 10, "Hello\n\nHow are you?"),
            array("<p>Hello</p>\n<p>How \n are you?</p>", 3, "Hello\n\nHow are..."),
            array("<p>Hello\n</p>\n<p>How are you?</p>", 0, "Hello\n\nHow are you?"),
            array("<p>\nHello</P>\n<P>\nHow are you?</p>", 0, "Hello\n\nHow are you?"),
            array("<p class='bob'>Hello</p>\n<p class='steve'>How are you?</p>", 0, "Hello\n\nHow are you?"),
            array("<p class=\"bob\">Hello</p>\n<p class=\"steve\">How are you?</p>", 0, "Hello\n\nHow are you?"),

            array('Hello < how are you?', 10, 'Hello < how are you?'),
            array('Hello < how are you?', 3, 'Hello < how...'),
            array('Hello < how are you?', 0, 'Hello < how are you?'),

            array('Hello &amp; how are you?', 10, 'Hello & how are you?'),
            array('Hello &amp; how are you?', 3, 'Hello & how...'),
            array('Hello &amp; how are you?', 0, 'Hello & how are you?'),

            array('<p>Hello <b>&amp;</b> how are you?</p>', 10, 'Hello & how are you?'),
            array('<p>Hello <b>&amp;</b> how are you?</p>', 3, 'Hello & how...'),
            array('<p>Hello <b>&amp;</b> how are you?</p>', 2, 'Hello &...'),
            array('<p>Hello <b>&amp;</b> how are you?</p>', 1, 'Hello...'),
            array('<p>Hello <b>&amp;</b> how are you?</p>', 0, 'Hello & how are you?'),

            array('<p>Hello <b>&#5772;</b> how are you?</p>', 10, 'Hello ᚌ how are you?'),
            array('<p>Hello <b>&#5772;</b> how are you?</p>', 3, 'Hello ᚌ how...'),
            array('<p>Hello <b>&#5772;</b> how are you?</p>', 2, 'Hello ᚌ...'),
            array('<p>Hello <b>&#5772;</b> how are you?</p>', 1, 'Hello...'),
            array('<p>Hello <b>&#5772;</b> how are you?</p>', 0, 'Hello ᚌ how are you?'),

            array('Hello ‽ how are you?', 10, 'Hello ‽ how are you?'),
            array('Hello ‽ how are you?', 3, 'Hello ‽ how...'),
            array('Hello ‽ how are you?', 0, 'Hello ‽ how are you?'),

            array('<p>Hello</p><style>.p { background: #000; }</style><p>World!</p>', 50, "Hello\n\nWorld!"),
            array('<p>Hello</p><style media="screen">.p { background: #000; }</style><p>World!</p>', 50, "Hello\n\nWorld!"),
            array('<p>Hello</p><STYLE>.p { background: #000; }</STYLE><p>World!</p>', 50, "Hello\n\nWorld!"),

            array('<p>Hello</p><script>alert("Hello world!");</script><p>World!</p>', 50, "Hello\n\nWorld!"),
            array('<p>Hello</p><script src="http://google.com">alert("Hello world!");</script><p>World!</p>', 50, "Hello\n\nWorld!"),
            array('<p>Hello</p><SCRIPT>alert("Hello world!");</SCRIPT><p>World!</p>', 50, "Hello\n\nWorld!"),
        );
    }

    /**
    * @dataProvider dataPlain
    **/
    public function testPlain($html, $limit, $expect)
    {
        $out = Text::plain($html, $limit);
        $this->assertEquals($expect, $out);
    }


    /**
    * Text::richtext
    **/
    public static function dataRichtext()
    {
        return array(
            array("Hello world", '<p>Hello world</p>'),
            array("Hello\nworld", '<p>Hello<br>world</p>'),
            array("Hello\r\nworld", '<p>Hello<br>world</p>'),
            array("Hello\rworld", '<p>Hello<br>world</p>'),
            array("Hello & world", '<p>Hello &amp; world</p>'),
            array("Hello <b> world", '<p>Hello &lt;b&gt; world</p>'),
            array("Hello &\n& world", '<p>Hello &amp;<br>&amp; world</p>'),
            array("Hello &\n\n& world", '<p>Hello &amp;<br><br>&amp; world</p>'),
            array(42, '<p>42</p>'),
        );
    }


    /**
    * Text::richtext
    * @dataProvider dataRichtext
    **/
    public function testRichtext($text, $expect)
    {
        $out = Text::richtext($text);
        $this->assertEquals($expect, $out);
    }


    /**
    * Text::richtext with a block tag specified
    **/
    public static function dataRichtextBlockTag()
    {
        return array(
            array("Hello world", 'p', '<p>Hello world</p>'),
            array("Hello\nworld", 'div', '<div>Hello<br>world</div>'),
            array("Hello\nworld", 'DIV', '<div>Hello<br>world</div>'),
            array("Hello\nworld", 'DiV', '<div>Hello<br>world</div>'),
            array("Hello world", '', 'Hello world'),
            array("Hello\nworld", '', 'Hello<br>world'),
            array("Hello world", null, 'Hello world'),
            array("Hello\nworld", null, 'Hello<br>world'),
        );
    }


    /**
    * Text::richtext with a block tag specified
    * @dataProvider dataRichtextBlockTag
    **/
    public function testRichtextBlockTag($text, $tag, $expect)
    {
        $out = Text::richtext($text, $tag);
        $this->assertEquals($expect, $out);
    }


    /**
    * lower_case -> CamelCaps
    **/
    public static function dataLcToCamelCaps()
    {
        return array(
            array('', ''),
            array('word', 'Word'),
            array('Word', 'Word'),
            array('one_two', 'OneTwo'),
            array('word_1', 'Word1'),
            array('1_word', '1Word'),
        );
    }

    /**
    * @dataProvider dataLcToCamelCaps
    **/
    public function testLcToCamelCaps($in, $expect)
    {
        $this->assertEquals($expect, Text::lc2camelcaps($in));
    }

    /**
    * lower_case -> camelCase
    **/
    public static function dataLcToCamelCase()
    {
        return array(
            array('', ''),
            array('word', 'word'),
            array('Word', 'word'),
            array('one_two', 'oneTwo'),
            array('word_1', 'word1'),
            array('1_word', '1Word'),
        );
    }

    /**
    * @dataProvider dataLcToCamelCase
    **/
    public function testLcToCamelCase($in, $expect)
    {
        $this->assertEquals($expect, Text::lc2camelcase($in));
    }

    /**
    * camelCase -> lower_case
    **/
    public static function dataCamelToLc()
    {
        return array(
            array('', ''),
            array('word', 'word'),
            array('Word', 'word'),
            array('OneTwo', 'one_two'),
            array('Word1', 'word_1'),
            array('1Word', '1_word'),
            array('word1', 'word_1'),
        );
    }

    /**
    * @dataProvider dataCamelToLc
    **/
    public function testCamelToLc($in, $expect)
    {
        $this->assertEquals($expect, Text::camel2lc($in));
    }


    public static function dataLimitedSubsetHtml()
    {
        return [
            ['', ''],
            ['Test', 'Test'],
            ['Test<ul>Dots</ul>', 'TestDots'],
            ['Test<ul>Dots</ul>Test', 'TestDotsTest'],
            ['Test<b>Bold</b>Test', 'Test<b>Bold</b>Test'],
            ['Test > Test', 'Test &gt; Test'],
            ['Test < Test', 'Test &lt; Test'],
            ['Test " Test', 'Test &quot; Test'],
            ['Test b> Test', 'Test b&gt; Test'],
            ['Test <b Test', 'Test &lt;b Test'],
            ['Test </b Test', 'Test &lt;/b Test'],
            ['Test <a href="xx">Link</a> Test', 'Test <a href="xx">Link</a> Test'],
        ];
    }

    /**
    * @dataProvider dataLimitedSubsetHtml
    **/
    public function testLimitedSubsetHtml($in, $expect)
    {
        $this->assertEquals($expect, Text::limitedSubsetHtml($in));
    }


    public static function dataContainsFormTag()
    {
        return [
            ['', false],
            ['<p>form</p>', false],
            ['<form action="xx">', true],
            ['<form<form yep>', true],
            ['<FORM action="xx">', true],
            ['<script>console.log("<form>");</script>', false],
            ['<script type="text/javascript">console.log("<form>");</script>', false],
            ['<script>*:after { content: "<form>" }</script>', false],
            ['<style type="text/javascript">*:after { content: "<form>" }</style>', false],
        ];
    }

    /**
    * @dataProvider dataContainsFormTag
    **/
    public function testContainsFormTag($in, $expect)
    {
        $this->assertEquals($expect, Text::containsFormTag($in));
    }
}
