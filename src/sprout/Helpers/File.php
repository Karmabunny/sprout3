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
use InvalidArgumentException;

use Kohana;

use karmabunny\pdb\Exceptions\RowMissingException;
use Kohana_Exception;
use Sprout\Exceptions\ImageException;
use Throwable;

/**
 * Methods for working with files, including images
 */
class File
{
    /**
     * Gets the details of a file using its id.
     *
     * Uses a prepared statement for speed when doing repeated queries.
     *
     * N.B. If the file entry doesn't exist, a reference to 'missing.png' is returned
     *
     * @param int $id The ID in the files table
     * @return array Contains keys 'filename' and 'date_file_modified'
     */
    public static function getDetails($id)
    {
        static $prepared_q = null;

        if (!$prepared_q) {
            $q = "SELECT filename, date_file_modified FROM ~files WHERE id = ?";
            $prepared_q = Pdb::prepare($q);
        }

        try {
            return Pdb::execute($prepared_q, [$id], 'row');
        } catch (RowMissingException $ex) {
            return ['filename' => 'missing.png', 'date_file_modified' => '1970-01-01 00:00:00'];
        }
    }


    /**
     * For a given file, returns the filename to use to retrieve a resized version of the file
     *
     * @param string $original Original image name, e.g. 123_example.jpg
     * @param string $size_name One of the 'file.image_transformations' config options, e.g. 'small'
     * @param string $force_ext If set, extension is set to this
     * @return string Name of resized file, e.g. 123_example.small.jpg
     */
    static function getResizeFilename($original, $size_name, $force_ext = null)
    {
        $parts = explode('.', $original);
        $ext = array_pop($parts);
        $file_noext = implode('.', $parts);

        if ($force_ext) {
            $ext = $force_ext;
        }

        return "{$file_noext}.{$size_name}.{$ext}";
    }


    /**
     * Gets the (final) extension from a file name, in lowercase
     * @param string $filename Full filename, e.g. 'image.large.jpg', '/path/to/image.png'
     * @return string Extension, excluding leading dot, e.g. 'jpg', 'png'
     */
    static function getExt($filename)
    {
        if (empty($filename)) return null;

        $parts = explode('.', $filename);
        return strtolower(array_pop($parts));
    }


    /**
     * Determines the file type from a file name by examining its extension
     * @param string $filename The file name
     * @return int One of the FileConstants::TYPE_* values, see {@see FileConstants}.
     *         If the type couldn't be determined, FileConstants::TYPE_OTHER is returned.
     */
    static function getType($filename)
    {
        $ext = self::getExt($filename);
        foreach (FileConstants::$type_exts as $type => $exts) {
            if (in_array($ext, $exts)) return $type;
        }
        return FileConstants::TYPE_OTHER;
    }


    /**
    * For a given file, returns the name without an ext
    *
    * @param string Full filename
    * @return string Base part of filename
    **/
    static function getNoext($original)
    {
        $parts = explode('.', $original);
        array_pop($parts);
        return implode('.', $parts);
    }


    /**
    * Converts a file size, in bytes, into a human readable form (with trailing kb, mb, etc)
    *
    * @param int $size Size in bytes
    * @return string
    **/
    static function humanSize($size)
    {
        static $types = array(' bytes', ' kb', ' mb', ' gb', ' tb');

        $type = 0;
        while ($size > 1024) {
            $size /= 1024;
            $type++;
            if ($type > 5) break;
        }

        return round($size, 1) . $types[$type];
    }


    /**
    * Make a filename sane - strip lots of characters which create problems
    *
    * @param string $filename Filename which may or may not already be sane
    * @return string Sane filename
    **/
    static function filenameMakeSane($filename)
    {
        $parts = explode('.', $filename);

        $ext = '';
        if (count($parts) > 1) $ext = array_pop($parts);
        $filename = implode('', $parts);

        $filename = preg_replace('![/\\\]!', '', $filename);
        $filename = preg_replace('/\s/', '_', $filename);
        $filename = strtolower($filename);
        $filename = preg_replace('/[^-_a-z0-9.]/', '', $filename);
        $filename = preg_replace('/[-_]{2,}/', '_', $filename);
        $filename = trim($filename, '_');

        if ($filename == '') $filename = time();

        if ($ext) $filename .= '.' . strtolower($ext);

        return $filename;
    }


    /**
    * Return the backend library to use for many file operations
    *
    * @return FilesBackend
    **/
    private static function backend()
    {
        static $backend;

        if ($backend === null) {
            $backend = new FilesBackendDirectory();
        }

        return $backend;
    }


