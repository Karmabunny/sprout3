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


    /**
     * Request YouTube playlist data for given URL
     *
     * @param string $video_url Playlist URL
     * @return array List of key-value pairs of [id, thumb_url, description, title]
     * @return bool False on error
     */
    private static function requestYoutubePlaylist($video_url)
    {
        $url_query = parse_url($video_url, PHP_URL_QUERY);
        $url_params = [];
        parse_str($url_query, $url_params);

        // Validate given URL
        if (empty($url_params['list'])) {
            Notification::error('Unable to determine YouTube playlist from given URL');
            return false;
        }

        // Validate API key
        if (empty(Kohana::config('sprout.google_youtube_api')) or Kohana::config('sprout.google_youtube_api') == 'please_generate_me') {
            Notification::error('Please configure your Google API key for YouTube videos');
            return false;
        }

        // Build the request URL
        $params = [];
        $params['part'] = 'snippet';
        $params['maxResults'] = 50;
        $params['playlistId'] = Enc::url($url_params['list']);
        $params['key'] = Enc::url(Kohana::config('sprout.google_youtube_api'));

        $url = 'https://www.googleapis.com/youtube/v3/playlistItems?' . http_build_query($params);

        // Do the request
        $playlist = HttpReq::get($url);
        if (!$playlist) {
            Notification::error('Unable to connect to YouTube API');
            return false;
        }

        // Decode results
        $playlist = @json_decode($playlist);
        if (!$playlist) {
            Notification::error('Unable to decode data from YouTube API');
            return false;
        }

        // Validate API request
        if (isset($playlist->error)) {
            Notification::error('YouTube API error: ' . $playlist->error->code . ' ' . $playlist->error->message);
            return false;
        }

        // Build results as array
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
