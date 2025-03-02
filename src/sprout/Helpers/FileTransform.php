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

use karmabunny\pdb\Exceptions\RowMissingException;
use Sprout\Helpers\Image;

use Exception;
use InvalidArgumentException;
use Kohana;
use Kohana_Exception;
use Sprout\Exceptions\FileTransformException;
use Sprout\Models\FileTransformModel;

/**
 * Methods for file resizing
 */
class FileTransform
{

    /**
     * Create a predictable filename for a given file/transform combo.
     *
     * This will use the folder prefix for the currently active backend
     *
     * @param string $filename The name of the file (no path) we're looking for
     * @param string $transform_name The transform string e.g. 'c400x200'
     * @param string|null $force_ext If set, extension is set to this
     * @param string|null $force_backend A backend to force config from, e.g. 's3' or 'local'
     *
     * @return string Name of resized file, e.g. 123_example.c400x200.jpg
     */
    public static function getTransformFilename(string $filename, string $transform_name, $force_ext = null, $force_backend = null)
    {
        $parts = explode('.', $filename);
        $ext = array_pop($parts);
        $file_noext = implode('.', $parts);

        if ($force_ext) {
            $ext = $force_ext;
        }

        if ($force_backend) {
            $backend = File::getBackendByType($force_backend);
        } else {
            $backend = File::backend();
        }

        // Get the folder prefix for the backend, if any
        $prefix = $backend->getTransformFolderPrefix();

        return "{$prefix}{$file_noext}.{$transform_name}.{$ext}";
    }


    /**
     * Add a transform record to the data store
     *
     * @param int $file_id The original file ID (or 0 if not known) that has been transformed
     * @param string $filename The original filename that has been transformed
     * @param string $transform_name The transform string e.g. 'c400x200'
     * @param string $transform_filename The filename of the transformed file
     * @param array|string $imgsize Array output from imgsize (opt. json encoded) or empty string
     * @param int $filesize
     *
     * @return FileTransformModel
     */
    public static function addTransformRecord(int $file_id, string $filename, string $transform_name, string $transform_filename, $imgsize, int $filesize, string $backend_type = null)
    {
        if (is_array($imgsize)) $imgsize = json_encode($imgsize);

        // Try and find an existing model
        $transform = FileTransformModel::findOrCreate(['filename' => $filename, 'transform_name' => $transform_name]);

        $transform->file_id = $file_id;
        $transform->filename = $filename;
        $transform->transform_name = $transform_name;
        $transform->transform_filename = $transform_filename;
        $transform->backend_type = $backend_type ?? File::getBackendType();

        $transform->imagesize = $imgsize;
        $transform->filesize = $filesize;

        $now = Pdb::now();
        $transform->date_added = $now;
        $transform->date_modified = $now;
        $transform->date_file_modified = $now;

        $transform->save();

        return $transform;
    }


    /**
     * Returns a list of files transforms stored in the database
     *
     * If passed an id, this will try and load stored data from the record
     * It may be passed a filename however for legacy/direct processing support
     *
     * @param string|int $filename_or_id The name of the file in the repository
     *
     * @return array An array of file transform records
     */
    public static function getTransforms($filename_or_id)
    {
        if (File::filenameIsId($filename_or_id)) {
            return FileTransformModel::findAll(['file_id' => $filename_or_id]);
        } else {
            return FileTransformModel::findAll(['filename' => (string) $filename_or_id]);
        }
    }



    /**
     * Get a file transform by its record ID
     *
     * @param int $transform_id
     *
     * @return FileTransformModel|null
     */
    public static function getById(int $transform_id)
    {
        try {
            return FileTransformModel::findOne(['id' => $transform_id]);
        } catch (RowMissingException $e) {
        }

        return null;
    }


    /**
     * Get a file transform by its transform filename
     *
     * @param string $transform_filename
     *
     * @return FileTransformModel|null
     */
    public static function getByTransformFilename(string $transform_filename)
    {
        try {
            return FileTransformModel::findOne(['transform_filename' => $transform_filename]);
        } catch (RowMissingException $e) {
        }

        return null;
    }