    /**
    * Returns the public URL for a given file. Does not include domain.
    *
    * @param string $filename The name of the file in the repository
    * @return string
    **/
    public static function url($filename)
    {
        return self::backend()->absUrl($filename);
    }


    /**
    * Returns the relative public URL for a given file.
    * Doesn't contain ROOT/ or domain. Use for content areas.
    *
    * @param string $filename The name of the file in the repository
    * @return string
    **/
    public static function relUrl($filename)
    {
        return self::backend()->relUrl($filename);
    }


    /**
    * Returns the public URL for a given file, including domain.
    *
    * @param string $filename The name of the file in the repository
    * @return string
    **/
    public static function absUrl($filename)
    {
        return self::backend()->absUrl($filename);
    }


    /**
     * Returns the relative URL for a dynamically resized image.
     *
     * Size formatting is as per {@see File::parseSizeString}, e.g. c400x300
     *
     * @param int $id ID or filename from record in files table
     * @param string $size A code as per {@see File::parseSizeString}
     * @return string HTML-safe relative URL, e.g. file/resize/c400x300/123_example.jpg
     */
    public static function resizeUrl($id, $size)
    {
        return self::backend()->resizeUrl($id, $size);
    }


    /**
     * Gets the relative URL for a fixed or dynamically resized image
     *
     * @param int|string $id ID or filename from record in files table
     * @param string $size_name The size you want, e.g. 'small', 'banner', 'c100x100', etc.
     *        The value can either be a size name from the 'file.image_transformations' config option,
     *        or be a resize code as per {@see File::parseSizeString}
     * @param string $force_ext Force the ext to a specific value, e.g. 'jpg'
     * @param bool $create_if_missing For numeric size names (e.g. 'c100x100'), causes a resize for any missing files
     * @return string File URL, e.g. 'file/download/123/small' or 'files/123_test.c100x100.jpg'
     */
    public static function sizeUrl($id, $size_name, $force_ext = null, $create_if_missing = false)
    {
        if (preg_match('/^[0-9]+$/', (string) $id)) {
            if (preg_match('/^[a-z_]+$/', $size_name)) {
                return "file/download/{$id}/{$size_name}";
            }
            $file_details = File::getDetails($id);
            $filename = $file_details['filename'];
        } else {
            $filename = $id;
            if (!self::exists($filename)) {
                try {
                    $filename = File::lookupReplacementName($filename);
                } catch (Exception $ex) {
                    return 'files/missing.png';
                }
            }
        }

        $url = File::getResizeFilename($filename, $size_name, $force_ext);

        $pattern = '/^[crm][0-9]+x[0-9]+(?:-[lcr][tcb](?:~[0-9]+)?)?$/';
        if ($create_if_missing and preg_match($pattern, $size_name) and !File::exists($url)) {
            File::createSize($filename, $size_name, $force_ext);
        }

        return File::relUrl($url);
    }


    /**
    * Returns TRUE if the file exists, and FALSE if it does not
    *
    * @param string $filename The name of the file in the repository. Deprecated: an ID value is also accepted in order
    *        to support older modules; such modules need to be updated to avoid an extra database lookup.
    * @return bool TRUE if the file exists, and FALSE if it does not
    **/
    public static function exists($filename)
    {
        if (preg_match('/^[0-9]+$/', $filename)) {
            $id = $filename;
            try {
                $filename = Pdb::q('SELECT filename FROM ~files WHERE id = ?', [$id], 'val');
            } catch (RowMissingException $ex) {
                return false;
            }
        }
        return self::backend()->exists($filename);
    }


    /**
    * Returns the size, in bytes, of the specified file
    *
    * @param string $filename The name of the file in the repository
    * @return int File size in bytes
    **/
    public static function size($filename)
    {
        if (!self::exists($filename)) {
            try {
                $filename = File::lookupReplacementName($filename);
            } catch (RowMissingException $ex) {
                // No problem, return original (broken) URL
            }
        }

        return self::backend()->size($filename);
    }


    /**
    * Returns the modified time, in unix timestamp format, of the specified file
    *
    * @param string $filename The name of the file in the repository
    * @return int Modified time as a unix timestamp
    **/
    public static function mtime($filename)
    {
        return self::backend()->mtime($filename);
    }


    /**
    * Sets access and modification time of file
    * @return bool True if successful
    **/
    public static function touch($filename)
    {
        return self::backend()->touch($filename);
    }


    /**
    * Returns the size of an image, or false on failure.
    *
    * Output format is the same as getimagesize, but will be at a minimum:
    *   [0] => width, [1] => height, [2] => type
    *
    * @param string $filename The name of the file in the repository
    * @return array
    **/
    public static function imageSize($filename)
    {
        return self::backend()->imageSize($filename);
    }


