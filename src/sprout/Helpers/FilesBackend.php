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

use Kohana;

/**
* Abstract class for a backend storage for the database-managed files
**/
abstract class FilesBackend {


    /**
     * Declare the backend type we're using
     * @var mixed
     */
    protected $backend_type = null;


    /**
     * Get the 'type' key for the current backend
     *
     * @return string
     */
    public function getType()
    {
        return $this->backend_type;
    }


    /**
     * Get the config settings for the current backend
     *
     * @return array
     */
    public function getConfig()
    {
        static $config;

        if (!$config) {
            $type = $this->backend_type;
            $config = Kohana::config("file.file_backends.{$type}");
        }

        return $config;
    }


    /**
     * Get the AWS config merge settings.
     *
     * @return array
     */
    public function getSettings()
    {
        $config = $this->getConfig();
        return $config['settings'] ?? [];
    }


    /**
     * Get the name for the current backend
     *
     * @return string
     */
    public function getName()
    {
        $config = $this->getConfig();
        return $config['name'] ??  'Unknown';
    }


    /**
     * @inheritdoc
     */
    public function getTransformFolderPrefix(): string
    {
        $settings = $this->getSettings();
        return $settings['transform_folder_prefix'] ?? '';
    }


    /**
     * Get a repeatable and predictable rdb key for a file function response
     *
     * @param string $function
     * @param string $filename
     * @return string
     */
    public function getCacheKey(string $function, string $filename): string
    {
        return "file:{$this->backend_type}:{$filename}:{$function}";
    }


    /**
     * Get a cached file function response
     *
     * @param string $function
     * @param string $filename
     * @return mixed Cache val or null if empty
     */
    public function getCacheResponse(string $function, string $filename): mixed
    {
        if (!Kohana::config('cache.enabled')) return null;

        $key = $this->getCacheKey($function, $filename);
        $cache = Cache::instance();

        return $cache->get($key);
    }


    /**
     * Set a cached file function response
     *
     * @param string $function
     * @param string $filename
     * @param mixed $response
     * @param int $ttl Number of seconds to hold cache for. Defaults to one day
     * @return void
     */
    public function setCacheResponse(string $function, string $filename, $response, int $ttl = null): void
    {
        if (!Kohana::config('cache.enabled')) return;

        $key = $this->getCacheKey($function, $filename);

        if ($ttl === null) {
            $settings = $this->getSettings();
            $ttl = $settings['default_cache_ttl'] ?? 86400;
        }

        $key = $this->getCacheKey($function, $filename);
        $cache = Cache::instance();

        $cache->set($key, $response, ['sprout-files'], $ttl);
    }


    /**
     * Clear a cached file function response
     *
     * @param string $function
     * @param string $filename
     * @return void
     */
    public function clearCacheResponse(string $function, string $filename): void
    {
        if (!Kohana::config('cache.enabled')) return;

        $key = $this->getCacheKey($function, $filename);
        $cache = Cache::instance();

        $cache->delete($key);
    }


    /**
     * Clear all caches for a given file
     *
     * @param string $filename
     * @return void
     */
    public function clearCaches(string $filename): void
    {
        $functions = get_class_methods(static::class);

        foreach ($functions as $function) {
            $this->clearCacheResponse($function, $filename);
        }
    }


    /**
     * Generate server files base directory path
     *
     * @return string
     */
    abstract function baseDir();

    /**
     * Returns the relative URL for a given file.
     *
     * Use for content areas.
     *
     * @param string|int $id ID of entry in files table, or (deprecated) string: filename
     *
     * @return string e.g. file/download/123
     */
    abstract function relUrl($id);


    /**
     * Returns the absolute URL for a given file id, including domain.
     *
     * @param string|int $id ID of entry in files table, or (deprecated) string: filename
     *
     * @return string e.g. http://example.com/file/download/123
     */
    abstract function absUrl($id): string;


    /**
     * Returns the relative URL for a dynamically resized image.
     *
     * Size formatting is as per {@see File::parseSizeString}, e.g. c400x300
     *
     * @param string|int $id ID or filename from record in files table
     * @param string $size A code as per {@see File::parseSizeString}
     *
     * @return string HTML-safe relative URL, e.g. file/resize/c400x300/123_example.jpg
     */
    abstract function resizeUrl($id, string $size): string;


    /**
     * Determine if a file exists
     *
     * @param string $filename The file to check for
     *
     * @return bool Returns TRUE if the file exists, and FALSE if it does not
     */
    abstract function exists(string $filename): bool;


    /**
     * Get the size, in bytes, of the specified file
     *
     * @param string $filename
     *
     * @return int|false
     */
    abstract function size(string $filename): int|false;


    /**
     * Get the modified time, in unix timestamp format, of the specified file
     *
     * @param string $filename
     *
     * @return int|false The modified file timestamp
     */
    abstract function mtime(string $filename);


    /**
     * Sets access and modification time of file
     *
     * @param string $filename
     *
     * @return bool
     */
    abstract function touch(string $filename): bool;


    /**
     * Returns the size of an image, or false on failure.
     *
     * Output format is the same as getimagesize, but will be at a minimum:
     *   [0] => width, [1] => height, [2] => type
     *
     * @param string $filename
     *
     * @return array|false|null depending if found
     */
    abstract function imageSize(string $filename);


    /**
     * Delete a file
     *
     * @param string $filename
     *
     * @return bool
     */
    abstract function delete(string $filename): bool;


    /**
    * Delete a directory
    **/
    abstract function deleteDir($directory);


    /**
    * Create an empty directory
    **/
    abstract function mkDir($directory);


    /**
     * Returns all files which match the specified mask.
     *
     * I have a feeling this returns other sizes (e.g. .small) as well - which may not be ideal.
     *
     * @param string $mask The search mask / string
     *
     * @return array An array of results from the lookup
     */
    abstract function glob(string $mask, $depth = 0): array;


    /**
     * This is the equivalent of the php readfile function
     *
     * @param string $filename
     *
     * @return int|false
     */
    abstract function readfile(string $filename);


    /**
    * Returns file content as a string. Basically the same as file_get_contents
     *
     * @param string $filename
     *
     * @return string|false
    **/
    abstract function getString(string $filename);


    /**
    * Saves file content as a string. Basically the same as file_put_contents
     *
     * @param string $filename
     * @param string $content
     *
     * @return bool
    **/
    abstract function putString(string $filename, string $content): bool;


    /**
    * Saves file content from a stream. Basically just fopen/stream_copy_to_stream/fclose
     *
     * @param string $filename
     * @param mixed $stream
     *
     * @return bool
    **/
    abstract function putStream(string $filename, $stream): bool;


    /**
    * Saves file content from an existing (local) file
     *
     * @param string $filename
     * @param string $existing
     *
     * @return bool
    **/
    abstract function putExisting(string $filename, string $existing): bool;


    /**
    * Create a copy of the file in a temporary directory.
    * Don't forget to File::destroy_local_copy($temp_filename) when you're done!
    *
    * @param string $filename The file to copy into a temporary location

    * @return string|null Temp filename or NULL on error
    **/
    abstract function createLocalCopy(string $filename);


    /**
    * Remove a local copy of a file
    *
    * @param string $temp_filename The filename returned by createLocalCopy
    **/
    abstract function cleanupLocalCopy(string $temp_filename): bool;

    /**
     * Moves an uploaded file into the repository.
     *
     * @param string $src The source file to move
     * @param string $filename The filename to move it to
     *
     * @return bool Returns TRUE on success, FALSE on failure.
     */
    abstract function moveUpload(string $src, string $filename): bool;

}
