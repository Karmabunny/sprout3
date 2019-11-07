<?php
/*
 * Copyright (C) 2018 Karmabunny Pty Ltd.
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

use DOMDocument;
use DOMXPath;
use Kohana;

use DaveChild\TextStatistics\Maths;
use DaveChild\TextStatistics\Syllables as Syllables;
use DaveChild\TextStatistics\Text as TextDC;
use Sprout\Helpers\Inflector;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\View;


/**
 * Provide Search Engine Optimisation functionality
 */
class AdminSeo
{
    public static $content = '';
    public static $extra_links = [];
    public static $dom = '';
    public static $topic = '';
    public static $slug = '';
    public static $seo_problems = [];
    public static $seo_improvements = [];
    public static $seo_considerations = [];
    public static $seo_goodresults = [];


    /**
     * Add main content for later processing
     *
     * @param string $str HTML
     * @return void
     */
    public static function addContent($str)
    {
        self::$content .= ' ' . $str;
    }


    /**
     * Add external links to inject into content analysis
     *
     * @param array $links [href, text] pairs
     * @return void
     */
    public static function addLinks($links)
    {
        self::$extra_links = $links;
    }


    /**
     * Set topic (focus word) for analysis
     *
     * @param string $str Word or words as the main topic
     * @return void
     */
    public static function setTopic($str)
    {
        $str = TextDC::cleanText($str);
        $str = trim(strtolower($str));
        self::$topic = str_replace('.','',$str);
    }


    /**
     * Set page slug for analysis
     *
     * @param string $str The front-end URL for current edited page
     * @return void
     */
    public static function setSlug($str)
    {
        $str = urldecode(trim(strtolower($str)));
        self::$slug = $str;
    }


    /**
     * Return list of useful keywords from given string
     *
     * @param bool $all True to include stop-words. Default of false (remove stop words)
     * @return array List of words
     */
    public static function processString($all = false)
    {
        $all = (bool) $all;

        $str = self::$content;
        $str = str_replace('&nbsp;', ' ', trim(strtolower($str)));
        $str = TextDC::cleanText($str, 0);

        if (!$all) {
            $expr = '/\b(' . implode(Kohana::config('admin_seo.stop_words'), '|') . ')\b/i';
            $str = preg_replace($expr, '', $str);
            $str = preg_replace('/[0-9]/', '', $str);
            $str = trim($str);
        }

        $words = preg_split("/[^\w]/", $str);
        $words = array_filter($words);
        sort($words);

        return $words;
    }


    /**
     * Setup content as DOM object
     *
     * @return void Sets class var directly
     */
    public static function processDOM()
    {
        if (!empty(self::$dom)) return;
        self::$dom = new DOMDocument();
        self::$dom->loadHTML(self::$content);
    }


    /**
     * Return list of keyword density
     *
     * @param string $str HTML to be processed
     * @param int $limit Number of results. Default of top five words
     * @return array [word => count] pairs
     */
    public static function getKeywordDensity($limit = 5)
    {
        $limit = (int) $limit;
        if ($limit <= 0 || $limit > 999) $limit = 5;

        $words = self::processString();
        $list = [];

        foreach ($words as $word) {
            if (empty($list[$word])) $list[$word] = 0;
            $list[$word] ++;
        }

        // Order largest to smallest
        arsort($list);

        // Cap at given limit
        while (count($list) > $limit) {
            array_pop($list);
        }

        return $list;
    }


    /**
     * Returns the average word count per section
     *
     * @return int Average words
     */
    public static function getWordCountPerSection()
    {
        self::processDOM();
    }


    /**
     * Return list of all links
     *
     * @return array List of URLs
     */
    public static function getListOfLinks()
    {
        self::processDOM();

        $list = [];
        $links = self::$dom->getElementsByTagName("a");
        foreach($links as $link) {
            $href =  $link->getAttribute("href");
            $text = trim(preg_replace("/[\r\n]/", " ", $link->nodeValue));
            $list[] = [
                'href' => $href,
                'text' => $text
            ];
        }

        if (!empty(self::$extra_links)) $list = array_merge($list, self::$extra_links);

        return $list;
    }


    /**
     * Determine if given word is a stop-word
     *
     * @param string $word Word to check
     * @return bool True when is stop-word
     * @return bool False when not stop-word
     */
    public static function isStopWord($word)
    {
        $word = trim(strtolower($word));
        return in_array($word, Kohana::config('admin_seo.stop_words'));
    }


