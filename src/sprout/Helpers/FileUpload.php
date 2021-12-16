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

namespace Sprout\Helpers;

use Exception;
use DomainException;
use InvalidArgumentException;

use Sprout\Helpers\File;
use Sprout\Exceptions\FileUploadException;


/**
 * Used for managing backend processing of fields which have had data submitted via chunked file uploads,
 * i.e. where chunked upload data has been stitched together by {@see FileUploadController}
 */
class FileUpload
{
    /**
     * Verify that the temporary file specified by the user exists and has valid content,
     * and that the original file name is acceptable
     * This is to be used during form processing upon a POST submission.
     *
     * @example
     *      // Expects $_POST['avatar'][0] and $_POST['avatar_temp'][0] to both be set
     *      // Expects $_SESSION['file_uploads']['user_details']['avatar']['code'] to be set
     *      $result = FileUpload::verify('user_details', 'avatar', 0, ['jpg', 'gif', 'png']);
     *      // Result will be something like '/home/.../sprout/temp/xxxx'
     *
     * @param string $sess_key Session key related to the form, e.g. 'user-register';
     *        see $params['sess_key'] of {@see Fb::chunkedUpload}
     * @param string $field Name of field to process, e.g. 'image'
     * @param int $index Array index; 0 for the first file, 1 for the next and so on
     * @param array $allowed_exts Array of string file-types that are allowed
     * @post string $field
     * @post string $field . '_temp'
     * @return string Path to temporarily uploaded file; this can be used as the first argument to
     *         {@see File::moveUpload} to put the file in the desired permanent location.
     * @throws DomainException If the $_POST upload state is invalid.
     * @throws FileUploadException If there was an issue the uploader should know about, e.g. the file extension isn't permitted.
     *                             This exception always has front-end safe exception messages.
     */
    public static function verify($sess_key, $field, $index, array $allowed_exts)
    {
        $index = (int) $index;
        if (empty($_POST[$field][$index])) {
            throw new DomainException('Missing original name');
        }
        if (empty($_POST[$field . '_temp'][$index])) {
            throw new DomainException('Missing temp name');
        }

        // Check for tampered temp file name
        $temp = $_POST[$field . '_temp'][$index];
        $res = preg_match('/^upload-[0-9]+-([A-Za-z0-9]{32}).dat$/', $temp, $matches);
        if (!$res) {
            throw new DomainException('Invalid temp file name');
        }
        $upload_code = $matches[1];

        // Check file exists
        $src_path = APPPATH . 'temp/' . $temp;
        if (!file_exists($src_path)) {
            throw new DomainException('Temp file missing');
        }

        // Check to see that the user is actually the one who uploaded the file
        if (!isset($_SESSION['file_uploads'][$sess_key][$field][$upload_code])) {
            throw new FileUploadException('Upload session lost');
        }

        // Validate original file name
        if (!self::checkFilename($_POST[$field][$index])) {
            throw new FileUploadException("This type of file cannot be uploaded for security reasons");
        }

        $ext = strtolower(File::getExt($_POST[$field][$index]));
        if (!empty($allowed_exts) and !in_array($ext, $allowed_exts)) {
            throw new FileUploadException("Invalid file extension");
        }

        if (File::checkFileContentsExtension($src_path, $ext) === false) {
            throw new FileUploadException("File content doesn't match extension");
        }

        return $src_path;
    }


    /**
     * Check a given filename is allowed to be uploaded - blocks PHP files etc
     *
     * @param string $filename
     * @return bool True if allowed, false if not
     */
    public static function checkFilename($filename)
    {
        if (strpos($filename, '.') === false) {
            return false;
        }

        //            .-------- PHP ---------. .---- WIN ----. .------- LINUX ------.
        $execs = '/\.(php|phar|phtml|php[345s]|bat|com|cmd|exe|sh|bin|csh|ksh|out|run|htaccess)?$/i';
        if (preg_match($execs, $filename)) {
            return false;
        }

        return true;
    }


    /**
     * Generates a fake upload, including a session entry and a temp symlink, from an existing file on disk.
     *
     * This allows reuse of existing uploaded files on Form::chunkedUpload fields which support multiple files
     *
     * @param string $filename The name of a file which exists in the files dir, e.g. 'image.jpg'
     * @param string $session_key The session key used for file uploads, e.g. 'user-register'
     *        (see {@see Fb::chunkedUpload})
     * @param string $field_name The name of the field supplied to {@see Fb::chunkedUpload}, e.g. 'photos'
     * @return string The filename of the newly generated symlink, which matches the naming format of files
     *         uploaded via the chunked uploader
     * @throws InvalidArgumentException if the filename is invalid
     */
    public static function generateFromDisk($filename, $session_key, $field_name)
    {
        if (!File::exists($filename)) {
            throw new InvalidArgumentException('Invalid filename');
        }

        $real_file = DOCROOT . 'files/' . $filename;

        $code = Security::randStr(32);

        $temp_file = 'upload-' . time() . '-' . $code . '.dat';
        if (!symlink(DOCROOT . 'files/' . $filename, APPPATH . 'temp/' . $temp_file)) {
            throw new InvalidArgumentException('Failed to create symlink');
        }

        $_SESSION['file_uploads'][$session_key][$field_name][$code] = ['size' => filesize($real_file)];

        return $temp_file;
    }


    /**
     * Get a human friendly error string from the `$_FILES[*]['error]` value.
     *
     * @param int $error
     * @return string
     */
    public static function getErrorMessage($error)
    {
        static $errors = [
            UPLOAD_ERR_OK => 'No error.',
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds upload_max_filesize (server).',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds MAX_FILE_SIZE (form).',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file.',
            UPLOAD_ERR_EXTENSION => 'An extension stopped the file upload.',
        ];

        $message = $errors[$error] ?? 'An unknown error occurred.';

        if ($error == UPLOAD_ERR_INI_SIZE) {
            $max_filesize = ini_get('upload_max_filesize');
            $max_filesize = File::humanSize($max_filesize);
            $message = strtr($message, 'upload_max_filesize', $max_filesize);
        }
        else if ($error == UPLOAD_ERR_FORM_SIZE) {
            $max_filesize = (int) $_POST['MAX_FILE_SIZE'] ?? $_POST['max_file_size'] ?? 0;
            $max_filesize = File::humanSize($max_filesize);
            $message = strtr($message, 'MAX_FILE_SIZE', $max_filesize);
        }

        return $message;
    }
}