    /**
     * Delete a file
     * If the file is an image, any resized variants (e.g. 'small', 'medium' etc.) are deleted too
     * @param string $filename The name of the file in the repository, e.g. '123_some_image.jpg'
     * @return bool True if the deletion of the main file succeeded
     */
    public static function delete($filename)
    {
        File::deleteCache($filename);
        $ext = File::getExt($filename);
        $base = File::getNoExt($filename);
        $transforms = Kohana::config('file.image_transformations');
        foreach ($transforms as $type => $params) {
            self::backend()->delete("{$base}.{$type}.{$ext}");
        }
        return self::backend()->delete($filename);
    }


    /**
     * Delete a directory. Must be empty to succeed
     *
     * @param string $directory The path of the directory to delete, relative to baseDir
     * @return bool True if the deletion of the directory succeeded
     */
    public static function deleteDir($directory)
    {
        return self::backend()->deleteDir($directory);
    }


    /**
     * Create a directory
     *
     * @param string $directory The path of the directory to make, relative to baseDir
     * @return bool True if the creation of the directory succeeded
     */
    public static function mkDir($directory)
    {
        return self::backend()->mkDir($directory);
    }


    /**
     * @deprecated Delete cached versions of a file. Use file transforms.
    *
    * @param string $filename The name of the file in the repository
    **/
    public static function deleteCache($filename)
    {
        $filename = preg_replace('![^-_a-z0-9.]!', '', $filename);

        // Old cache structure
        $files = glob(STORAGE_PATH . 'cache/resize-*-' . $filename);
        foreach ($files as $file) {
            if (is_dir($file)) continue;

            try {
                unlink($file);
            } catch (Throwable $ex) {
                // Log and continue
                Kohana::logException($ex);
            }
        }

        // Updated cache structure
        $files = glob(WEBROOT . "files/resize/*/{$filename}");
        foreach ($files as $file) {
            if (is_dir($file)) continue;

            try {
                unlink($file);
            } catch (Throwable $ex) {
                // Log and continue
                Kohana::logException($ex);
            }
        }
    }


    /**
    * Returns all files which match the specified mask.
    * I have a feeling this returns other sizes (e.g. .small) as well - which may not be ideal.
    *
    * @param string $mask Files to find. Supports wildcards such as * and ?
    * @param int $depth How deep to recursively search subdirectories, 0 to disable
    **/
    public static function glob($mask, $depth = 0)
    {
        return self::backend()->glob($mask, $depth);
    }


    /**
    * This is the equivalent of the php readfile function
    *
    * @param string $filename The name of the file in the repository
    **/
    public static function readfile($filename)
    {
        return self::backend()->readfile($filename);
    }


    /**
    * Returns file content as a string. Basically the same as file_get_contents
    *
    * @param string $filename The name of the file in the repository
    * @return string $content The content
    **/
    public static function getString($filename)
    {
        return self::backend()->getString($filename);
    }


    /**
    * Saves file content as a string. Basically the same as file_put_contents
    *
    * @param string $filename The name of the file in the repository
    * @param string $content The content
    * @return bool True on success, false on failure
    **/
    public static function putString($filename, $content)
    {
        return self::backend()->putString($filename, $content);
    }


    /**
    * Saves file content from a stream. Basically just fopen/stream_copy_to_stream/fclose
    *
    * @param string $filename The name of the file in the repository
    * @param resource $stream The stream to copy content from
    * @return bool True on success, false on failure
    **/
    public static function putStream($filename, $stream)
    {
        return self::backend()->putStream($filename, $stream);
    }


    /**
    * Saves file content from an existing file
    *
    * @param string $filename The name of the file in the repository
    * @param string $existing The existing file on disk
    * @return bool True on success, false on failure
    **/
    public static function putExisting($filename, $existing)
    {
        return self::backend()->putExisting($filename, $existing);
    }


    /**
    * Moves an uploaded file into the repository.
    * Returns TRUE on success, FALSE on failure.
    *
    * @param string $src Source filename
    * @param string $filename The name of the file in the repository
    * @return bool True on success, false on failure
    **/
    public static function moveUpload($src, $filename)
    {
        return self::backend()->moveUpload($src, $filename);
    }


    /**
    * Create a copy of the file in a temporary directory.
    * Don't forget to File::destroy_local_copy($temp_filename) when you're done!
    *
    * @param string $filename The file to copy into a temporary location
    * @return string Temp filename or NULL on error
    **/
    public static function createLocalCopy($filename)
    {
        return self::backend()->createLocalCopy($filename);
    }


