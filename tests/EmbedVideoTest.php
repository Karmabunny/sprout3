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
use Sprout\Helpers\EmbedVideo;


class EmbedVideoTest extends TestCase
{

    public function testGetVideoIdType()
    {
        // Youtube
        $out = EmbedVideo::getVideoIdType('http://www.youtube.com/watch?v=YP31r70_QNM&feature=grec_index');
        $this->assertTrue($out[0] == EmbedVideo::TYPE_YOUTUBE);
        $this->assertTrue($out[1] == 'YP31r70_QNM');

        $out = EmbedVideo::getVideoIdType('http://youtu.be/YP31r70_QNM');
        $this->assertTrue($out[0] == EmbedVideo::TYPE_YOUTUBE);
        $this->assertTrue($out[1] == 'YP31r70_QNM');

        $out = EmbedVideo::getVideoIdType('https://youtu.be/YP31r70_QNM');
        $this->assertTrue($out[0] == EmbedVideo::TYPE_YOUTUBE);
        $this->assertTrue($out[1] == 'YP31r70_QNM');

        $out = EmbedVideo::getVideoIdType('http://www.youtube.com/watch?v=c6jWWfQJt78&feature=grec_index');
        $this->assertTrue($out[0] == EmbedVideo::TYPE_YOUTUBE);
        $this->assertTrue($out[1] == 'c6jWWfQJt78');

        $out = EmbedVideo::getVideoIdType('http://www.youtube.com/watch?feature=grec_index&v=c6jWWfQJt78');
        $this->assertTrue($out[0] == EmbedVideo::TYPE_YOUTUBE);
        $this->assertTrue($out[1] == 'c6jWWfQJt78');

        $out = EmbedVideo::getVideoIdType('http://youtube.com/watch?v=c6jWWfQJt78');
        $this->assertTrue($out[0] == EmbedVideo::TYPE_YOUTUBE);
        $this->assertTrue($out[1] == 'c6jWWfQJt78');

        $out = EmbedVideo::getVideoIdType('http://youtube.com/watch?v=c6jWWfQJt78');
        $this->assertTrue($out[0] == EmbedVideo::TYPE_YOUTUBE);
        $this->assertTrue($out[1] == 'c6jWWfQJt78');

        $out = EmbedVideo::getVideoIdType('http://youtu.be/c6jWWfQJt78');
        $this->assertTrue($out[0] == EmbedVideo::TYPE_YOUTUBE);
        $this->assertTrue($out[1] == 'c6jWWfQJt78');

        $out = EmbedVideo::getVideoIdType('https://youtu.be/c6jWWfQJt78');
        $this->assertTrue($out[0] == EmbedVideo::TYPE_YOUTUBE);
        $this->assertTrue($out[1] == 'c6jWWfQJt78');

        $out = EmbedVideo::getVideoIdType('http://youtu.be/dK-Wy2NhO8o');
        $this->assertTrue($out[0] == EmbedVideo::TYPE_YOUTUBE);
        $this->assertTrue($out[1] == 'dK-Wy2NhO8o');

        $out = EmbedVideo::getVideoIdType('http://www.youtube.com/v/4_gMar4_Jhg?version=3&f=videos&app=youtube_gdata&rel=0');
        $this->assertTrue($out[0] == EmbedVideo::TYPE_YOUTUBE);
        $this->assertTrue($out[1] == '4_gMar4_Jhg');

        $out = EmbedVideo::getVideoIdType('https://www.youtube.com/v/4_gMar4_Jhg?version=3&f=videos&app=youtube_gdata&rel=0');
        $this->assertTrue($out[0] == EmbedVideo::TYPE_YOUTUBE);
        $this->assertTrue($out[1] == '4_gMar4_Jhg');

        $out = EmbedVideo::getVideoIdType('http://www.youtube.com/user/inyaka');
        $this->assertNull($out);

        $out = EmbedVideo::getVideoIdType('http://youtube.com/user/inyaka');
        $this->assertNull($out);


        // Vimeo
        $out = EmbedVideo::getVideoIdType('http://vimeo.com/6745866');
        $this->assertTrue($out[0] == EmbedVideo::TYPE_VIMEO);
        $this->assertTrue($out[1] == '6745866');

        $out = EmbedVideo::getVideoIdType('https://vimeo.com/6745866');
        $this->assertTrue($out[0] == EmbedVideo::TYPE_VIMEO);
        $this->assertTrue($out[1] == '6745866');

        $out = EmbedVideo::getVideoIdType('http://vimeo.com/');
        $this->assertNull($out);

        $out = EmbedVideo::getVideoIdType('http://vimeo.com/channels/staffpicks#13906628');
        $this->assertNull($out);


        // other
        $out = EmbedVideo::getVideoIdType('http://thejosh.info');
        $this->assertNull($out);

        $out = EmbedVideo::getVideoIdType('abcde');
        $this->assertNull($out);

        $out = EmbedVideo::getVideoIdType(array());
        $this->assertNull($out);
    }