    /**
     * Look for a transform table entry for a file by the filename
     *
     * If not found, this will attempt to find matching file details in the files table
     * If we have these, we'll try and ID lookup
     *
     * @param string $filename The name of the file (no path) we're looking for
     * @param string $transform_name The transform string e.g. 'c400x200'
     *
     * @return FileTransformModel|null
     */
    public static function findByFilename(string $filename, string $transform_name): ?FileTransformModel
    {
        try {
            return FileTransformModel::findOne(['filename' => $filename, 'transform_name' => $transform_name]);
        } catch (RowMissingException $e) {
        }

        return null;
    }


    /**
     * Look for a transform table entry for a file by its ID
     *
     * @param int $file_id The ID of the file we're looking for
     * @param string $transform_name The filesize string e.g. 'c400x200'
     *
     * @return FileTransformModel|null
     */
    public static function findByFileId(int $file_id, string $transform_name): ?FileTransformModel
    {
        try {
            return FileTransformModel::findOne(['file_id' => $file_id, 'transform_name' => $transform_name]);
        } catch (RowMissingException $e) {
        }

        return null;
    }


    /**
     * Create a specific default image size, as per the config parameter 'file.image_transformations'
     *
     * @param string|int $filename_or_id
     * @param string $specific_size
     * @return bool
     * @throws InvalidArgumentException when given a specific size that does not exist
     * @throws FileTransformException
     */
    public static function createDefaultTransform($filename_or_id, string $specific_size)
    {
        $sizes = Kohana::config('file.image_transformations');
        $status = FileTransform::createTransformSizes($filename_or_id, $sizes, $specific_size);
        return $status[$specific_size] ?? false;
    }


    /**
     * Create default image sizes as per the config parameter 'file.image_transformations'
     *
     * @param int $file_id The file to create sizes for
     * @return bool[] [ size => bool ]
     */
    public static function createDefaultTransforms(int $file_id)
    {
        $sizes = Kohana::config('file.image_transformations');
        return FileTransform::createTransformSizes($file_id, $sizes);
    }


    /**
     * Create default image sizes as per the config parameter 'file.image_transformations_instant'
     *
     * @param int $file_id The file to create sizes for
     * @return bool[] [ size => bool ]
     */
    public static function createInstantTransforms(int $file_id)
    {
        $sizes = Kohana::config('file.image_transformations_instant');
        return FileTransform::createTransformSizes($file_id, $sizes);
    }


    /**
     * Create transformed image for a single resize.
     *
     * @param string|int $filename_or_id
     * @param string $size_name
     * @param ResizeImageTransform $size
     * @param string|null $file_backend_type
     * @throws FileTransformException
     * @return bool
     */
    public static function createTransformSize($filename_or_id, string $size_name, ResizeImageTransform $size, $file_backend_type = null)
    {
        $sizes = [$size_name => $size];
        $status = FileTransform::createTransformSizes($filename_or_id, $sizes, $size_name, $file_backend_type);
        return $status[$size_name] ?? false;
    }