    /**
    * Remove a local copy of a file
    * Call this once you're done with the local copy
    *
    * @param string $temp_filename The filename returned by createLocalCopy
    **/
    public static function cleanupLocalCopy($temp_filename)
    {
        return self::backend()->cleanupLocalCopy($temp_filename);
    }


    /**
     * Fetch files base directory path
     *
     * @return string
     */
    public static function baseDir()
    {
        return self::backend()->baseDir();
    }


    /**
    * Searches the whole database to find all records in all columns
    * which contain a given filename.
    *
    * The search looks in VARCHAR columns with more than 200 chars (exact match)
    * and in TEXT columns (contains match)
    *
    * Return value is an array of matches, in the format:
    *   [0] => table
    *   [1] => record id
    *   [2] => record name, if available
    *
    * @param string $filename The name of the file to search
    **/
    public static function findUsage($filename)
    {
        $pf = Pdb::prefix();

        $all_params = [
            'filename' => $filename,
            'like_filename' => Pdb::likeEscape($filename),
        ];


        $size_names = Kohana::config('file.image_transformations');
        foreach ($size_names as $size_name => $transform) {
            Pdb::validateIdentifier($size_name);
            $sizes[] = $size_name;
            $all_params["resize_{$size_name}"] = Pdb::likeEscape(File::getResizeFilename($filename, $size_name));
        }

        // Tables to not show results for
        $badtables = array(
            $pf . 'files',
            $pf . 'history_items',
            $pf . 'cronjobs',
            $pf . 'workerjobs',
            $pf . 'pages',
            $pf . 'page_revisions',
            $pf . 'page_widgets',
            $pf . 'exception_log',
        );

        // Iterate the tables
        $q = "SHOW TABLE STATUS";
        $db_tables = Pdb::q($q, [], 'arr');

        $queries = array();
        foreach ($db_tables as $tbl) {
            if (strpos($tbl['Name'], $pf) !== 0) continue;
            if (in_array($tbl['Name'], $badtables)) continue;

            // Grab the columns
            $q = "SHOW COLUMNS FROM {$tbl['Name']}";
            $db_cols = Pdb::q($q, [], 'arr');

            // Build a where clause
            $cols = [];
            $where = [];
            $params = [];
            foreach ($db_cols as $col) {
                if ($col['Field'] === 'id') $cols[] = 'id';
                if ($col['Field'] === 'name') $cols[] = 'name';

                if (preg_match('/VARCHAR\(([0-9]+)\)/i', $col['Type'], $matches) and $matches[1] >= 200) {
                    $where[] = "{$col['Field']} = :filename";
                    if (!isset($params['filename'])) $params['filename'] = $all_params['filename'];

                } else if (preg_match('/TEXT/i', $col['Type'])) {
                    $where[] = "{$col['Field']} LIKE CONCAT('%', :like_filename, '%')";
                    if (!isset($params['like_filename'])) $params['like_filename'] = $all_params['like_filename'];

                    foreach ($sizes as $size_name) {
                        $param_name = "resize_{$size_name}";
                        $where[] = "{$col['Field']} LIKE CONCAT('%', :{$param_name}, '%')";
                        if (!isset($params[$param_name])) $params[$param_name] = $all_params[$param_name];
                    }
                }
            }

            if (count($cols) == 0 or count($where) == 0) continue;

            $q = 'SELECT ' . implode(', ', $cols) . ' FROM ' . $tbl['Name'] . ' WHERE ' . implode(' OR ', $where);
            $queries[$tbl['Name']] = [$q, $params];
        }

        // Spekky query for page revisions
        $where = [];
        $params = $all_params;
        unset($params['filename']);

        $where[] = "widget.settings LIKE CONCAT('%', :like_filename, '%')";
        foreach ($sizes as $size_name) {
            $param_name = "resize_{$size_name}";
            $where[] = "widget.settings LIKE CONCAT('%', :{$param_name}, '%')";
        }
        $where[] = "page.banner LIKE CONCAT('%', :like_filename, '%')";
        $q = "SELECT DISTINCT page.id, page.name
            FROM ~page_revisions AS rev
            INNER JOIN ~page_widgets AS widget ON rev.id = widget.page_revision_id
                AND widget.area_id = 1 AND widget.type = 'RichText'
            INNER JOIN ~pages AS page ON rev.page_id = page.id
            WHERE (" . implode(' OR ', $where) . ')
                AND rev.status = :live';
        $params['live'] = 'live';
        $queries['sprout_pages'] = [$q, $params];

        // Spekky query for gallery images
        if (Sprout::moduleInstalled('galleries2')) {
            $where = [];
            $params = $all_params;
            unset($params['filename']);
            $where[] = 'f.filename LIKE :like_filename';
            foreach ($sizes as $size_name) {
                $param_name = "resize_{$size_name}";
                $where[] = "f.filename LIKE :{$param_name}";
            }

            $q = "SELECT g.id, g.name
                FROM ~galleries AS g
                INNER JOIN ~gallery_sources AS src ON src.gallery_id = g.id AND src.type = :type_image
                INNER JOIN ~files_cat_join AS joiner ON joiner.cat_id = src.category
                INNER JOIN ~files AS f ON joiner.file_id = f.id
                WHERE (" . implode(' OR ', $where) . ')';
            $params['type_image'] = GalleryConstants::SOURCE_FILES_IMAGE;
            $queries['sprout_galleries'] = [$q, $params];
        }

        // Run the queries
        ksort($queries);
        $output = array();
        foreach ($queries as $table => $q_and_params) {
            list($q, $params) = $q_and_params;
            $res = Pdb::q($q, $params, 'pdo');

            // Save results
            foreach ($res as $row) {
                $output[] = array(
                    substr($table, strlen($pf)),
                    $row['id'],
                    (isset($row['name']) ? $row['name'] : 'Record #' . $row['id']),
                );
            }
            $res->closeCursor();
        }

        return $output;
    }


    /**
    * Return the mimetype for a given filename.
    *
    * Only uses the extension - doesn't actually check the file
    * If you need deep checking, take a look at {@see File::checkFileContentsExtension}
    * If the extension is unrecognised, returns 'application/octet-stream'.
    *
    * @param string $filename
    * @return string E.g. 'image/png'
    **/
    public static function mimetype($filename)
    {
        $ext = File::getExt($filename);
        return (isset(Constants::$mimetypes[$ext]) ? Constants::$mimetypes[$ext] : 'application/octet-stream');
    }


    /**
     * Checks file contents match the extension
     *
     * @param string $filename The full path/filename of the file to check
     * @param string $ext The supplied file extension
     * @return bool|null True if the file matches, false if it doesn't, null if there's no check for that type
     */
    public static function checkFileContentsExtension($filename, $ext)
    {
        $ext = strtolower(trim($ext));

        switch ($ext) {
            case 'jpg':
            case 'jpeg':
            case 'jpe':
            case 'jif':
            case 'jfif':
            case 'jfi':
                $size = getimagesize($filename);
                return ($size[2] == IMAGETYPE_JPEG);

            case 'png':
                $size = getimagesize($filename);
                return ($size[2] == IMAGETYPE_PNG);

            case 'webp':
                $size = getimagesize($filename);
                return ($size[2] == IMAGETYPE_WEBP);

            case 'gif':
                $size = getimagesize($filename);
                return ($size[2] == IMAGETYPE_GIF);

            case 'pdf':
                $fp = fopen($filename, 'r');
                $magic = fread($fp, 4);
                fclose($fp);
                return ($magic == '%PDF');
        }

        return null;
    }


    /**
     * Get the content-type of a file using magic mime.
     *
     * This is _NOT_ limited to the whitelist of mime types described in the
     * Constants. Use this with care.
     *
     * Note mime_content_type() inspects file contents and can't always
     * determine css/js files correctly, this is a hack fix for that.
     *
     * https://stackoverflow.com/a/17736797/7694753
     *
     * @param string $path
     * @return string|null null if unknown
     */
    public static function mimetypeExtended($path)
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        switch($extension) {
            case 'css':
                return 'text/css; charset=UTF-8';

            case 'js':
                return 'application/javascript; charset=UTF-8';

            case 'svg':
                 return 'image/svg+xml; charset=UTF-8';
        }

        $info = finfo_open(FILEINFO_MIME);
        if (!$info) return null;

        return finfo_file($info, $path) ?: null;
    }


    /**
    * Prompts a user to download a file, and terminates the script
    * Sets all the right headers and stuff, doesn't set caching/expires/etc headers though.
    *
    * @param string $filename The name of the file in the repository
    * @param string $download_name An optional alternate name to provide to the user
    **/
    public static function download($filename, $download_name = null)
    {
        $size = File::size($filename);
        $mime = File::mimetype($filename);

        // Set some general headers
        header('Content-type: ' . $mime);
        header('Content-length: ' . $size);
        header('Content-disposition: attachment; filename="' . addslashes($download_name ? $download_name : $filename) . '"');

        // MSIE needs "public" when under SSL - http://support.microsoft.com/kb/316431
        header('Pragma: public');
        header('Cache-Control: public, max-age=1');

        Kohana::closeBuffers();
        File::readfile($filename);
        exit();
    }


    /**
    * Parse the size string used in file/resize and some helpers.
    *
    * Syntax: [crm]{number}x{number}(-[lcr][tcb])(~{number})
    *         Type  Width    Height    Crop X Y     Quality
    *
    * Returns an array.
    *   [0] type, either 'r', 'c' or 'm'
    *       r = resize, up or down, try to fill the area requested
    *       c = crop, resulting file will always be the width and height requested
    *       m = resize down only
    *   [1] width
    *   [2] height
    *   [3] x position, 'left', 'center' or 'right'
    *   [4] y position, 'top', 'center' or 'bottom'
    *   [5] jpeg quality, 0 = worst, 100 = best
    *
    * Returns an empty array on error (so you can use list() safely)
    *
    * @param $str Size string
    * @return array
    **/
    public static function parseSizeString($str)
    {
        $result = preg_match('/^([crm])([0-9]+)x([0-9]+)(?:-([lcr])([tcb]))?(?:~([0-9]+))?$/', $str, $matches);
        if (! $result) return array();
        array_shift($matches);

        $matches[1] = (int) $matches[1];
        $matches[2] = (int) $matches[2];

        if (empty($matches[3])) {
            $matches[3] = 'center';
            $matches[4] = 'center';
        } else {
            if ($matches[3] == 'l') $matches[3] = 'left';
            if ($matches[3] == 'c') $matches[3] = 'center';
            if ($matches[3] == 'r') $matches[3] = 'right';
            if ($matches[4] == 't') $matches[4] = 'top';
            if ($matches[4] == 'c') $matches[4] = 'center';
            if ($matches[4] == 'b') $matches[4] = 'bottom';
        }

        if (empty($matches[5])) $matches[5] = null;

        return $matches;
    }


    /**
    * Create a resized version of the specified file at a given size.
    *
    * The size is specified the same as the file/resize method (rXXXxYYY or cXXXxYYY)
    * The output filename will be basename.size.ext
    *
    * The files can be used with `size_url` or `get_resize_filename` on the front-end.
    *
    * @param string $filename The original filename
    * @param string $size The size in on-the-fly resize format
    * @param string $force_ext Force a different ext on save, such as jpg for banners
    * @return bool True on success, false on failure
    **/
    public static function createSize($filename, $size, $force_ext = null)
    {
        if (! File::exists($filename)) {
            return false;
        }

        $temp_filename = File::createLocalCopy($filename);
        if (! $temp_filename) {
            return false;
        }

        $img = new Image($temp_filename);

        // Determine the size
        list($type, $width, $height, $crop_x, $crop_y, $quality) = File::parseSizeString($size);

        if ($type == 'm') {
            // Max size
            $file_size = File::imageSize($filename);

            if ($width == 0) $width = PHP_INT_MAX;
            if ($height == 0) $height = PHP_INT_MAX;

            if ($file_size[0] > $width or $file_size[1] > $height) {
                $img->resize($width, $height);
            }

        } else if ($type == 'r') {
            // Resize
            $img->resize($width, $height);

        } else if ($type == 'c') {
            // Crop
            if ($width / $img->width > $height / $img->height) {
                $master = Image::WIDTH;
            } else {
                $master = Image::HEIGHT;
            }

            $img->resize($width, $height, $master);
            $img->crop($width, $height, $crop_y, $crop_x);

        } else {
            // What?
            File::cleanupLocalCopy($temp_filename);
            throw new Exception('Incorrect resize type');
        }

        if ($quality) {
            $img->quality($quality);
        }

        $sized_filename = self::getResizeFilename($filename, $size, $force_ext);

        $result = $img->save();
        if (! $result) return false;

        $result = File::putExisting($sized_filename, $temp_filename);
        if (! $result) return false;

        File::cleanupLocalCopy($temp_filename);

        $result = Replication::postFileUpdate($sized_filename);
        if (! $result) return false;

        return true;
    }


    /**
     * Will we have enough RAM to do the resizes?
     *
     * @throws Exception If we don't
     * @param array $dimensions Dimensions of the original image; 0 = width, 1 => height
     * @return void
     */
    public static function calculateResizeRam(array $dimensions)
    {
        $origin_ram = $dimensions[0] * $dimensions[1] * 4;
        $memory_limit = Sprout::getMemoryLimit();

        $sizes = Kohana::config('file.image_transformations');
        foreach ($sizes as $size_name => $transform) {
            $size_ram = 0;
            foreach ($transform as $t) {
                $size_ram += $t->estimateRamRequirement();
            }
            $total_ram_req = $origin_ram + $size_ram;

            if ($total_ram_req > $memory_limit) {
                $total_ram_req = str_replace('&nbsp;', ' ', File::humanSize($total_ram_req));
                $memory_limit = str_replace('&nbsp;', ' ', File::humanSize($memory_limit));

                throw new Exception(
                    "Unable to create size '{$size_name}'; expected RAM requirements "
                    . "of {$total_ram_req} exceeds memory limit of {$memory_limit}"
                );
            }
        }
    }


    /**
     * Create default image sizes as per the config parameter 'file.image_transformations'
     *
     * The transformed files get saved onto the server.
     * If any of the transformations in a transform-group fails,
     * the whole group will fail and the file will not be saved.
     *
     * @param string|int $filename The file or ID to create sizes for
     * @param string $specific_size Optional parameter to process only a single size
     * @return void
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public static function createDefaultSizes($filename, $specific_size = null)
    {
        $sizes = Kohana::config('file.image_transformations');

        if ($specific_size) {
            if (!isset($sizes[$specific_size])) {
                throw new InvalidArgumentException('Invalid param $specific_size; size doesn\'t exist.');
            }

            $sizes = array($specific_size => $sizes[$specific_size]);
        }

        /** @var ImageTransform[][] $sizes [ name => [transforms] ] */

        $query = Pdb::find('files')
            ->select('filename', 'author', 'embed_author');

        // Look up image in DB
        // - convert ID -> filename
        // - see if see if it needs author attribution
        if (preg_match('/^[0-9]+$/', $filename)) {
            $query->where(['id' => $filename]);
        } else {
            $query->where(['filename' => $filename]);
        }

        $row = $query->one();

        $filename = $row['filename'];

        if ($row['author'] and $row['embed_author']) {
            $embed_text = $row['author'];
        } else {
            $embed_text = false;
        }

        foreach ($sizes as $size_name => $transform) {
            $temp_filename = File::createLocalCopy($filename);
            if (!$temp_filename) {
                throw new Exception('Unable to create temporary copy for processing');
            }

            $img = new Image($temp_filename);

            // Do the transforms
            $width = $height = 0;
            foreach ($transform as $t) {
                $res = $t->transform($img);

                if ($t instanceof ResizeImageTransform) {
                    $dims = $t->getDimensions();
                    $width = $dims['width'];
                    $height = $dims['height'];
                }

                // If an individual transform fails,
                // cancel the transforming for this group
                // The other transform groups will still be processed though
                if ($res == false) {
                    File::cleanupLocalCopy($temp_filename);
                    continue 2;
                }
            }

            if ($embed_text) $img->addText($embed_text);

            $result = $img->save();
            if (! $result) {
                throw new Exception('Save of new image failed');
            }

            // Import temp file into media repo
            $resize_filename = self::getResizeFilename($filename, $size_name);
            $result = File::putExisting($resize_filename, $temp_filename);
            if (! $result) {
                throw new Exception('Image copy of new file into repository failed');
            }

            File::cleanupLocalCopy($temp_filename);
        }
    }