    public function testRenderEmbed()
    {
        // Three video urls
        $out = EmbedVideo::renderEmbed('http://www.youtube.com/watch?v=YP31r70_QNM&feature=grec_index');
        $this->assertStringContainsString('<iframe', $out);
        $this->assertStringContainsString('youtube', $out);
        $this->assertStringContainsString('400', $out);
        $this->assertStringContainsString('300', $out);

        $out = EmbedVideo::renderEmbed('http://youtu.be/YP31r70_QNM');
        $this->assertStringContainsString('<iframe', $out);
        $this->assertStringContainsString('youtube', $out);
        $this->assertStringContainsString('400', $out);
        $this->assertStringContainsString('300', $out);

        $out = EmbedVideo::renderEmbed('http://vimeo.com/6745866');
        $this->assertStringContainsString('<iframe', $out);
        $this->assertStringContainsString('vimeo', $out);
        $this->assertStringContainsString('400', $out);
        $this->assertStringContainsString('300', $out);

        $out = EmbedVideo::renderEmbed('https://vimeo.com/6745866');
        $this->assertStringContainsString('<iframe', $out);
        $this->assertStringContainsString('vimeo', $out);
        $this->assertStringContainsString('400', $out);
        $this->assertStringContainsString('300', $out);


        // Invalid urls
        $out = EmbedVideo::renderEmbed('http://thejosh.info');
        $this->assertNull($out);

        $out = EmbedVideo::renderEmbed(array());
        $this->assertNull($out);


        // Sizes
        $out = EmbedVideo::renderEmbed('http://youtu.be/YP31r70_QNM', 500, 1000);
        $this->assertStringContainsString('500', $out);
        $this->assertStringContainsString('1000', $out);
    }


    public function testRenderEmbedAutoplay()
    {
        $out = EmbedVideo::renderEmbed('https://vimeo.com/6745866', 500, 1000);
        $this->assertStringNotContainsString('autoplay=1', $out);

        $out = EmbedVideo::renderEmbed('https://vimeo.com/6745866', 500, 1000, array());
        $this->assertStringNotContainsString('autoplay=1', $out);

        $out = EmbedVideo::renderEmbed('https://vimeo.com/6745866', 500, 1000, array('autoplay' => false));
        $this->assertStringNotContainsString('autoplay=1', $out);

        $out = EmbedVideo::renderEmbed('https://vimeo.com/6745866', 500, 1000, array('autoplay' => true));
        $this->assertStringContainsString('autoplay=1', $out);
    }


    public function testRenderEmbedTitle()
    {
        $out = EmbedVideo::renderEmbed('https://vimeo.com/6745866', 500, 1000);
        $this->assertStringContainsString('title="Embedded video"', $out);

        $out = EmbedVideo::renderEmbed('https://vimeo.com/6745866', 500, 1000, array('title' => 'test'));
        $this->assertStringContainsString('title="test"', $out);

        $out = EmbedVideo::renderEmbed('https://vimeo.com/6745866', 500, 1000, array('title' => null));
        $this->assertStringContainsString('title="Embedded video"', $out);

        $out = EmbedVideo::renderEmbed('https://vimeo.com/6745866', 500, 1000, array('title' => ''));
        $this->assertStringContainsString('title="Embedded video"', $out);
    }


    /**
    * @slow
    **/
    public function testGetThumbFilename()
    {
        $out = EmbedVideo::getThumbFilename('http://www.youtube.com/watch?v=YP31r70_QNM&feature=grec_index');
        if ($out !== null) {
            $this->assertMatchesRegularExpression('!^//!', $out);
            $this->assertStringContainsString('ytimg', $out);
            $size = @getimagesize($out);
            $this->assertTrue($size !== null);
        }

        $out = EmbedVideo::getThumbFilename('http://youtu.be/YP31r70_QNM');
        if ($out !== null) {
            $this->assertMatchesRegularExpression('!^//!', $out);
            $this->assertStringContainsString('ytimg', $out);
            $size = @getimagesize($out);
            $this->assertTrue($size !== null);
        }

        $out = EmbedVideo::getThumbFilename('http://vimeo.com/6745866');
        if ($out !== null) {
            $this->assertMatchesRegularExpression('!^https?://!', $out);
            $this->assertStringContainsString('vimeo', $out);
            $size = @getimagesize($out);
            $this->assertTrue($size !== null);
        }

        $out = EmbedVideo::getThumbFilename('dsfsjfsfbnsfbshjfb');
        $this->assertNull($out);
    }
}