    /**
     * Determine Flesch reading score
     * 0 = hard, 100 = easy
     * Thanks to github.com/DaveChild
     *
     * @param string $str Text to score
     * @param string $encoding Encoding of text
     * @return int
     */
    public static function getFleschReadingScore($str, $encoding = '')
    {
        $str = TextDC::cleanText($str);

        $score = Maths::bcCalc(
            Maths::bcCalc(
                206.835,
                '-',
                Maths::bcCalc(
                    1.015,
                    '*',
                    TextDC::averageWordsPerSentence($str, $encoding)
                )
            ),
            '-',
            Maths::bcCalc(
                84.6,
                '*',
                Syllables::averageSyllablesPerWord($str, $encoding)
            )
        );

        return Maths::normaliseScore($score, 0, 100, 1);
    }


    /**
     * Populate SEO view with analysis
     *
     * @return string HTML view
     */
    public static function getAnalysis()
    {
        if (empty(self::$content) or TextDC::wordCount(self::$content) < 25) {
            $view = new View('sprout/admin/main_seo');
            $view->disabled = true;
            return $view->render();
        }

        self::determineReadabilityScore();
        self::determineWordCountScore();
        self::determineAverageWordScore();
        self::determineTopicWordsScore();
        self::determineSlugWordsScore();
        self::determineLinksScore();
        self::determineSectionWordScore();

        $view = new View('sprout/admin/main_seo');

        $view->keywords = self::getKeywordDensity(6);
        $view->seo_problems = self::$seo_problems;
        $view->seo_improvements = self::$seo_improvements;
        $view->seo_considerations = self::$seo_considerations;
        $view->seo_goodresults = self::$seo_goodresults;

        return $view->render();
    }


    /**
     * Determine SEO readability score
     *
     * @return void Updates result arrays directly
     */
    public static function determineReadabilityScore()
    {
        $score = self::getFleschReadingScore(self::$content);
        $ratings = Kohana::config('admin_seo.readability_scores');

        foreach ($ratings as $rating) {
            if (floor($score) > $rating['range'][0] and floor($score) <= $rating['range'][1]) {
                switch ($rating['type']) {
                    case 'good':
                        self::$seo_goodresults[] = sprintf('Readability score: %u%%. %s %s', $score, $rating['desc'], $rating['fix']);
                        break;

                    case 'problem':
                        self::$seo_problems[] = sprintf('Readability score: %u%%. %s %s', $score, $rating['desc'], $rating['fix']);
                        break;
                }
                break;
            }
        }
    }


    /**
     * Determine SEO word count score
     *
     * @return void Updates result arrays directly
     */
    public static function determineWordCountScore()
    {
        $count = TextDC::wordCount(self::$content);
        $score = Kohana::config('admin_seo.word_count');

        if ($count < $score) {
            self::$seo_improvements[] = sprintf('Content contains %u %s. This is below the recommended minimum of %u words.', $count, Inflector::plural('word', $count), $score);
        } else if ($count >= $score) {
            self::$seo_goodresults[] = sprintf('Content contains the recommended minimum of %u words', $score);
        }
    }


    /**
     * Determine SEO average word score
     *
     * @return void Updates result arrays directly
     */
    public static function determineAverageWordScore()
    {
        $avg = ceil(TextDC::averageWordsPerSentence(self::$content));
        $words = Kohana::config('admin_seo.average_words_sentence');

        if ($avg < $words) {
            self::$seo_goodresults[] = sprintf('Your sentences contain an average of %u words. Aiming for average maximum of %u.', $avg, $words);
        } else {
            self::$seo_considerations[] = sprintf('Your sentences contain an average of %u words. Aim for an average maximum of %u words.', $avg, $words);
        }
    }


    /**
     * Determine SEO topic keywords score
     *
     * @return void Updates result arrays directly
     */
    public static function determineTopicWordsScore()
    {
        if (empty(self::$topic)) return;

        $keywords = self::getKeywordDensity(6);
        $words = explode(' ', self::$topic);
        $topic = false;
        $stopwords = false;
        $count = 0;

        foreach ($words as $word) {
            if (self::isStopWord($word)) $stopwords = true;
            if (isset($keywords[$word])) {
                $topic = true;
                $count ++;
            }
        }

        if ($topic) {
            self::$seo_goodresults[] = sprintf('Keywords appear in topic "%s" %u %s.', self::$topic, $count, Inflector::plural('time', $count));;
        } else {
            self::$seo_improvements[] = sprintf('Keywords do not appear in your topic "%s".', self::$topic);
        }

        if ($stopwords) {
            self::$seo_considerations[] = sprintf('Your topic "%s" contains <a href="https://en.wikipedia.org/wiki/Stop_words" target="_blank">stop words</a>. This may or may not be wise depending on the circumstances.', self::$topic);
        }
    }


