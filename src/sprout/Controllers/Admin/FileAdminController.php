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

namespace Sprout\Controllers\Admin;

use Exception;

use Kohana;
use Kohana_404_Exception;

use karmabunny\pdb\Exceptions\QueryException;
use Sprout\Exceptions\ImageException;
use Sprout\Exceptions\WorkerJobException;
use Sprout\Helpers\Admin;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\AdminPerms;
use Sprout\Helpers\Category;
use Sprout\Helpers\ColModifierBinary;
use Sprout\Helpers\ColModifierLookupArray;
use Sprout\Helpers\Cron;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\File;
use Sprout\Helpers\FileConstants;
use Sprout\Helpers\FileIndexing;
use Sprout\Helpers\FileUpload;
use Sprout\Helpers\Form;
use Sprout\Helpers\FrontEndSearch;
use Sprout\Helpers\Image;
use Sprout\Helpers\Json;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\RefineBar;
use Sprout\Helpers\RefineWidgetSelect;
use Sprout\Helpers\RefineWidgetTextbox;
use Sprout\Helpers\Replication;
use Sprout\Helpers\Search;
use Sprout\Helpers\Security;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\Text;
use Sprout\Helpers\Upload;
use Sprout\Helpers\Url;
use Sprout\Helpers\Validator;
use Sprout\Helpers\PhpView;
use Sprout\Helpers\WorkerCtrl;