    /**
     * Create default image sizes as per the specified array of sizes'
     *
     * @see config file.image_transformations*
     *
     * The transformed files get saved onto the server.
     * If any of the transformations in a transform-group fails,
     * the whole group will fail and the file will not be saved.
     *
     * @param string|int $filename_or_id The file to create sizes for
     * @param ResizeImageTransform[][] $sizes [ name => [transforms] ]
     * @param string|null $specific_size Optional parameter to process only a single size
     * @param string|null $file_backend_type FileBackend $file_backend Optional parameter to specify a different file backend
     * @throws InvalidArgumentException when given a specific size that does not exist
     * @throws FileTransformException
     * @return bool[] [ size => bool ]
     */
    public static function createTransformSizes($filename_or_id, array $sizes, $specific_size = null, $file_backend_type = null)
    {
        $details = File::getDetails($filename_or_id);

        // Determine our file ID and filename from the params given
        if (File::filenameIsId($filename_or_id)) {
            if (empty($details)) {
                throw new FileTransformException('Unable to create default image sizes: file not found');
            }
            $file_id = (int) $filename_or_id;
            $filename = $details['filename'];

        } else {
            $file_id = $details['id'] ?? 0;
            $filename = $filename_or_id;
        }

        if ($file_backend_type === null) {
            $file_backend = File::backend();
            $file_backend_type = $file_backend->getType();
        } else {
            $file_backend = File::getBackendByType($file_backend_type);
        }

        if ($specific_size) {
            if (!isset($sizes[$specific_size])) {
                throw new InvalidArgumentException('Invalid param $specific_size; size doesn\'t exist.');
            }

            $sizes = array($specific_size => $sizes[$specific_size]);
        }

        if ($details and $details['author'] and $details['embed_author']) {
            $embed_text = $details['author'];
        } else {
            $embed_text = false;
        }

        $status = array_fill_keys(array_keys($sizes), false);

        // Get a local copy to avoid keep pulling remote images
        $base_file = $file_backend->createLocalCopy($filename);
        if (empty($base_file)) {
            throw new FileTransformException('Unable to create local copy of ' . $filename);
        }

        foreach ($sizes as $size_name => $transform) {
            // Replicate the local temp file. Include size name to avoid clashes
            $temp_filename = STORAGE_PATH
                . 'temp/'
                . time()
                . '_transform_' . $size_name
                . '_' . str_replace('/', '~', $filename);

            $res = @copy($base_file, $temp_filename);

            if (! $res) {
                throw new FileTransformException('Unable to create temporary copy of ' . $base_file . ' for processing');
            }

            $img = new Image($temp_filename);

            $transform_filename = FileTransform::getTransformFilename($filename, $size_name);

            // Do the transforms
            foreach ($transform as $t) {
                $res = $t->transform($img);

                if ($t instanceof ResizeImageTransform) {
                    $dims = $t->getDimensions();
                }

                // If an individual transform fails,
                // cancel the transforming for this group
                // The other transform groups will still be processed though
                if ($res == false) {
                    Kohana::logException(new FileTransformException('Transform failed: ' . get_class($t)));
                    continue 2;
                }
            }

            if ($embed_text) $img->addText($embed_text);

            $result = $img->save();
            if (! $result) {
                $file_backend->cleanupLocalCopy($temp_filename);
                throw new FileTransformException('Save of new image failed');
            }

            // Import temp file into media repo
            $result = File::putExisting($transform_filename, $temp_filename);
            if (! $result) {
                $file_backend->cleanupLocalCopy($temp_filename);
                throw new FileTransformException('Image copy of new file into repository failed');
            }

            // Create a file transforms record
            $imgsize = getimagesize($temp_filename);
            $filesize = filesize($temp_filename);
            FileTransform::addTransformRecord($file_id, $filename, $size_name, $transform_filename, $imgsize, $filesize);

            $file_backend->cleanupLocalCopy($temp_filename);
            $status[$size_name] = true;
        }

        $file_backend->cleanupLocalCopy($base_file);

        return $status;
    }


    /**
     * Delete transformed versions of a file
     *
     * If passed an id, this will try and load stored data from the record
     * It may be passed a filename however for legacy/direct processing support
     *
     * @param string|int $filename_or_id The name of the file in the repository
     */
    public static function deleteTransforms($filename_or_id)
    {
        // We don't care if this is string or int as delete will take care of both
        // TODO: This is too messy
        $transforms = FileTransform::getTransforms($filename_or_id);

        /** @var FileTransformModel $transform */

        foreach ($transforms as $transform) {
            $res = $transform->deleteFile();
            if (!$res) return false;

            $res = $transform->delete(false);
            if (!$res) return false;

        }

        return true;
    }


    /**
     * Delete a file transform by its record ID
     *
     * @param int $transform_id
     *
     * @return bool
     */
    public static function deleteById(int $transform_id)
    {
        $transform = FileTransform::getById($transform_id);
        return $transform->delete(true);
    }


    /**
     * Delete a given transform file and db record by its ID
     *
     * @param int $file_id The base file ID we want to delete
     * @param string $transform_name The filesize string e.g. 'c400x200'
     *
     * @return bool
     */
    public static function deleteByFileId(int $file_id, string $transform_name)
    {
        $transform = FileTransform::findByFileId($file_id, $transform_name);
        return $transform->delete(true);
    }


    /**
     * Delete a given transform file and db record by its filename
     *
     * If we don't have a record, we'll try a direct delete of the transform filename
     *
     * @param string $filename The base filename we want to delete
     * @param string $transform_name The filesize string e.g. 'c400x200'
     *
     * @return bool
     */
    public static function deleteByFilename(string $filename, string $transform_name)
    {
        $transform = FileTransform::findByFilename($filename, $transform_name);
        $transform_filename = FileTransform::getTransformFilename($filename, $transform_name);

        // If it's missing, build the filename as normal, then spray and pray
        if (empty($transform)) {
            // This will use the current active file backend in all cases
            return File::backend()->delete($transform_filename);
        }

        // Try and delete by stored filename
        $res = $transform->delete(true);

        if ($res) return true;

        // If the delete misses, try a delete against the expected filename
        $backend = File::getBackendByType($transform->backend_type);
        return $backend->delete($transform_filename);
    }