    /**
     * Determine SEO slug stopwords score
     *
     * @return void Updates result arrays directly
     */
    public static function determineSlugWordsScore()
    {
        if (empty(self::$slug)) return;

        $stopword = false;
        $keyword = false;
        $kwords = self::getKeywordDensity(6);
        $slug_words = preg_split('~[/ ]~', self::$slug);

        foreach ($slug_words as $slug_word) {
            if (self::isStopWord($slug_word)) $stopword = true;
            if (in_array($slug_word, $kwords)) $keyword = true;
        }

        if (!$keyword) {
            self::$seo_improvements[] = 'Keywords do not appear in your URL slug.';
        }

        if ($stopword) {
            self::$seo_considerations[] = 'The URL slug contains <a href="https://en.wikipedia.org/wiki/Stop_words" target="_blank">stop words</a>. This may or may not be wise depending on the circumstances.';
        }

        // Topic in slug
        if (empty(self::$topic) or empty($slug_words)) return;

        $topic_words = explode(' ', self::$topic);
        $topic = false;

        foreach ($topic_words as $topic_word) {
            if (in_array($topic_word, $slug_words)) $topic = true;
        }

        if ($topic) {
            self::$seo_goodresults[] = sprintf('Topic "%s" appears in the URL slug.', self::$topic);
        } else {
            self::$seo_improvements[] = sprintf('Topic "%s" doesn\'t appear in the URL slug.', self::$topic);
        }
    }


    /**
     * Determines SEO links score
     *
     * @return void Updates result arrays directly
     */
    public static function determineLinksScore()
    {
        $links = self::getListOfLinks();

        if (count($links) == 0) {
            self::$seo_considerations[] = 'Content contains no links. Try linking to other pages within your site of related content.';
            return;
        }

        // Determine internal links
        $internal = false;
        $read_more = false;
        foreach ($links as $link) {
            // Determine if "read more" link label
            if (in_array(
                TextDC::cleanText(TextDC::lowerCase(str_replace(['.', '-'], '', $link['text']))),
                ['more', 'read more', 'view more'])
            ) $read_more = true;

            if (strpos($link['href'], Sprout::absRoot()) !== false) {
                $internal = true;
            } else if (strpos($link['href'], 'http') === false)  {
                $internal = true;
            }
        }

        // No internal links
        if (!$internal) {
            self::$seo_considerations[] = 'Try linking to pages of related topics within your site.';
        }

        // Generic link labels
        if ($read_more) {
            self::$seo_problems[] = 'Avoid generic "read more" link labels. Give labels that help users confidently predict what the next page will be.';
        }
    }


    /**
     * Determine SEO word count per section score
     *
     * @return void Updates result arrays directly
     */
    public static function determineSectionWordScore()
    {
        self::processDOM();
        $xpath = new DOMXPath(self::$dom);

        // Bold as headings
        $false_headings = $xpath->query("//p/strong");
        foreach ($false_headings as $heading) {
            if ($heading->previousSibling === null and $heading->nextSibling === null) {
                self::$seo_problems[] = 'Avoid using Bold styling as headings. Use heading styles: <strong>H</strong> buttons in the tool-bar.';
                break;
            }
        }

        // No headings
        $headings = $xpath->query("//h1|//h2|//h3|//h4");
        if ($headings->length == 0) {
            self::$seo_considerations[] = 'Use headings to break your content into sections for easier reading.';
            return;
        }

        // Content between headings (sections)
        $contents = [];
        $sections = 0;

        $elems = $xpath->query('//body/*');
        foreach ($elems as $elem) {
            if (empty($contents[$sections])) $contents[$sections] = '';

            // Not a heading, concat text to make up a "section"
            if (!in_array($elem->tagName, ['h1','h2','h3','h4'])) {
                $contents[$sections] .= $elem->nodeValue;
            } else {
                $sections ++;
            }
        }

        // Count words per section
        $count = 0;
        foreach ($contents as &$content) {
            $content = TextDC::cleanText($content);
            $words = TextDC::wordCount($content);
            if ($words > $count) $count = $words;
        }

        // Check if above recommended maximum
        $score = Kohana::config('admin_seo.word_count');
        if ($count >= $score) {
            self::$seo_improvements[] = sprintf('Content between headings contains %u %s. This is above the recommended maximum of %u words per section.', $count, Inflector::plural('word', $count), $score);
        }
    }
}