    /**
    * Do post-processing after a file upload
    *
    * @throw Exception
    * @param string $filename The name of hte new file
    * @param int $file_id The ID of the new file
    * @param int $file_type The new file type - e.g. DOCUMENT or IMAGE; see FileConstants
    **/
    public static function postUploadProcessing($filename, $file_id, $file_type)
    {
        $file_id = (int) $file_id;
        $file_type = (int) $file_type;

        $update_data = ['filename' => $filename];
        Pdb::update('files', $update_data, ['id' => $file_id]);

        switch ($file_type) {
            case FileConstants::TYPE_DOCUMENT:
                $ext = FileIndexing::getExt($filename);
                if (FileIndexing::isExtSupported($ext)) {
                    $update_data = [];
                    $update_data['plaintext'] = FileIndexing::getPlaintext($filename, $ext);
                    Pdb::update('files', $update_data, ['id' => $file_id]);
                }
                break;

            case FileConstants::TYPE_IMAGE:
                File::createDefaultSizes($filename);
                break;
        }
    }


    /**
     * Generates a cropped, base-64 encoded thumbnail of an image
     * @param string $file_path Path to the original image
     * @param int $width Width to use for thumbnail
     * @param int $height Height to use for thumbnail
     * @return array Has the following keys:
     *         'encoded_thumbnail': Base-64 encoded thumbnail
     *         'original_width': width of the original image
     *         'original_height': height of the original image
     * @throws ImageException read the error code for the reason:
     *   - `UNSUPPORTED_IMAGE_TYPE`: if image is not a valid image
     *   - `IMAGE_TOO_LARGE`: if not enough RAM to generate thumbnail
     */
    public static function base64Thumb($file_path, $width, $height)
    {
        if (!file_exists($file_path)) {
            throw new ImageException('Image file does not exist');
        }

        $size = getimagesize($file_path);

        // One can only assume...
        if (!$size) {
            throw new ImageException('Unsupported image type', ImageException::IMAGE_UNKNOWN_TYPE);
        }

        list($w, $h) = $size;
        $current_size = new ResizeImageTransform($w, $h);
        $resize = new ResizeImageTransform($width, $height);

        $resize_ram = $current_size->estimateRamRequirement();
        $resize_ram += $resize->estimateRamRequirement();

        if ($resize_ram > Sprout::getMemoryLimit()) {
            throw new ImageException('Not enough RAM to generate thumbnail', ImageException::IMAGE_TOO_LARGE);
        }

        try {
            $thumbnail = Image::base64($file_path, $resize);
        } catch (Kohana_Exception $ex) {
            $tr = $ex->getTranslation();
            if ($tr[0] === 'image.type_not_allowed') {
                throw new ImageException('Unsupported image type: ' . $tr[1], ImageException::IMAGE_UNKNOWN_TYPE, $ex);
            }
            throw new ImageException('Error generating thumbnail', 0, $ex);
        }

        return [
            'encoded_thumbnail' => $thumbnail,
            'original_width' => $w,
            'original_height' => $h,
        ];
    }