    /**
     * Resize an image to a given size spec
     *
     * This avoids using any File helpers that might use remote backend functions
     * This is to allow the tooling to be used as needed on any temp/local images
     *
     * @param string $filepath Full (local) file path to the image to be resized
     * @param string $size Size string as per the File::parseSizeString() method.
     * @param string|null $embed_text Text to embed in the image, if any.
     * @param array|null $focal_points Focal points for cropping, if any.
     *
     * @return bool Flag for success/fail
     *
     * @throws FileTransformException
     * @throws InvalidArgumentException on parsing the size params
     * @throws Kohana_Exception from the image transformer
     */
    public static function resizeImage(string $filepath, string $size, ?string $embed_text = null, ?array $focal_points = [])
    {

        // Clean up after ourselves.
        set_exception_handler(function($error) use ($filepath) {
            File::cleanupLocalCopy($filepath);
            Kohana::exceptionHandler($error);
        });

        // Resizing, etc
        $img = new Image($filepath);

        $parsed_size = File::parseSizeString($size);
        if (count($parsed_size) < 5) {
            throw new InvalidArgumentException('Invalid image resize parameters');
        }

        list($type, $width, $height, $crop_x, $crop_y, $quality) = $parsed_size;

        $size_limits = Kohana::config('image.max_size');

        if ($width > $size_limits['width'] or $height > $size_limits['height']) {
            throw new FileTransformException('Image dimensions exceed the maximum limit.');
        }

        if ($type == 'm') {
            // Max size
            $file_size = getimagesize($filepath);

            if ($width == 0) $width = PHP_INT_MAX;
            if ($height == 0) $height = PHP_INT_MAX;

            if ($file_size[0] > $width or $file_size[1] > $height) {
                $img->resize($width, $height);
                if ($embed_text) $img->addText($embed_text);

            } else {
                // Nothing to do, original matches
                return true;
            }

        } else if ($type == 'r') {
            // Resize
            $img->resize($width, $height);
            // $resize_dims = $img->calcResizeDims($width, $height, Image::AUTO);
            if ($embed_text) $img->addText($embed_text);

        } else if ($type == 'c') {
            // Crop
            if ($width / $img->width > $height / $img->height) {
                $master = Image::WIDTH;
            } else {
                $master = Image::HEIGHT;
            }

            // Determine orientation (portrait/square/landscape/panorama)
            $ratio = $width / $height;
            $orientation = 'panorama';
            foreach (FileConstants::$image_ratios as $orient_name => $orient_ratio) {
                if ($ratio <= $orient_ratio) {
                    $orientation = $orient_name;
                    break;
                }
            }

            if (isset($focal_points[$orientation])) {
                $point = $focal_points[$orientation];
            } else {
                $point = @$focal_points['default'];
            }

            @list($x, $y) = $point;
            if ($x > 0 and $y > 0) {
                // $full_dims = File::imageSize($filename);

                if ($master == Image::WIDTH) {
                    $scale = $width / $img->width;
                } else {
                    $scale = $height / $img->height;
                }

                $scaled_x = round($x * $scale);
                $scaled_y = round($y * $scale);

                // Put focal point as close to center of crop position as possible
                if ($master == Image::WIDTH) {
                    $crop_y = $scaled_y - round($height / 2);
                    if ($crop_y < 0) $crop_y = 0;

                    if ($crop_y + $height > $img->height * $scale) {
                        $crop_y = floor($img->height * $scale) - $height;
                    }
                } else {
                    $crop_x = $scaled_x - round($width / 2);
                    if ($crop_x < 0) $crop_x = 0;

                    if ($crop_x + $width > $img->width * $scale) {
                        $crop_x = floor($img->width * $scale) - $width;
                    }
                }
            }

            $img->resize($width, $height, $master);
            $img->crop($width, $height, $crop_y, $crop_x);
            if ($embed_text) $img->addText($embed_text);

        } else {
            // What?
            throw new InvalidArgumentException("Incorrect resize type: {$type} ({$size}}");
        }

        if ($quality) {
            $img->quality($quality);
        }

        $img->save($filepath, 0644, true);

        return true;
    }

}

