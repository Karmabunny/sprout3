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

use Sprout\Exceptions\FileUploadException;
use Sprout\Helpers\Enc;
use Sprout\Helpers\File;
use Sprout\Helpers\FileConstants;
use Sprout\Helpers\FileUpload;
use Sprout\Helpers\Json;
use Sprout\Helpers\Session;
use Sprout\Helpers\PhpView;


/**
 * Handles file uploads in chunks using the JS File API and XMLHttpRequest
 */
class FileUploadController extends Controller
{
    /**
     * Maximum number of chunks that can be uploaded per field-session
     */
    const MAX_CHUNK_COUNT = 512;


    /**
     * Checks POSTed code matched the expected format
     *
     * @return string The code itself, if it's valid
     */
    protected function validateCode()
    {
        if (!preg_match('/^[a-z0-9]{32}$/i', $_POST['code'] ?? '')) {
            Json::error('Invalid data');
        }

        return $_POST['code'];
    }


    /**
     * Creates a session record for a new file upload, and configures its initial state
     *
     * @return &array
     */
    protected function &startSession()
    {
        Session::instance();

        if (empty($_POST['form_id']) or !is_string($_POST['form_id'])) {
            Json::error('Invalid data');
        }

        if (empty($_POST['field_name']) or !is_string($_POST['field_name'])) {
            Json::error('Invalid data');
        }

        $code = $this->validateCode();

        $uri = $_POST['form_id'];
        $field_name = $_POST['field_name'];

        if (isset($_SESSION['file_uploads'][$uri][$field_name][$code])) {
            Json::error('Invalid data');
        }

        if (!isset($_SESSION['file_uploads'][$uri][$field_name])) {
            $_SESSION['file_uploads'][$uri][$field_name] = [];
        }

        $_SESSION['file_uploads'][$uri][$field_name][$code] = [
            'index' => 0,
            'size' => 0,
        ];

        return $_SESSION['file_uploads'][$uri][$field_name][$code];
    }


    /**
     * Gets the session data associated with a file upload
     * @return &array
     */
    public function &session()
    {
        Session::instance();

        if (empty($_POST['form_id']) or !is_string($_POST['form_id'])) {
            Json::error('Invalid data');
        }

        if (empty($_POST['field_name']) or !is_string($_POST['field_name'])) {
            Json::error('Invalid data');
        }

        $code = $this->validateCode();
        $uri = $_POST['form_id'];
        $field_name = $_POST['field_name'];

        if (!isset($_SESSION['file_uploads'][$uri][$field_name][$code])) {
            Json::error('Invalid data');
        }

        return $_SESSION['file_uploads'][$uri][$field_name][$code];
    }


    public function clearSession()
    {
        Session::instance();

        $code = $this->validateCode();
        $uri = (string) @$_POST['uri'];
        $field_name = (string) @$_POST['field_name'];

        unset($_SESSION['file_uploads'][$uri][$field_name][$code]);

        if (empty($_SESSION['file_uploads'][$uri][$field_name])) {
            unset($_SESSION['file_uploads'][$uri][$field_name]);
        }
    }


    /**
     * Signals that the user would like to upload a file
     * Perform basic sanity checks and establish (or reset) their session state
     * @post string code Their upload code
     * @post string uri URI of the form processor, e.g. /user/process_register
     * @post string field_name The name of the field
     */
    public function uploadBegin()
    {
        $session = &$this->startSession();

        Json::confirm([]);
    }


    /**
     * Perform additional checks (beyond the checks already in {@see FileUploadController::uploadChunk}) on an uploaded chunk.
     * To be implemented by subclasses
     * @return void
     */
    public function extraChunkCheck()
    {
    }


    /**
     * Perform a size check on a set of uploaded chunks.
     * To be implemented by subclasses
     * @return void
     */
    public function chunkSizeCheck()
    {
    }


    /**
     * Perform a size check on a completed upload (after chunks have been stitched).
     * To be implemented by subclasses
     * @param string $temp_path Path to the temporary uploaded file
     * @throws FileUploadException if the file is smaller than the allowed maximum for the field.
     */
    public function fileSizeCheck($temp_path)
    {
    }