    /**
     * Checks the database for an updated URL path for a file.
     * I.e. a file which has been replaced by the admin 'replace file' tool.
     * @param string $filename Name of file, with no path, e.g. 123_image.jpg
     * @return string Updated URL (relative to root), e.g. 'files/123_new_image.png' or 'file/download/123'
     * @throws RowMissingException If no updated path was found
     * @throws InvalidArgumentException If linkspec in DB invalid
     */
    public static function lookupReplacementUrl($filename)
    {
        $q = "SELECT destination
            FROM ~redirects
            WHERE path_exact = ?";
        $dest_spec_json = Pdb::q($q, ['files/' . $filename], 'val');
        $dest_spec = json_decode($dest_spec_json, true);
        if ($dest_spec['class'] != '\\Sprout\\Helpers\\LinkSpecInternal') {
            throw new InvalidArgumentException("Link spec doesn't match expected value");
        }
        return $dest_spec['data'];
    }


    /**
     * Checks the database for an updated name for a file.
     *
     * This only works for full-sized images, e.g. 123_example.jpg, not 123_example.small.jpg
     *
     * @param string $filename Name of file, with no path, e.g. 123_image.jpg
     * @return string Updated filename, e.g. '123_new_image.png'
     * @throws RowMissingException If no updated path was found
     * @throws InvalidArgumentException If linkspec in DB invalid
     */
    public static function lookupReplacementName($filename)
    {
        $replacement = self::lookupReplacementUrl($filename);

        if (preg_match('#^file/download/([0-9]+)#', $replacement)) {
            $id = substr($replacement, strlen('file/download/'));
            $file_details = self::getDetails($id);
            return $file_details['filename'];
        } else {
            throw new InvalidArgumentException("Redirect target doesn't match expected value");
        }
    }