/**
* Handles most of the processing for files
**/
class FileAdminController extends HasCategoriesAdminController
    implements FrontEndSearch
{
    protected $friendly_name = 'Files';
    protected $add_defaults = array(
        'categories' => array(),
        'indexing' => 1,
    );
    protected $category_archive = true;
    protected $main_delete = true;


    /**
    * Constructor
    **/
    public function __construct()
    {
        $this->main_columns = [
            'Name' => 'name',
            'Type' => [new ColModifierLookupArray(FileConstants::$type_names), 'type'],
            'Filename' => 'filename',
            'Author' => 'author',
            'Show Author' => [new ColModifierBinary(), 'embed_author'],
        ];

        $this->main_where = array(
            "item.type != 0",
            "item.name != ''",
        );

        $this->refine_bar = new RefineBar();
        $this->refine_bar->setGroup('Files');
        $this->refine_bar->addWidget(new RefineWidgetTextbox('name', 'Name'));
        $this->refine_bar->addWidget(new RefineWidgetSelect('type', 'Type', FileConstants::$type_names));
        $this->refine_bar->addWidget(new RefineWidgetTextbox('filename', 'Filename'));

        $this->refine_bar->setGroup('Documents');
        $this->refine_bar->addWidget(new RefineWidgetSelect('document_type', 'Document Type', Pdb::lookup('document_types')));

        $this->main_modes['thumb'] = array('Thumbnails', 'grid');

        parent::__construct();
    }

    /**
     * Files controller provides it's own per-record permissions.
     *
     * @inheritdoc
     */
    public static function _getContentPermissionGroups(): array
    {
        $permissions = parent::_getContentPermissionGroups();
        unset($permissions['record']);
        return $permissions;
    }

    /**
     * Return the fields to show in the sidebar when adding or editing a record.
     * These fields are shown under a heading of "Visibility"
     *
     * Key is the field name, value is the field label
     *
     * @return array
     */
    public function _getVisibilityFields()
    {
        $file_id = Admin::getRecordId();
        $file = !empty($file_id) ? Pdb::get('files', $file_id) : null;
        $list = [];

        if (!empty($file['type']) and $file['type'] == FileConstants::TYPE_IMAGE) $list['embed_author'] = 'Embed author credit in image';

        $list['enable_indexing'] = 'Show in search results';

        return $list;
    }


    /**
     * Is the "add" action saved?
     * These may be false if the UI provides its own save mechanism (e.g. multi-add)
     *
     * @return bool True if they are saved, false if they are not
     */
    public function _isAddSaved()
    {
        return false;
    }


    /**
    * Hook called by _getAddForm() just before the view is rendered
    **/
    protected function _addPreRender($view)
    {
        parent::_addPreRender($view);

        $opts = array();
        $opts['chunk_url'] = 'admin/call/file/ajaxDragdropChunk';
        $opts['done_url'] = 'admin/call/file/ajaxDragdropDone';
        $opts['form_url'] = 'admin/call/file/ajaxDragdropForm';
        $opts['cancel_url'] = 'admin/call/file/ajaxDragdropCancel';
        $opts['form_params'] = [];
        $opts['form_el'] = '.drag-drop__form';
        $opts['max_files'] = 1000;

        $view->opts = $opts;
    }


    /**
    * Save a single chunk of a multi-part file upload
    *
    * @post string chunk Binary data
    * @post int index Chunk index, 0-based
    * @post string code Unique code for this upload
    * @return void Outputs JSON
    **/
    public function ajaxDragdropChunk()
    {
        if (!preg_match('/^[0-9]+$/', $_POST['index'])) {
            Json::error('Invalid "index" param');
        }
        if (!preg_match('/^[A-Za-z0-9]{32}$/', $_POST['code'])) {
            Json::error('Invalid "code" param');
        }

        if (!is_dir(STORAGE_PATH . 'temp')) {
            Json::error('Temporary directory does not exist');
        }
        if (!is_writable(STORAGE_PATH . 'temp')) {
            Json::error('Temporary directory is not writable');
        }

        $filename = STORAGE_PATH . 'temp/chunk-' . $_POST['code'] . '-' . $_POST['index'] . '.dat';
        $result = rename($_FILES['chunk']['tmp_name'], $filename);
        if (!$result) {
            Json::error('Move of chunk to temporary directory failed');
        }

        Json::confirm();
    }


    /**
    * Stitch together uploaded chunks into an actual file
    *
    * Outputs a JSON response.
    * The field "success" will be checked (= 1) to determine success.
    * On error, the field "message" will be used as an error message.
    * Other keys provided are passed to the ajaxDragdropForm method.
    *
    * @post num The total number of chunks uploaded
    * @post string code Unique code for this upload
    * @return void Outputs JSON
    **/
    public function ajaxDragdropDone()
    {
        if (!preg_match('/^[0-9]+$/', $_POST['num'])) {
            Json::error('Invalid "num" param');
        }
        if (!preg_match('/^[A-Za-z0-9]{32}$/', $_POST['code'])) {
            Json::error('Invalid "code" param');
        }

        $dest_filename = 'upload-' . time() . '-' . $_POST['code'] . '.dat';

        try {
            $this->stitchChunks(STORAGE_PATH . 'temp/' . $dest_filename, $_POST['code'], $_POST['num']);
        } catch (Exception $ex) {
            Json::error($ex->getMessage());
        }

        Json::confirm(array(
            'tmp_file' => $dest_filename,
        ));
    }


    /**
    * Stitch together the uploaded file from multiple chunks
    *
    * @param string $dest_filename The destination filename
    * @param string $code Upload code
    * @param string $num_chunks The number of chunks to stitch together
    **/
    private function stitchChunks($dest_filename, $code, $num_chunks) {
        $num_chunks = (int) $num_chunks;

        $out = @fopen($dest_filename, 'w');
        if (! $out) {
            throw new Exception('Unable to open file for writing');
        }

        // Copy chunks into the file. If anything goes wrong, the file will not be complete so bail
        $damaged = false;
        for ($i = 0; $i < $num_chunks; ++$i) {
            $chunk = STORAGE_PATH . 'temp/chunk-' . $code . '-' . $i . '.dat';
            if (!file_exists($chunk)) {
                $damaged = true;
                break;
            }

            $in = @fopen($chunk, 'r');
            if (! $in) {
                $damaged = true;
                break;
            }

            $result = @stream_copy_to_stream($in, $out);
            if (! $result) {
                $damaged = true;
                break;
            }

            $result = @fclose($in);
            if (! $result) {
                $damaged = true;
                break;
            }
        }

        $result = fclose($out);
        if (! $result) {
            $damaged = true;
        }

        // Nuke all the chunks prior to error handling
        for ($i = 0; $i < $num_chunks; ++$i) {
            $chunk = STORAGE_PATH . 'temp/chunk-' . $code . '-' . $i . '.dat';
            @unlink($chunk);
        }

        if ($damaged) {
            throw new Exception('One or more chunks failed to be read');
        }
    }


    /**
    * Returns the form for updating a file which has been uploaded
    *
    * @get array file File details, as per the File API; 'lastModifiedDate', 'name', 'size', 'type'
    * @get array result The full JSON response from the ajaxDragdropDone call
    * @get array form Details of the form shown above the drag-n-drop field
    * @return void Outputs HTML
    **/
    public function ajaxDragdropForm()
    {
        $_GET['file']['name'] = trim(Enc::cleanfunky($_GET['file']['name']));

        if (!FileUpload::checkFilename($_GET['file']['name'])) {
            echo '<p>This type of file cannot be uploaded.</p>';
            return;
        }

        $data = [];
        $data['name'] = str_replace('_', ' ', File::getNoext($_GET['file']['name']));

        // Determine type from extension
        $data['type'] = FileConstants::TYPE_OTHER;
        $ext = strtolower(File::getExt($_GET['file']['name']));
        foreach (FileConstants::$type_exts as $type => $exts) {
            if (in_array($ext, $exts)) {
                $data['type'] = $type;
                break;
            }
        }

        // Attempt to use the last modified date as the publish date
        $ts = strtotime($_GET['file']['lastModifiedDate'] ?? '');
        if (!$ts) $ts = time();
        $data['date_published'] = date('Y-m-d', $ts);

        $data['embed_author'] = 1;

        $view = new PhpView('sprout/admin/file_add_dragdrop_form');
        $view->tmp_file = $_GET['result']['tmp_file'];
        $view->orig_file = $_GET['file'];
        $view->size_bytes = filesize(STORAGE_PATH . 'temp/' . $_GET['result']['tmp_file']);
        $view->errors = [];
        $view->categories = Pdb::lookup('files_cat_list');

        if ($data['type'] == FileConstants::TYPE_IMAGE) {
            $temp_path = STORAGE_PATH . 'temp/' . $view->tmp_file;
            try {
                $view->shrunk_img = File::base64Thumb($temp_path, 200, 200);

                $max_dims = Kohana::config('image.original_size');
                if (!empty($max_dims)) {
                    $shrink_original = false;
                    if ($view->shrunk_img['original_width'] > $max_dims['width']) {
                        $shrink_original = true;
                    } else if ($view->shrunk_img['original_height'] > $max_dims['height']) {
                        $shrink_original = true;
                    }
                    $view->shrink_original = $shrink_original;
                    $data['shrink_original'] = 1;
                }

            } catch (ImageException $ex) {
                Kohana::logException($ex);

                if ($ex->getCode() == ImageException::IMAGE_UNKNOWN_TYPE) {
                    $view->unsupported_image_type = true;
                } else if ($ex->getCode() == ImageException::IMAGE_TOO_LARGE) {
                    $view->image_too_large = true;
                } else {
                    $view->error = $ex->getMessage();
                }
            }
        }

        $view->data = $data;

        // Only one category? Select that. Category specified? Select that.
        if (count($view->categories) == 1) {
            $view->data['category_id'] = key($view->categories);
        } else if (!empty($_GET['form']['category_id'])) {
            $view->data['category_id'] = $_GET['form']['category_id'];
        }

        $q = "SELECT id, name
            FROM ~document_types
            ORDER BY record_order";
        $view->document_types = Pdb::q($q, [], 'map');

        echo $view->render();
    }


    /**
    * Handles the drag-and-drop upload form
    *
    * Output JSON should be:
    *    success   1 or 0
    *    message   Error message, if success is 0
    *    html      Confirmation HTML, if success is 1
    *
    * @return void Outputs JSON
    **/
    public function ajaxDragdropSave()
    {
        Csrf::checkOrDie();

        $_POST['orig_name'] = trim(Enc::cleanfunky($_POST['orig_name']));
        $_POST['name'] = trim(Enc::cleanfunky($_POST['name']));

        if (!FileUpload::checkFilename($_POST['orig_name'])) {
            Json::error('This type of file cannot be uploaded');
        }
        if (!$_POST['name']) {
            Json::error('You must specify a name');
        }

        $type = File::getType($_POST['orig_name']);

        // For images, calculate the expected RAM requirement of the resizing
        // and confirm it's within the memory limit
        if ($type == FileConstants::TYPE_IMAGE) {
            $dimensions = getimagesize(STORAGE_PATH . 'temp/' . $_POST['tmp_file']);
            try {
                File::calculateResizeRam($dimensions);
            } catch (Exception $ex) {
                Json::error($ex);
            }
        }

        Pdb::transact();

        $update_fields = array();
        $update_fields['name'] = $_POST['name'];
        $update_fields['type'] = $type;
        $update_fields['date_added'] = Pdb::now();
        $update_fields['date_modified'] = Pdb::now();

        if (isset($_POST['document_type'])) {
            $update_fields['document_type'] = $_POST['document_type'];
        }

        if (isset($_POST['date_published'])) {
            $update_fields['date_published'] = $_POST['date_published'];
        } else {
            $update_fields['date_published'] = Pdb::now();
        }

        $update_fields['author'] = @$_POST['author'];
        $update_fields['embed_author'] = @$_POST['embed_author'] ? 1 : 0;

        $update_fields['sha1'] = hash_file('sha1', STORAGE_PATH . 'temp/' . $_POST['tmp_file'], false);

        try {
            $file_id = Pdb::insert('files', $update_fields);
        } catch (QueryException $ex) {
            Json::error('Database error');
        }

        $filename = $file_id . '_' . File::filenameMakeSane($_POST['orig_name']);

        // Filename is only set after upload because the ID is in the name
        $update_fields = array();
        $update_fields['filename'] = $filename;
        try {
            Pdb::update('files', $update_fields, ['id' => $file_id]);
        } catch (QueryException $ex) {
            Json::error('Database error');
        }

        // Update categories
        if (!empty($_POST['category_id'])) {
            Category::insertInto('files', $file_id, $_POST['category_id']);
        }

        // Actually move the file in
        $src = STORAGE_PATH . 'temp/' . $_POST['tmp_file'];
        if (!empty($_POST['shrink_original'])) {
            $size = getimagesize($src);
            $max_dims = Kohana::config('image.original_size');

            if ($size[0] > $max_dims['width'] or $size[1] > $max_dims['height']) {
                $temp_path = STORAGE_PATH . 'temp/original_image_' . time() . '_' . Sprout::randStr(4);
                $temp_path .= '.' . File::getExt($filename);
                $img = new Image($src);
                $img->resize($max_dims['width'], $max_dims['height']);
                $img->save($temp_path);

                $result = File::putExisting($filename, $temp_path);
                unlink($temp_path);
                unlink($src);
            } else {
                $result = File::moveUpload($src, $filename);
            }

        } else {
            $result = File::moveUpload($src, $filename);
        }
        if (!$result) {
            Json::error('Copying temporary file into media repository failed');
        }

        // Index documents, resize images, etc
        try {
            File::postUploadProcessing($filename, $file_id, $type);
        } catch (Exception $ex) {
            Json::error($ex);
        }

        Pdb::commit();

        $html = '<div class="file-upload__item__feedback__response file-upload__item__feedback__response--success file-upload__item__feedback__response--success--not-image">';
        $html .= '<p class="file-upload__item__feedback__name"><a href="admin/edit/file/' . $file_id . '" target="_blank">' . Enc::html($filename) . '</a></p>';
        $html .= '<p class="file-upload__item__feedback__size">' . File::humanSize(File::size($filename)) . '</p>';
        $html .= '</div>';

        Json::confirm([
            'html' => $html,
            'file_id' => $file_id,
            'filename' => $filename,
        ]);
    }


    /**
     * Cancel an upload - delete temporary files.
     *
     * May receive two different variations on the provided POST data:
     *    [result][tmp_file]       Whole file was uploaded
     *    [partial_upload][code]   Only some chunks of the file have been uploaded
     *
     * @return void
     */
    public function ajaxDragdropCancel()
    {
        // The file upload controller has a perfect implementation of this, so just use that
        $ctlr = new \Sprout\Controllers\FileUploadController();
        $ctlr->uploadCancel();
    }


    /**
     * Matches user input against a list of possible authors for files
     * @return void Outputs JSON directly (see {@see Json::out})
     */
    public function ajaxAuthorLookup()
    {
        if (empty($_GET['term'])) Json::out([]);

        $terms = preg_split('/\s+/', trim($_GET['term']));

        // Check extant author list
        $conditions = [];
        foreach ($terms as $term) {
            $conditions[] = ['author', 'CONTAINS', Pdb::likeEscape($term)];
        }

        $params = [];
        $clause = Pdb::buildClause($conditions, $params);
        $q = "SELECT DISTINCT author
            FROM ~files
            WHERE {$clause}
            ORDER BY author";
        Json::out(Pdb::q($q, $params, 'col'));
    }


    /**
    * Not used.
    **/
    public function _addSave(&$item_id)
    {
        return false;
    }


    /**
    * Does a quick upload (from the fileselector)
    * Returns JSON.
    **/
    public function quickUpload()
    {
        Csrf::checkOrDie();

        if (! AdminPerms::controllerAccess('file', 'add')) {
            throw new Kohana_404_Exception();
        }

        $result = $this->doUpload($_POST['category_id'] ?? 0);

        echo '<div>', json_encode($result), '</div>';
    }


    /**
     * Used by the quick upload tool
     * @param int $category_id ID of category to store file in
     */
    private function doUpload($category_id)
    {
        $category_id = (int) $category_id;

        // Check upload exists and has valid metadata
        $allowed_exts = [];
        if ($_POST['type'] ?? '' == 'image') {
            $allowed_exts = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        }
        try {
            $temp_file = FileUpload::verify('admin_quick_upload', 'file', 0, $allowed_exts);
            $filename = @$_POST['file'][0];
            if (!$filename) {
                return ['error' => 'File uploading failed'];
            }
        } catch (Exception $ex) {
            return ['error' => $ex->getMessage()];
        }

        $filename = File::filenameMakeSane($filename);

        // Don't allow executable files
        if (!FileUpload::checkFilename($filename)) {
            return array('error' => 'This type of file cannot be uploaded');
        }

        // Check name
        $name = trim($_POST['name']);
        if (!$name) {
            return array('error' => 'No name was supplied');
        }

        // Get type
        $file_type = 0;
        $ext = File::getExt($filename);
        foreach (FileConstants::$type_exts as $type => $exts) {
            if (in_array($ext, $exts)) {
                $file_type = $type;
                break;
            }
        }

        if ($file_type == 0) $file_type = FileConstants::TYPE_OTHER;


        Pdb::transact();

        // Add file
        $update_data = array();
        $update_data['name'] = $name;
        $update_data['filename'] = $filename;
        $update_data['type'] = $file_type;
        $update_data['date_added'] = Pdb::now();
        $update_data['date_modified'] = Pdb::now();
        $update_data['date_file_modified'] = Pdb::now();
        $update_data['sha1'] = hash_file('sha1', $temp_file, false);

        try {
            $file_id = Pdb::insert('files', $update_data);
        } catch (Exception $ex) {
            return array('error' => 'Unable to upload file; database error (main)');
        }

        // Add category
        if (!empty($_POST['category_new'])) {
            if (!AdminPerms::controllerAccess('file', 'categories')) {
                return array('error' => 'Unable to create category; no permissions');
            }
            try {
                $category_id = Category::create('files', $_POST['category_new']);
            } catch (Exception $ex) {
                return array('error' => 'Unable to upload file; database error (cat)');
            }
        }

        // Add file category
        if ($category_id) {
            try {
                Category::insertInto('files', $file_id, $category_id);
            } catch (Exception $ex) {
                return array('error' => 'Unable to upload file; database error (joiner)');
            }
        }

        // Upload the file - uses the file id
        $filename = $file_id . '_' . $filename;
        $result = File::moveUpload($temp_file, $filename);
        if (! $result) {
            return array('error' => 'Failed to save the uploaded file in media repository');
        }

        // Update file name and do image resizing, text indexing, and other postprocessing
        try {
            File::postUploadProcessing($filename, $file_id, $file_type);
        } catch (Exception $ex) {
            return array('error' => $ex->getMessage());
        }

        Pdb::commit();


        return array(
            'id' => $file_id,
            'filename' => $filename,
            'type' => $file_type,
            'cat_id' => $category_id,
            'rel_url' => File::relUrl($filename),
        );
    }


    /**
    * Pre-render hook
    **/
    public function _editPreRender($view, $item_id)
    {
        if ($view->data['type'] == FileConstants::TYPE_IMAGE) {
            $size = File::imageSize($view->item['filename']);

            $view->img_dimensions = 'Unkown';
            $view->sizes = [];
            $view->original_image = '';

            if (empty($size)) {
                Notification::error('Image may be missing from server');
                return;
            }

            $view->img_dimensions = $size[0] . 'x' . $size[1];

            $parts = explode('.', $view->item['filename']);
            $view->sizes = File::glob($parts[0] . '.*.' . $parts[1]);

            $image_url = File::resizeUrl($view->data['filename'], 'r200x0');
            $image_url .= (strpos($image_url, '?') === false ? '?' : '&');
            $view->original_image = $image_url . 'version=' . Sprout::randStr(10);

        } else if ($view->data['type'] == FileConstants::TYPE_DOCUMENT) {
            $view->document_types = Pdb::lookup('document_types');

            // Date published is a DATETIME, but the datepicker can't handle that
            $view->data['date_published'] = date('Y-m-d', strtotime($view->data['date_published']));

            // Clean up and prepare text preview
            $preview = trim(Enc::cleanFunky($view->data['plaintext']));
            $preview = Text::limitWords($preview, 50, '...');
            $preview = wordwrap($preview, 50);
            $view->preview = $preview;
        }
    }


    /**
     * Return the sub-actions for editing; for spec {@see AdminController::renderSubActions}
     * @return array
     */
    public function _getEditSubActions($item_id)
    {
        $actions = parent::_getEditSubActions($item_id);

        $actions['usage'] = [
            'url' => 'admin/extra/' . $this->controller_name . '/find_usage/' . $item_id,
            'name' => 'Find Usage',
            'class' => 'icon-link-button icon-before icon-search',
        ];

        return $actions;
    }


    /**
     * Saves the provided POST data into this file in the database
     *
     * @param int $item_id The record to update
     * @return bool True on success, false on failure
     * @throws QueryException
     */
    public function _editSave($item_id)
    {
        $item_id = (int) $item_id;

        $file = Pdb::get('files', $item_id);

        $_SESSION['admin']['field_values'] = Validator::trim($_POST);

        $valid = new Validator($_POST);
        $valid->required(['name']);
        $valid->check('name', 'Validity::length', 1, 200);
        $valid->check('description', 'Validity::length', 1, 10000);
        $valid->check('author', 'Validity::length', 1, 80);

        if (!empty($_FILES['replace']['name'])) {
            // Check upload is valid
            if (!Upload::valid($_FILES['replace'])) {
                Notification::error('Error with upload of replacement; you will need to re-select your file');
                $valid->addFieldError('replace', 'File upload failed');
            }

            // Check type matches
            $file_type = File::getType($_FILES['replace']['name']);
            if ($file['type'] != $file_type) {
                Notification::error('Error with file upload; you will need to re-select your file');
                $valid->addFieldError('replace', 'File must be of type: ' . FileConstants::$type_names[$file['type']]);
            }
        }

        if ($valid->hasErrors()) {
            $_SESSION['admin']['field_errors'] = $valid->getFieldErrors();
            $valid->createNotifications();
            return false;
        }

        $needs_regenerate_sizes = false;

        // Upload the new file
        $filename = $file['filename'];
        $original_filename = $filename;
        if (!empty($_FILES['replace']['name'])) {
            $new_filename = $item_id . '_' . File::filenameMakeSane($_FILES['replace']['name']);

            if (!File::putExisting($new_filename, $_FILES['replace']['tmp_name'])) {
                Notification::error('File upload failed');
                return false;
            }

            if ($file['type'] == FileConstants::TYPE_DOCUMENT) {
                // Do document indexing
                $ext = File::getExt($new_filename);
                $plain = '';
                if (FileIndexing::isExtSupported($ext)) {
                    $plain = FileIndexing::getPlaintext($new_filename, $ext);
                }

            } else if ($file['type'] == FileConstants::TYPE_IMAGE) {
                $needs_regenerate_sizes = true;

                // No sense in keeping the focal point for a replaced image
                $_POST['focal_points'] = '';
            }

            Notification::confirm('New file uploaded successfully');

            // Delete original
            if ($filename != $new_filename) {
                File::delete($filename);

                // Remove any redirects from new filename, to prevent unnecessary redirection
                $pattern = 'files/' . File::getNoext($filename) . '.';
                $params = [];
                $conditions = [['path_exact', 'BEGINS', $pattern]];
                $q = "DELETE FROM ~redirects WHERE " . Pdb::buildClause($conditions, $params);
                Pdb::q($q, $params, 'null');
            }

            $filename = $new_filename;
        }

        // Image manipulations
        if ($file['type'] == FileConstants::TYPE_IMAGE) {

            // Do image manipulations, if requested
            if ($_POST['manipulate'] != '') {
                $temp_filename = File::createLocalCopy($file['filename']);
                if (! $temp_filename) return false;

                $img = new Image($temp_filename);
                if (! $img) return false;

                switch ($_POST['manipulate']) {
                    case 'rotate-90-clockwise': $img->rotate(90); break;
                    case 'rotate-90-counterclockwise': $img->rotate(-90); break;
                    case 'rotate-180': $img->rotate(180); break;
                    case 'flip-horizontal': $img->flip(Image::HORIZONTAL); break;
                    case 'flip-vertical': $img->flip(Image::VERTICAL); break;
                    default:
                        throw new Exception('Invalid image manipulation "' . $_POST['manipulate'] . '"');
                }

                $res = $img->save();
                if (! $res) return false;

                $result = File::putExisting($file['filename'], $temp_filename);
                if (! $result) return false;

                File::cleanupLocalCopy($temp_filename);

                $res = Replication::postFileUpdate($file['filename']);
                if (! $res) return false;

                $needs_regenerate_sizes = true;

                // No sense in keeping the focal point for a manipulated image
                $_POST['focal_points'] = '';

                Notification::confirm('Image was manipulated successfully');
            }

            // If author (or embed option) has changed, the sizes will need regeneration
            if (
                $file['embed_author'] != (int) @$_POST['embed_author']
                or
                ((int) @$_POST['embed_author']) and $file['author'] != $_POST['author']
            ) {
                $needs_regenerate_sizes = true;
            }
        }


        Pdb::transact();

        // Update record
        $data = [];
        $data['date_modified'] = Pdb::now();
        $data['name'] = $_POST['name'];
        $data['description'] = $_POST['description'];
        $data['author'] = $_POST['author'];
        $data['filename'] = $filename;
        $data['enable_indexing'] = (int) @$_POST['enable_indexing'];

        if ($file['type'] == FileConstants::TYPE_IMAGE) {
            $data['embed_author'] = (int) @$_POST['embed_author'];

            $points = @json_decode($_POST['focal_points'], true);
            if (is_array($points)) {
                foreach ($points as $key => $point) {
                    if (!is_array($point) or count($point) != 2) {
                        unset($points[$key]);
                        continue;
                    }
                    if (!is_int($point[0]) or !is_int($point[1])) {
                        unset($points[$key]);
                        continue;
                    }
                }
                $data['focal_points'] = json_encode($points);
            } else {
                $data['focal_points'] = '';
            }

            if ($data['focal_points'] != $file['focal_points']) {
                $needs_regenerate_sizes = true;
            }
        } elseif ($file['type'] == FileConstants::TYPE_DOCUMENT) {
            $data['document_type'] = $_POST['document_type'];
            $data['date_published'] = $_POST['date_published'];
        }

        Pdb::update('files', $data, ['id' => $item_id]);

        $this->reindexItem($item_id, $_POST['name'], $file['plaintext'], $data['enable_indexing']);

        if ($file['type'] == FileConstants::TYPE_IMAGE and $needs_regenerate_sizes) {
            File::touch($file['filename']);
            File::createDefaultSizes($filename);
            File::deleteCache($filename);
        }

        $this->updateCategories($item_id, @$_POST['categories']);

        if ($original_filename != $filename) {
            File::delete($original_filename);
            File::deleteCache($original_filename);

            $variants = array('');
            if ($file['type'] == FileConstants::TYPE_IMAGE) {
                $variants = array_merge($variants, array_keys(Kohana::config('file.image_transformations')));
            }

            // Make sure old links still function by adding a redirect from the old file name to the new one
            foreach ($variants as $variant) {
                $old_path = 'files/' . $original_filename;
                $new_path = 'file/download/' . $item_id;

                // For image variants:
                // convert e.g. 123_blah.jpg to 123_blah.small.jpg
                // append size to redirect URL, e.g. file/123/small
                if ($variant) {
                    $old_path = File::getResizeFilename($old_path, $variant);
                    $new_path .= '/' . $variant;
                }

                $dest_link_spec = json_encode([
                    'class' => '\\Sprout\\Helpers\\LinkSpecInternal',
                    'data' => $new_path,
                ]);

                $redirect = [
                    'path_exact' => $old_path,
                    'destination' => $dest_link_spec,
                    'type' => 'Temporary',
                    'date_added' => Pdb::now(),
                    'date_modified' => Pdb::now(),
                ];
                Pdb::insert('redirects', $redirect);
            }

            if ($file['type'] == FileConstants::TYPE_IMAGE) {
                Pdb::update('pages', ['banner' => $filename], ['banner' => $original_filename]);
            }
        }

        Pdb::commit();

        return true;
    }


    /**
     * Deletes an item and logs the deleted data
     *
     * This method DOES NOT remove files from disk, in case the deleted DB record needs to be restored.
     * They are removed later, when the deletion log is cleared; see {@see ActionLogAdminController::cronCleanup}.
     * If lack of disk space is an issue, the log should be cleared more often, or alternate file backends should be
     * used; see {@see FilesBackend}.
     *
     * @param int $item_id The record to delete
     * @return bool True on success, false on failure
     * @throws QueryException
     */
    public function _deleteSave($item_id)
    {
        return parent::_deleteSave($item_id);
    }


    /**
     * Does a re-index for a file
     *
     * @param int $item_id
     * @param string $name
     * @param string $plaintext
     * @param bool $enabled
     * @return bool True on success
     */
    private function reindexItem($item_id, $name, $plaintext, $enabled = true)
    {
        $enabled = (bool) $enabled;
        Search::selectIndex('file_keywords', $item_id);

        $res = Search::clearIndex();
        if (! $res) return false;

        // File is marked as not to be included in search results
        if (!$enabled) return true;

        $res = Search::indexText($name, 4);
        if (! $res) return false;

        if ($plaintext) {
            $res = Search::indexHtml($plaintext, 1);
            if (! $res) return false;
        }

        $res = Search::cleanup('files');
        if (! $res) return false;

        return true;
    }


    /**
    * Does a complete re-index of all files
    **/
    public function reindexAll()
    {
        AdminAuth::checkLogin();

        Pdb::transact();

        $q = "SELECT id, name, filename, plaintext, enable_indexing FROM ~files";
        $res = Pdb::query($q, [], 'pdo');

        foreach ($res as $row) {
            $plain = '';

            if ($row['plaintext'] == '') {
                $ext = File::getExt($row['filename']);

                if (FileIndexing::isExtSupported($ext)) {
                    $plain = FileIndexing::getPlaintext($row['filename'], $ext);
                    if ($plain) {
                        Pdb::update('files', ['plaintext' => $plain], ['id' => $row['id']]);
                    }
                }
            }

            $this->reindexItem($row['id'], $row['name'], $plain ?: $row['plaintext'], $row['enable_indexing']);
        }

        $res->closeCursor();

        Pdb::commit();

        echo '<p>Success</p>';
    }


    /**
    * Process the results of a search.
    *
    * @param array $row A single row of data to output
    * @return string The result string
    **/
    public function frontEndSearch($item_id, $relevancy, $keywords)
    {
        $q = "SELECT name, filename, plaintext, enable_indexing FROM sprout_files WHERE id = ?";
        $row = Pdb::q($q, [$item_id], 'row');

        // File is marked as not to be included in search results
        if ($row['enable_indexing'] == 0) return null;

        $text = strip_tags($row['plaintext'] ?? '');
        $text = substr($text, 0, 5000);

        // Look for the first keyword in the text
        $pos = 5000;
        $matches = null;
        foreach ($keywords as $k) {
            $k = preg_quote($k);
            if (preg_match("/(^|\W){$k}($|\W)/i", $text, $matches, PREG_OFFSET_CAPTURE)) {
                $pos = min($pos, $matches[0][1]);
            }
        }

        // If anything was found in first 5000 chars, show that bit
        if ($pos < 5000) {
            $pos -= 10;
            if ($pos > 1) {
                $text = '...' . substr($text, $pos);
            }
        }

        // Limit to something more reasonable
        $text = Text::limitWords($text, 40, '...');

        // Bolden keywords
        $name = $row['name'];
        foreach ($keywords as $k) {
            $k = preg_quote($k);
            $name = preg_replace("/(^|\W)({$k})($|\W)/i", '$1<b>$2</b>$3', $name);
            $text = preg_replace("/(^|\W)({$k})($|\W)/i", '$1<b>$2</b>$3', $text);
        }

        $view = new PhpView('sprout/search_results_page');
        $view->name = $name;
        $view->url = File::url($row['filename']);
        $view->text = $text;
        $view->relevancy = $relevancy;

        return $view->render();
    }


    /**
    * Return the list of sidebar tools
    **/
    public function _getTools()
    {
        $tools = parent::_getTools();
        unset($tools['import']);

        $tools[] = '<li class="config"><a href="admin/extra/file/cleanup_invalid">Cleanup invalid files</a></li>';

        if (AdminAuth::isSuper()) {
            $tools[] = '<li class="config"><a href="admin/extra/file/redo_sizes">Recreate resized images</a></li>';
        }

        return $tools;
    }


    /**
    * Provides a UI for doing a 'cleanup' - removes invalid files
    **/
    public function _extraCleanupInvalid()
    {
        $view = new PhpView("sprout/admin/file_cleanup_invalid");
        $view->count_delete = 0;

        $q = "SELECT id, filename, name, type FROM ~files";
        $res = Pdb::query($q, [], 'pdo');

        foreach ($res as $file) {
            if ($file['name'] == '') {
                $view->count_delete++;
            } else if ($file['type'] == FileConstants::TYPE_NONE) {
                $view->count_delete++;
            } else if (!File::exists($file['filename'])) {
                $view->count_delete++;
            }
        }

        $res->closeCursor();

        return array(
            'title' => 'Cleanup invalid files',
            'content' => $view->render()
        );
    }


    /**
    * Does file cleanup - wrapper (admin)
    **/
    public function cleanupInvalidAction()
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        try {
            $this->cleanupInvalidActionInner();
        } catch (QueryException $ex) {
            Notification::error('Database error performing cleanup');
            Url::redirect('admin/extra/file/cleanup_invalid');
        }

        Notification::confirm('Cleanup was successful');
        Url::redirect('admin/intro/file');
    }


    /**
    * Does file cleanup - wrapper (cron)
    **/
    public function cronCleanupInvalid()
    {
        Cron::start('Cleanup invalid files');
        $this->cleanupInvalidActionInner();
        Cron::success();
    }


    /**
    * Remove invalid files, such as:
    *  - Files without a name
    *  - Files without a type
    *  - Files which don't actually exist
    *
    * @throws QueryExeption
    **/
    private function cleanupInvalidActionInner()
    {
        Pdb::transact();

        $joiner_table = Category::tableMain2joiner('files');

        $q = "SELECT id, filename, name, type FROM ~files";
        $res = Pdb::query($q, [], 'pdo');

        foreach ($res as $file) {
            $delete = false;
            if ($file['name'] == '') {
                $delete = true;
            } else if ($file['type'] == FileConstants::TYPE_NONE) {
                $delete = true;
            } else if (!File::exists($file['filename'])) {
                $delete = true;
            }

            if ($delete) {
                Pdb::delete('files', ['id' => $file['id']]);
                Cron::message("Deleted file: " . json_encode($file));
                Pdb::delete($joiner_table, ['file_id' => $file['id']]);
            }
        }

        $res->closeCursor();

        Pdb::commit();
    }


    /**
    * Return HTML for a resultset of items
    * The returned HTML will be sandwiched between the refinebar and the pagination bar.
    *
    * @param Traversable $items The items to render.
    * @param string $mode The mode of the display.
    * @param StdClass $category Category details if a category has been selected.
    **/
    public function _getContentsView($items, $mode, $category)
    {
        if ($mode == 'list') {
            return $this->_getContentsViewList($items, $category);
        } else if ($mode == 'thumb') {
            return $this->_getContentsViewThumb($items, $category);
        }
    }


    /**
    * Thumbnail view for files
    **/
    private function _getContentsViewThumb($items, $category)
    {
        $view = new PhpView("sprout/admin/file_contents_thumbs");
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;
        $view->items = $items;
        $view->allow_add = $this->main_add;
        $view->category = $category;

        return $view->render();
    }


    /**
    * On the fly image resizing
    *
    * The size parameter is the new size.
    * The first character is taken to be the resize type, accepts 'r' or 'c'.
    * The width and height is specified width . 'x' . height (e.g. 200x100)
    **/
    public function previewTransform($transform, $filename)
    {
        $filename = str_replace('/', '', $filename);
        $temp_filename = File::createLocalCopy($filename);

        $img = new Image($temp_filename);
        $img->resize(200, 0);

        switch ($transform) {
            case 'rotate-90-clockwise':
                $img->rotate(90);
                break;

            case 'rotate-90-counterclockwise':
                $img->rotate(-90);
                break;

            case 'rotate-180':
                $img->rotate(180);
                break;

            case 'flip-horizontal':
                $img->flip(Image::HORIZONTAL);
                break;

            case 'flip-vertical':
                $img->flip(Image::VERTICAL);
                break;
        }

        // Content-type
        $parts = explode('.', $filename);
        $ext = array_pop($parts);
        $mime = array(
            'gif' => 'image/gif',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
        );
        $mime = $mime[$ext];
        if (! $mime) $mime = 'application/octet-stream';

        header('Content-type: ' . $mime);
        $img->render();

        File::cleanupLocalCopy($temp_filename);
    }


    /**
     * Outputs the file selector HTML
     *
     * @return void
     */
    public function selectorPopup()
    {
        $field_name = trim($_GET['field'] ?? '');

        $view = new PhpView('sprout/admin/file_selector_popup');
        $view->field_name = $field_name;
        $view->f_type = (int) $_GET['f_type'];
        $view->cats = Pdb::lookup('files_cat_list');

        $view->upload = isset($_GET['upload']) ? (int) $_GET['upload'] : 1;
        $view->browse = isset($_GET['browse']) ? (int) $_GET['browse'] : 1;
        $view->req_category = isset($_GET['req_category']) ? (int) $_GET['req_category'] : 1;

        if (! AdminPerms::controllerAccess('file', 'add')) {
            $view->upload = false;
        }
        if (! AdminPerms::controllerAccess('file', 'contents')) {
            $view->browse = false;
        }
        $view->cat_create = AdminPerms::controllerAccess('file', 'categories');

        echo $view->render();
    }


    /**
    * Does the backend selector search
    **/
    public function selectorPopupSearch()
    {
        if (! AdminPerms::controllerAccess('file', 'contents')) {
            throw new Kohana_404_Exception();
        }

        $_GET['page'] = (int) $_GET['page'];
        $_GET['f_type'] = (int) $_GET['f_type'];
        $_GET['name'] = trim($_GET['name']);
        $_GET['category_id'] = (int) $_GET['category_id'];

        $joins = [];
        $clauses = [];
        $params = [];

        if ($_GET['category_id'] > 0) {
            $joins[] = "INNER JOIN ~files_cat_join AS joiner
                ON files.id = joiner.file_id AND joiner.cat_id = ?";
            $params[] = $_GET['category_id'];
        }

        if ($_GET['f_type'] > 0) {
            $clauses[] = 'files.type = ?';
            $params[] = $_GET['f_type'];

        } else {
            $clauses[] = 'files.type != 0';
        }


        if ($_GET['name']) {
            $parts = preg_split('/\s+/', $_GET['name']);
            foreach ($parts as $part) {
                $part = Pdb::likeEscape($part);
                $clauses[] = "(files.name LIKE CONCAT('%', ?, '%') OR files.filename LIKE CONCAT('%', ?, '%'))";

                // Doubly add param, once for each LIKE clause
                $params[] = $part;
                $params[] = $part;
            }
        }


        $clauses = implode(' AND ', $clauses);

        $joins = implode("\n", $joins);

        $q = "SELECT COUNT(DISTINCT id) AS C
            FROM ~files AS files
            {$joins}
            WHERE {$clauses}";
        $num_results = Pdb::q($q, $params, 'val');
        $num_pages = ceil($num_results / 10);

        $offset = 10 * $_GET['page'];
        $q = "SELECT DISTINCT files.id, files.name, files.filename
            FROM ~files AS files
            {$joins}
            WHERE {$clauses}
            ORDER BY files.name
            LIMIT 10 OFFSET {$offset}";
        $res = Pdb::q($q, $params, 'arr');

        // Json return object
        $json = array();
        $json[] = array(
            'num_results' => $num_results,
            'num_pages' => $num_pages,
            'curr_page' => $_GET['page'],
        );

        // Append one json record for each db record
        foreach ($res as $row) {
            $preview = $preview_large = '';
            if ($_GET['f_type'] == FileConstants::TYPE_IMAGE or strpos(File::mimetype($row['filename']), 'image/') === 0) {
                if (File::exists($row['filename'])) {
                    $preview = '<img src="' . str_replace('SITE/', '', File::resizeUrl($row['filename'], 'c50x50')) . '">';
                    $preview_large = str_replace('SITE/', '', File::resizeUrl($row['filename'], 'm400x150'));
                }
            }

            $json[] = array(
                'id' => $row['id'],
                'filename' => $row['filename'],
                'name' => $row['name'],
                'preview' => $preview,
                'preview_large' => $preview_large,
            );
        }

        Json::out($json);
    }


    /**
    * List of links to redo the sizes
    **/
    public function _extraRedoSizes()
    {
        $sizes = Kohana::config('file.image_transformations');
        $sz = ['' => 'All'];
        foreach ($sizes as $size_name => $transform) {
            $sz[$size_name] = ucfirst($size_name);
        }

        $out = '<form action="admin/call/file/redoSizesAction" method="post">';
        $out .= Csrf::token();

        Form::nextFieldDetails('Size', true);
        $out .= Form::dropdown('size', [], $sz);

        $out .= '<button type="submit" class="button">Re-generate sizes</button>';
        $out .= '</form>';

        return $out;
    }


    /**
    * Fixes files which don't have the sizes they should
    **/
    public function redoSizesAction()
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        try {
            $info = WorkerCtrl::start('Sprout\\Helpers\\WorkerRedoSizes', $_POST['size']);
        } catch (WorkerJobException $ex) {
            Notification::error('Unable to create worker job');
            Url::redirect('admin/intro/file');
        }

        Url::redirect($info['log_url']);
    }


    /**
     * Find usage of a given file
     *
     * @param int $file_id
     * @return array
     */
    public function _extraFindUsage($file_id)
    {
        $file = Pdb::get('files', $file_id);

        $view = new PhpView('sprout/admin/file_usage');
        $view->file = $file;
        $view->usage = File::findUsage($file['filename']);

        return [
            'title' => sprintf("Usage of file '%s'", $file['name'] ? $file['name'] : $file['filename']),
            'content' => $view
        ];
    }


    /**
     * Renders a HTML subset containing a focal crop image
     *
     * @param string $size WxH, e.g. 300x200
     * @param string $filename E.g. 123_image.jpg
     * @param string $focal_point_data JSON to store in files.focal_points
     */
    public function previewFocalCrop($size, $filename, $focal_point_data)
    {
        if ($size[0] != 'c') {
            $size = 'c' . $size;
        }

        // Copy original file to test location
        $content = File::getString($filename);
        $temp_filename = 'focal_preview_' . $filename;
        File::putString($temp_filename, $content);

        Pdb::transact();

        Pdb::update('files', ['focal_points' => $focal_point_data, 'filename' => $temp_filename], ['filename' => $filename]);

        $_GET['s'] = Security::serverKeySign(['filename' => $temp_filename, 'size' => $size]);
        $_GET['force'] = 1;

        $cont = new \Sprout\Controllers\FileController();
        $cont->resize($size, $temp_filename);

        Pdb::rollback();

        File::deleteCache($temp_filename);
        File::delete($temp_filename);
    }


    /**
     * Provide the contents of a temporarily uploaded file, for e.g. listening to uploaded audio
     *
     * @return void Sets headers and outputs file content
     */
    public function downloadTemp($filename)
    {
        $path = STORAGE_PATH . 'temp/' . $filename;

        if (!preg_match('/^[a-zA-Z0-9-]*\.dat$/', $filename) or !file_exists($path)) {
            http_response_code(404);
            die();
        }

        header('Pragma: public');
        header('Content-type: application/octet-stream');
        header("Cache-Control: no-store, no-cache");
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        header('Last-modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Content-length: ' . filesize($path));
        Kohana::closeBuffers();
        readfile($path);
    }
}