    /**
     * Perform a file extension check.
     * To be implemented by subclasses
     * @throws FileUploadException if the file extension is matches the requirements for the field.
     */
    public function fileExtCheck($temp_path)
    {
        $ext = strtolower(File::getExt($_GET['file']['name']));
        $ok = File::checkFileContentsExtension($temp_path, $ext);
        // N.B. $ok === null if there's no means of checking the content for a specific extension
        if ($ok === false) {
            throw new FileUploadException("File type doesn't match extension");
        }
    }


    /**
     * Save a single chunk of a multi-part file upload
     *
     * @post string chunk Binary data
     * @post int index Chunk index, 0-based
     * @post string code Unique code for this upload
     * @return void Outputs JSON
     */
    public function uploadChunk()
    {
        $upload_state = &$this->session();

        // TODO: implement rate-limiting and other anti-spam measures
        if (!is_dir(STORAGE_PATH . 'temp')) {
            Json::error('Temporary directory does not exist');
        }
        if (!is_writable(STORAGE_PATH . 'temp')) {
            Json::error('Temporary directory is not writable');
        }

        // Ensure a file chunk was actually uploaded
        if (empty($_FILES['chunk']) or $_FILES['chunk']['error'] !== UPLOAD_ERR_OK) {
            Json::error('Error uploading chunk');
        }

        // Check that there's actually content behind their upload
        if ($_FILES['chunk']['size'] <= 0) {
            Json::error('The uploaded file chunk was empty, there may have been a network fault during your upload');
        }

        $field_name = (string) @$_POST['field_name'];
        if (!$field_name) {
            Json::error('Invalid data');
        }

        $this->extraChunkCheck();

        $_POST['index'] = (int) @$_POST['index'];

        if (!$upload_state) {
            // @see uploadBegin
            Json::error('File upload wasn\'t started correctly');
        }

        $upload_state['size'] += $_FILES['chunk']['size'];

        $this->chunkSizeCheck();

        if ($_POST['index'] < 0 or $upload_state['index'] !== $_POST['index']) {
            Json::error('File chunks received out of sequence, you may have experienced a network error');
        }

        // Don't fill up the temp folder with too much junk
        if ($_POST['index'] > static::MAX_CHUNK_COUNT) {
            // Another likely malicious failure - wipe the data
            static::cleanupChunks($upload_state['code'], $upload_state['index']);

            Json::error('Maximum number of upload chunks exceeded');
        }

        $upload_state['index'] = $_POST['index'] + 1;

        $filename = STORAGE_PATH . 'temp/chunk-' . $_POST['code'] . '-' . $_POST['index'] . '.dat';
        $result = move_uploaded_file($_FILES['chunk']['tmp_name'], $filename);
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
     */
    public function uploadDone()
    {
        $upload_state = &$this->session();

        $field_name = (string) @$_POST['field_name'];

        $this->clearSession();

        $_POST['num'] = (int) $_POST['num'];
        if ($upload_state['index'] !== $_POST['num']) {
            Json::error('Invalid number of chunks uploaded');
        }

        $dest_filename = 'upload-' . time() . '-' . $_POST['code'] . '.dat';

        try {
            $total_size = $this->stitchChunks(STORAGE_PATH . 'temp/' . $dest_filename, $_POST['code'], $_POST['num']);

            if ($total_size !== $upload_state['size']) {
                Json::error('Final filesize didn\'t match upload size');
            }
        } catch (Exception $ex) {
            Json::error($ex->getMessage());
        }

        Json::confirm(array(
            'tmp_file' => $dest_filename,
        ));
    }


    /**
     * Returns the form for updating a file which has been uploaded
     *
     * @get array file File details, as per the File API; 'lastModifiedDate', 'name', 'size', 'type'
     * @get array result The full JSON response from the ajaxDragdropDone call
     * @get array form Details of the form shown above the drag-n-drop field
     * @return void Outputs HTML
     */
    public function uploadForm()
    {
        $temp_path = STORAGE_PATH . 'temp/' . $_GET['result']['tmp_file'];

        $_GET['file']['name'] = trim(Enc::cleanfunky($_GET['file']['name']));

        $error = false;

        if (!FileUpload::checkFilename($_GET['file']['name'])) {
            $error = 'This type of file cannot be uploaded';
        }

        if (!$error) {
            try {
                $this->fileSizeCheck($temp_path);
            } catch (FileUploadException $ex) {
                $error = $ex->getMessage();
            }
        }

        if (!$error) {
            try {
                $this->fileExtCheck($temp_path);
            } catch (FileUploadException $ex) {
                $error = $ex->getMessage();
            }
        }

        $view = new PhpView('sprout/file_confirm');
        if ($error) {
            $view->error = $error;

        } else {
            $data = [];
            $data['name'] = str_replace('_', ' ', File::getNoext($_GET['file']['name']));

            // Determine type from extension
            $data['type'] = File::getType($_GET['file']['name']);

            // Attempt to use the last modified date as the publish date
            $ts = strtotime(@$_GET['file']['lastModifiedDate']);
            if (!$ts) $ts = time();
            $data['date_published'] = date('Y-m-d', $ts);

            $view->tmp_file = $_GET['result']['tmp_file'];
            $view->orig_file = $_GET['file'];
            $view->data = $data;

            if ($data['type'] == FileConstants::TYPE_IMAGE) {
                try {
                    $view->shrunk_img = File::base64Thumb($temp_path, 200, 200);
                } catch (Exception $ex) {
                    $view->image_too_large = true;
                }
            }
        }

        echo $view->render();
    }


    /**
     * Stitch together the uploaded file from multiple chunks
     *
     * @param string $dest_filename The destination filename
     * @param string $code Upload code
     * @param string $num_chunks The number of chunks to stitch together
     * @return int Size of the final file in bytes
     */
    private function stitchChunks($dest_filename, $code, $num_chunks) {
        $out = @fopen($dest_filename, 'w');
        if (!$out) {
            throw new Exception('Unable to open file for writing');
        }

        // Copy chunks into the file. If anything goes wrong, the file will not be complete so bail
        $total_size = 0;
        $damaged = false;
        for ($i = 0; $i < $num_chunks; ++$i) {
            $chunk = STORAGE_PATH . 'temp/chunk-' . $code . '-' . $i . '.dat';
            if (!file_exists($chunk)) {
                $damaged = true;
                break;
            }

            $in = @fopen($chunk, 'r');
            if (!$in) {
                $damaged = true;
                break;
            }

            $result = @stream_copy_to_stream($in, $out);
            if (!$result) {
                $damaged = true;
                break;
            }

            // stream_copy_to_stream returns the number of bytes copied
            $total_size += $result;

            $result = @fclose($in);
            if (!$result) {
                $damaged = true;
                break;
            }
        }

        $result = fclose($out);
        if (! $result) {
            $damaged = true;
        }

        $this->cleanupChunks($code, $num_chunks);

        if ($damaged) {
            throw new Exception('One or more chunks failed to be read');
        }

        return $total_size;
    }


    /**
     * Clean up any left-over chunks
     * @param string $code The upload session code
     * @param int $num_chunks The number of chunks
     * @return void
     */
    protected function cleanupChunks($code, $num_chunks)
    {
        for ($i = 0; $i < $num_chunks; ++$i) {
            $chunk = STORAGE_PATH . 'temp/chunk-' . $code . '-' . $i . '.dat';
            @unlink($chunk);
        }
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
    public function uploadCancel()
    {
        if (isset($_POST['result']['tmp_file'])) {
            // Whole file was uploaded
            $result = preg_match('/^upload-[0-9]+-[a-zA-Z0-9]{32}.dat$/', $_POST['result']['tmp_file']);
            if (!$result) {
                die('Invalid');
            }

            @unlink(STORAGE_PATH . 'temp/' . $_POST['result']['tmp_file']);

        } elseif (isset($_POST['partial_upload']['code'])) {
            // Only part of the file has been uploaded
            $result = preg_match('/^[a-zA-Z0-9]{32}$/', $_POST['partial_upload']['code']);
            if (!$result) {
                die('Invalid');
            }

            $files = glob(STORAGE_PATH . 'temp/chunk-' . $_POST['partial_upload']['code'] . '-*.dat');
            foreach ($files as $file) {
                @unlink($file);
            }

        } else {
            die('Invalid');
        }

        echo 'Done';
    }
}