    /**
     * Replaces a set of files to be stored in a single field; this acts as a backend for {@see Fb::chunkedUpload}
     *
     * Files are saved as '{prefix}{num}.{ext}', with 'num' starting at 1
     *
     * @param string $session_key Session key used for this file field, used for {@see FileUpload::verify}
     * @param string $field_name Name of the field used on the form, e.g. 'photos'
     * @param array $exts Allowed file extensions, e.g. ['jpg', 'jpeg', 'png', 'gif']
     * @param string $prefix The start of the new file name, e.g. 'user-gallery-123-'
     * @return array List of newly saved filenames
     */
    public static function replaceSet($session_key, $field_name, $exts, $prefix)
    {
        $files_saved = [];
        $i = 0;
        while (isset($_POST[$field_name][$i])) {
            try {
                $src_file = FileUpload::verify($session_key, $field_name, $i, $exts);
                if (!empty($src_file)) {
                    $ext = File::getExt($_POST[$field_name][$i]);
                    $dest = $prefix . ($i + 1) . '.' . $ext;
                    $res = File::moveUpload($src_file, $dest);

                    if (!empty($res)) {
                        $files_saved[] = $dest;
                    }
                }
            } catch (Exception $ex) {
            }

            ++$i;
        }

        $old_files = File::glob($prefix . '*');
        foreach ($old_files as $file) {
            if (!in_array($file, $files_saved)) {
                File::delete($file);
            }
        }

        return $files_saved;
    }

}

