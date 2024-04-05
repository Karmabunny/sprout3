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

namespace Sprout\Controllers;

use Exception;

use Kohana;

use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\AdminPerms;
use Sprout\Helpers\Category;
use Sprout\Helpers\Enc;
use Sprout\Helpers\File;
use Sprout\Helpers\FileConstants;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Register;
use Sprout\Helpers\RteLibContainer;
use Sprout\Helpers\RteLibObject;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\PhpView;


/**
* Link library, image library, etc
**/
class Tinymce4Controller extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }


    /**
    * Initialises a new search toolbar
    *
    * @param string $type 'image' or 'library'
    **/
    protected static function initToolbar($type)
    {
        if (!in_array($type, array('image', 'video', 'library'))) {
            throw new Exception("Must be 'image', 'video', or 'library'");
        }

        $view = new PhpView("sprout/tinymce4/toolbar");
        $view->is_root = false;
        $view->is_search = false;
        $view->search_url = "tinymce4/{$type}_search";
        $view->upload_url = "tinymce4/upload?type=" . $type;
        $view->reset_url = "tinymce4/{$type}";

        if ($type == 'image') {
            $view->search_label = 'Search for an image';
            $view->upload_label = 'Upload an image';
        } else if ($type == 'video') {
            $view->search_label = 'Search for a video';
            $view->upload_label = 'Upload a video';
        } else {
            $view->search_label = 'Search';
            $view->upload_label = 'Upload a file';
        }

        $view->can_upload = AdminPerms::controllerAccess('file', 'add');

        if (isset($_GET['search'])) {
            $view->search_query = $_GET['search'];
        } else {
            $view->search_query = '';
        }

        return $view;
    }


    /**
    * Show a gallery of image categories
    **/
    public function image()
    {
        AdminAuth::checkLogin();

        $cat_table = Category::tableMain2cat('files');
        $joiner_table = Category::tableMain2joiner('files');
        $q = "SELECT cat.id, cat.name, GROUP_CONCAT(file.filename ORDER BY RAND() SEPARATOR '|') AS filenames
            FROM ~{$cat_table} AS cat
            INNER JOIN ~{$joiner_table} AS joiner ON joiner.cat_id = cat.id
            INNER JOIN ~files AS file ON joiner.file_id = file.id
            WHERE file.name != '' AND file.type = ?
            GROUP BY cat.id
            ORDER BY cat.name
            LIMIT 500";
        $res = Pdb::q($q, [FileConstants::TYPE_IMAGE], 'pdo');

        $view = new PhpView('sprout/tinymce4/image_cat');
        $view->toolbar = self::initToolbar('image');
        $view->toolbar->is_root = true;
        $view->categories = $res;

        $outer = new PhpView('sprout/tinymce4/outer');
        $outer->main_content = $view->render();
        $outer->page_title = 'Insert image - choose category';

        $res->closeCursor();

        echo $outer->render();
    }


    /**
    * Show a gallery of images for a given category
    **/
    public function imageList($category_id)
    {
        AdminAuth::checkLogin();
        $category_id = (int) $category_id;

        $cat_name = Category::name('files', $category_id);

        $cat_table = Category::tableMain2cat('files');
        $joiner_table = Category::tableMain2joiner('files');
        $q = "SELECT file.id, file.name, file.filename
            FROM ~files AS file
            INNER JOIN ~{$joiner_table} AS joiner ON joiner.file_id = file.id
            WHERE joiner.cat_id = ? AND file.name != '' AND file.type = ?
            GROUP BY file.id
            ORDER BY file.name
            LIMIT 500";
        $res = Pdb::q($q, [$category_id, FileConstants::TYPE_IMAGE], 'pdo');

        $view = new PhpView('sprout/tinymce4/image_list');
        $view->toolbar = self::initToolbar('image');
        $view->images = $res;
        $view->up_url = 'SITE/tinymce4/image';
        $view->gallery_name = $cat_name;
        $view->link_attrs = array('category_id' => $category_id);

        $outer = new PhpView('sprout/tinymce4/outer');
        $outer->main_content = $view->render();
        $outer->page_title = 'Insert image - ' . $cat_name;

        $res->closeCursor();

        echo $outer->render();
    }


    /**
    * Show image search results
    **/
    public function imageSearch()
    {
        AdminAuth::checkLogin();
        $key = trim($_GET['search'] ?? '');
        $safe_key = Pdb::likeEscape($key);

        $q = "SELECT id, name, filename
            FROM ~files
            WHERE type = ?
                AND (name LIKE CONCAT('%', ?, '%') OR filename LIKE CONCAT('%', ?, '%'))
            ORDER BY name
            LIMIT 200";
        $res = Pdb::q($q, [FileConstants::TYPE_IMAGE, $safe_key, $safe_key], 'pdo');

        $view = new PhpView('sprout/tinymce4/image_list');
        $view->toolbar = self::initToolbar('image');
        $view->toolbar->is_search = true;
        $view->images = $res;
        $view->search_key = $key;
        $view->link_attrs = array('search' => $key);

        $outer = new PhpView('sprout/tinymce4/outer');
        $outer->main_content = $view->render();
        $outer->page_title = 'Insert image';

        $res->closeCursor();

        echo $outer->render();
    }


    /**
    * Show the various sizes to choose from
    **/
    public function imageSize($file_id)
    {
        AdminAuth::checkLogin();
        $file_id = (int) $file_id;
        $_GET['category_id'] = (int) @$_GET['category_id'];
        $_GET['search'] = (string) @$_GET['search'];

        // Grab file info
        $q = "SELECT id, name, filename FROM ~files WHERE id = ?";
        $row = Pdb::q($q, [$file_id], 'row');

        // Prep links for the various sizes
        $sizes = array();
        $transforms = Kohana::config('file.image_transformations');

        // Exclude transforms that don't actually exist, e.g. if the original image is smaller than certain sizes
        foreach ($transforms as $key => $resolution) {
            $original = $row['filename'];
            $resize = File::getNoext($original) . ".{$key}." . File::getExt($original);
            if (!File::exists($resize)) {
                unset($transforms[$key]);
            }
        }

        // The UP url
        $up_url = 'SITE/tinymce4/image';
        if (!empty($_GET['category_id'])) {
            $up_url = 'SITE/tinymce4/image_list/' . $_GET['category_id'];
        } else if (!empty($_GET['search'])) {
            $up_url = 'SITE/tinymce4/image_search?search=' . Enc::url($_GET['search']);
        }

        $view = new PhpView('sprout/tinymce4/image_size');
        $view->toolbar = self::initToolbar('image');
        $view->image = $row;
        $view->sizes = array_keys($transforms);
        $view->up_url = $up_url;

        $outer = new PhpView('sprout/tinymce4/outer');
        $outer->main_content = $view->render();
        $outer->page_title = 'Insert image - ' . $row['name'];
        echo $outer->render();
    }


    /**
    * Show a list of RTE libraries
    **/
    public function library()
    {
        AdminAuth::checkLogin();

        $libraries = array();
        foreach (Register::getRteLibraries() as $class_name) {
            $inst = Sprout::instance($class_name, ['Sprout\\Helpers\\RteLibrary']);

            $url = 'SITE/tinymce4/library_browse/' . Enc::url($class_name) . '?path=';

            $libraries[$inst->getName()] = array(
                'url' => $url,
                'name' => $inst->getName(),
            );
        }

        $view = new PhpView('sprout/tinymce4/library_list');
        $view->toolbar = self::initToolbar('library');
        $view->toolbar->is_root = true;
        $view->libraries = $libraries;

        $outer = new PhpView('sprout/tinymce4/outer');
        $outer->main_content = $view->render();
        $outer->page_title = 'Insert link';
        echo $outer->render();
    }


    /**
    * Search rte libraries
    **/
    public function librarySearch()
    {
        AdminAuth::checkLogin();

        $key = trim($_GET['search'] ?? '');

        $class_names = Register::getRteLibraries();
        if (isset($_GET['lib']) and in_array($_GET['lib'], $class_names)) {
            $class_names = array($_GET['lib']);
        }

        $objects = array();
        foreach ($class_names as $class_name) {
            $inst = Sprout::instance($class_name, ['Sprout\\Helpers\\RteLibrary']);

            try {
                $res = $inst->search($key);
            } catch (Exception $ex) {
                continue;
            }

            $objects = array_merge($objects, $res);
        }

        $view = new PhpView('sprout/tinymce4/library_search');
        $view->toolbar = self::initToolbar('library');
        $view->toolbar->is_search = true;
        $view->objects = $objects;

        $outer = new PhpView('sprout/tinymce4/outer');
        $outer->main_content = $view;

        if (isset($_GET['lib'])) {
            $outer->page_title = 'Insert link - ' . $inst->getName() . ' - Search';
            $view->library_name = $inst->getName();
            $view->toolbar->search_params = array('lib' => $_GET['lib']);
            $view->toolbar->reset_url = 'tinymce4/library_browse/' . $_GET['lib'];
        } else {
            $outer->page_title = 'Insert link - Search';
            $view->library_name = null;
        }

        echo $outer->render();
    }


    /**
    * Browse an RTE library
    *
    * @param string $class_name The RTE library to browse
    * @get string path The path to browse
    **/
    public function libraryBrowse($class_name)
    {
        AdminAuth::checkLogin();

        $class_name = trim($class_name);
        if (!in_array($class_name, Register::getRteLibraries())) {
            throw new Exception('Invalid library class');
        }

        $inst = Sprout::instance($class_name, 'Sprout\\Helpers\\RteLibrary');
        $res = $inst->browse($_GET['path'] ?? '');

        // Split into two arrays
        $objects = array();
        $containers = array();
        foreach ($res as $obj) {
            if ($obj instanceof RteLibObject) {
                $objects[] = $obj;

            } else if ($obj instanceof RteLibContainer) {
                $path = trim(($_GET['path'] ?? '') . '/' . $obj->getName(), '/');
                $url = 'SITE/tinymce4/library_browse/' . Enc::url($class_name) . '?path=' . Enc::url($path);

                $containers[$url] = $obj->getLabel();
            }
        }

        // The URL to go "up" a level
        $up_url = 'SITE/tinymce4/library';
        if (!empty($_GET['path'])) {
            $parts = explode('/', $_GET['path']);
            array_pop($parts);
            $up_url = 'SITE/tinymce4/library_browse/' . Enc::url($class_name) . '?path=' . Enc::url(implode('/', $parts));
        }

        $view = new PhpView('sprout/tinymce4/library_browse');
        $view->toolbar = self::initToolbar('library');
        $view->toolbar->search_url .= '&lib=' . $class_name;
        $view->objects = $objects;
        $view->containers = $containers;
        $view->up_url = $up_url;
        $view->library_name = $inst->getName();

        $outer = new PhpView('sprout/tinymce4/outer');
        $outer->main_content = $view->render();
        $outer->page_title = 'Insert link - ' . $inst->getName();
        echo $outer->render();
    }


    /**
     * Show a gallery of video categories
     */
    public function video()
    {
        AdminAuth::checkLogin();

        $cat_table = Category::tableMain2cat('files');
        $joiner_table = Category::tableMain2joiner('files');
        $q = "SELECT cat.id, cat.name, COUNT(file.id) AS num_files
            FROM ~{$cat_table} AS cat
            INNER JOIN ~{$joiner_table} AS joiner ON joiner.cat_id = cat.id
            INNER JOIN ~files AS file ON joiner.file_id = file.id
                AND file.name != '' AND file.type = ?
            GROUP BY cat.id
            ORDER BY cat.name
            LIMIT 100";
        $res = Pdb::q($q, [FileConstants::TYPE_VIDEO], 'pdo');

        $view = new PhpView('sprout/tinymce4/video_cat');
        $view->toolbar = self::initToolbar('video');
        $view->toolbar->is_root = true;
        $view->categories = $res;

        $outer = new PhpView('sprout/tinymce4/outer');
        $outer->main_content = $view->render();
        $outer->page_title = 'Insert video - choose category';

        $res->closeCursor();

        echo $outer->render();
    }


    /**
     * Show a gallery of videos for a given category
     */
    public function videoList($category_id)
    {
        AdminAuth::checkLogin();
        $category_id = (int) $category_id;

        $cat_name = Category::name('files', $category_id);

        $cat_table = Category::tableMain2cat('files');
        $joiner_table = Category::tableMain2joiner('files');
        $q = "SELECT file.id, file.name, file.filename
            FROM ~{$joiner_table} AS joiner
            INNER JOIN ~files AS file ON joiner.file_id = file.id
                 AND file.name != '' AND file.type = ?
            WHERE joiner.cat_id = ?
            GROUP BY file.id
            ORDER BY file.name
            LIMIT 500";
        $res = Pdb::q($q, [FileConstants::TYPE_VIDEO, $category_id], 'pdo');

        $view = new PhpView('sprout/tinymce4/video_list');
        $view->toolbar = self::initToolbar('video');
        $view->videos = $res;
        $view->up_url = 'SITE/tinymce4/video';
        $view->gallery_name = $cat_name;
        $view->link_attrs = ['category_id' => $category_id];

        $outer = new PhpView('sprout/tinymce4/outer');
        $outer->main_content = $view->render();
        $outer->page_title = 'Insert video - ' . $cat_name;

        $res->closeCursor();

        echo $outer->render();
    }


    /**
     * Show video search results
     */
    public function videoSearch()
    {
        AdminAuth::checkLogin();
        $key = trim($_GET['search'] ?? '');
        $safe_key = Pdb::likeEscape($key);

        $q = "SELECT id, name, filename
            FROM ~files
            WHERE type = ?
                AND (name LIKE CONCAT('%', ?, '%') OR filename LIKE CONCAT('%', ?, '%'))
            ORDER BY name
            LIMIT 200";
        $res = Pdb::q($q, [FileConstants::TYPE_VIDEO, $safe_key, $safe_key], 'pdo');

        $view = new PhpView('sprout/tinymce4/video_list');
        $view->toolbar = self::initToolbar('video');
        $view->toolbar->is_search = true;
        $view->videos = $res;
        $view->search_key = $key;
        $view->link_attrs = ['search' => $key];

        $outer = new PhpView('sprout/tinymce4/outer');
        $outer->main_content = $view->render();
        $outer->page_title = 'Insert video';

        $res->closeCursor();

        echo $outer->render();
    }


    /**
    * UI for uploading files
    **/
    public function upload()
    {
        AdminAuth::checkLogin();

        if (!AdminPerms::controllerAccess('file', 'add')) {
            echo '<p>You do not have permission to upload files.</p>';
            return;
        }

        $type = $_GET['type'] ?? '';

        $view = new PhpView('sprout/tinymce4/upload');
        $view->toolbar = self::initToolbar($type);

        switch ($type) {
        case 'image':
            $view->f_type = FileConstants::TYPE_IMAGE;
            break;

        case 'video':
            $view->f_type = FileConstants::TYPE_VIDEO;
            break;

        default:
            $view->f_type = FileConstants::TYPE_DOCUMENT;
        }

        $cat_table = Category::tableMain2cat('files');
        $view->cats = Pdb::lookup($cat_table);
        $view->cats['_new'] = '-- New --';

        $outer = new PhpView('sprout/tinymce4/outer');
        $outer->main_content = $view->render();
        $outer->page_title = $view->toolbar->upload_label;
        echo $outer->render();
    }


    /**
    * Show a gallery of image categories
    **/
    public function gallery()
    {
        AdminAuth::checkLogin();

        $cat_table = Category::tableMain2cat('files');
        $joiner_table = Category::tableMain2joiner('files');

        $q = "SELECT
                cat.id,
                cat.name,
                GROUP_CONCAT(file.filename ORDER BY RAND() SEPARATOR '|') AS filenames
            FROM ~{$cat_table} AS cat
            INNER JOIN ~{$joiner_table} AS joiner
                ON joiner.cat_id = cat.id
            INNER JOIN ~files AS file
                ON joiner.file_id = file.id
            WHERE file.name != ''
                AND file.type = ?
            GROUP BY cat.id
            ORDER BY cat.name
            LIMIT 500";

        $categories = Pdb::q($q, [FileConstants::TYPE_IMAGE], 'arr');

        $view = new PhpView('sprout/tinymce4/image_gallery');
        $view->categories = $categories;

        $outer = new PhpView('sprout/tinymce4/outer');
        $outer->main_content = $view->render();
        $outer->page_title = 'Insert Gallery - choose category';

        echo $outer->render();
    }
}

