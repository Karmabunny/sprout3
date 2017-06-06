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

namespace Sprout\Widgets;

use Kohana;

use Sprout\Exceptions\ValidationException;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
use Sprout\Helpers\HttpReq;
use Sprout\Helpers\Notification;
use Sprout\Helpers\View;


/**
 * Renders a gallery of videos
 */
class VideoPlaylistWidget extends Widget
{
    protected $friendly_name = "Video gallery";
    protected $friendly_desc = 'YouTube play-list gallery';
    public $classname = "VideoPlaylist";


    /**
     * Renders the front-end view of this widget
     *
     * @param int $orientation The orientation of the widget.
     * @return string HTML
     */
    public function render($orientation)
    {
        if (empty($this->settings['playlist_id'])) return;
        $this->settings['captions'] = (int) @$this->settings['captions'];
        $this->settings['thumb_rows'] = (int) @$this->settings['thumb_rows'];
        $videos = $this->requestYoutubePlaylist($this->settings['playlist_id']);

        if ($this->settings['thumb_rows'] <= 0) {
            $this->settings['thumb_rows'] = 4;
        }

        $view = new View('sprout/video_playlist');
        $view->videos = $videos;
        $view->captions = $this->settings['captions'];
        $view->thumb_rows = $this->settings['thumb_rows'];

        return $view->render();
    }


    /**
     * Renders the admin settings form for this widget
     *
     * @return string HTML
     */
    public function getSettingsForm()
    {
        $this->settings['captions'] = (int) @$this->settings['captions'];
        $this->settings['thumb_rows'] = (int) @$this->settings['thumb_rows'];

        if ($this->settings['thumb_rows'] <= 0) {
            $this->settings['thumb_rows'] = 4;
        }

        $view = new View('sprout/video_playlist_settings');
        $view->settings = $this->settings;
        $view->thumbs = [1=>1,2=>2,3=>3,4=>4];

        return $view->render();
    }



    private static function requestYoutubePlaylist($video_id)
    {
        $url = parse_url($video_id, PHP_URL_QUERY);
        parse_str($url, $vars);

        if (!empty($vars['list'])) {
            $video_id = $vars['list'];
        }

        if (!empty(Kohana::config('sprout.google_youtube_api')) and Kohana::config('sprout.google_youtube_api') == 'please_generate_me') {
            Notification::error('Please configure your Google API key for YouTube videos');
            return false;
        } else if (!empty(Kohana::config('sprout.google_youtube_api'))) {
            $key = Kohana::config('sprout.google_youtube_api');
        }

        $url = 'https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=50&playlistId=' . Enc::html($video_id);

        if (!empty($key)) {
            $url .= '&key=' . Enc::html($key);
        }

        $playlist = HttpReq::get($url);

        if (!$playlist) {
            Notification::error('Unable to connect to YouTube API');
            return false;
        }

        $playlist = @json_decode($playlist);
        if (!$playlist) {
            Notification::error('Unable to decode data from YouTube API');
            return false;
        }

        if (isset($playlist->error)) {
            Notification::error('YouTube API error: ' . $playlist->error->code . ' ' . $playlist->error->message);
            return false;
        }

        $videos = [];
        foreach ($playlist->items as $video) {
            $snippet = $video->snippet;

            if (isset($snippet->thumbnails->standard)) {
                $thumb = $snippet->thumbnails->standard;
            } else if (isset($snippet->thumbnails->high)) {
                $thumb = $snippet->thumbnails->high;
            } else if (isset($snippet->thumbnails->medium)) {
                $thumb = $snippet->thumbnails->medium;
            } else {
                continue;
            }

            $video = [];
            $video['id'] = $snippet->resourceId->videoId;
            $video['thumb_url'] = $thumb->url;
            $video['description'] = $snippet->description;
            $video['title'] = $snippet->title;

            $videos[] = $video;
        }

        return $videos;
    }
}
