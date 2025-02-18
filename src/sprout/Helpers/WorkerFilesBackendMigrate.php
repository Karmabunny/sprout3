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

use InvalidArgumentException;
use Kohana;
use Sprout\Models\FileModel;
use Sprout\Models\FileTransformModel;

class WorkerFilesBackendMigrate extends WorkerBase
{
    protected $job_name = 'Files Backend Migration';


    /** @var FilesBackend */
    private $_old_backend;

    /** @var FilesBackend */
    private $_new_backend;

    /** @var string */
    private $_old_backend_type;

    /** @var string */
    private $_new_backend_type;

    /** @var array */
    private $_options = [];


    /**
    * Do stuff
    **/
    public function run(array $options)
    {
        ini_set('memory_limit', '1024M');

        $this->_options = $options;
        $this->_old_backend_type = $options['backend_source'];
        $this->_new_backend_type = $options['backend_target'];

        $action = $this->_options['action'] ?? 'unset';

        Worker::message("Migrating files to new backend: {$this->_new_backend_type}");
        Worker::message('Migration type: ' . $action);

        switch ($action) {
            case 'prepare':
                Worker::message("Preparing files for migration");
                $this->prepareFileCopy();
                break;

            case 'update':
                Worker::message("Updating database records only");
                $this->updateDatabaseRecords();
                break;

            case 'full':
                Worker::message("Preparing files for migration");
                $this->prepareFileCopy();

                Worker::message('');
                Worker::message("Updating database records");
                $this->updateDatabaseRecords();
                break;

            default:
                Worker::failure('Invalid action: ' . $action);
        }

        Worker::message('');
        Worker::success();
    }


    /**
     *
     * @param FileModel|FileTransformModel $file_model
     *
     * Migrate a single file
     */
    private function copyFile($file_model)
    {
        Worker::message('  ');

        // Some records might old or misconfigured backends. We don't want to
        // let that stop us from moving forwards with other records.
        try {
            $file_backend = $file_model->getBackend();
        } catch (InvalidArgumentException $exception) {
            Worker::message($exception->getMessage());
            return false;
        }

        if ($file_model instanceof FileModel) {
            if (empty($file_model->filename)) {
                Worker::message("Missing filename for file #{$file_model->id}");
                return false;
            }

            $filename = $file_model->filename;
            $new_filename = $file_model->filename;

            Worker::message("Processing file #{$file_model->id} | {$filename}");
        }

        if ($file_model instanceof FileTransformModel) {
            if (empty($file_model->transform_filename)) {
                Worker::message("Missing filename for file #{$file_model->id}");
                return false;
            }

            $filename = $file_model->transform_filename;
            $new_filename = FileTransform::getTransformFilename($file_model->filename, $file_model->transform_name, null, $this->_new_backend_type);

            Worker::message("Processing transform #{$file_model->id} | {$filename}");
        }

        // Grab a copy from the old backend
        $temp_filename = $file_backend->createLocalCopy($filename);

        if (!$temp_filename) {
            Worker::message("Missing file data for file #{$file_model->id} | {$filename}");
            $failed[] = $file_model->id;
            return false;
        }

        // Use File helpers on the new backend to save it
        $res = $this->_new_backend->putExisting($new_filename, $temp_filename);

        if (!$res) {
            Worker::message("Failed to copy file for file #{$file_model->id} | {$filename}");
            $failed[] = $file_model->id;
            return false;
        }

        Worker::message("Copied file for file #{$file_model->id} | {$filename}");

        /** Don't do any updating or saving if we're not looking to add new records */
        if (empty($this->_options['create_missing']) and empty($file_model->id)) {
            return true;
        }

        $now = Pdb::now();

        if ($file_model instanceof FileModel) {
            $file_type = $file_model->type ? $file_model->type : File::getType($filename);
            $file_model->type = $file_type;
        } else {
            $file_type = File::getType($file_model->filename);
        }

        if ($file_type == FileConstants::TYPE_IMAGE) {
            $imagesize = $this->_new_backend->imageSize($file_model->transform_filename ?? $file_model->filename);
            $imagesize = $imagesize ? $imagesize : [];
        } else {
            $imagesize = [];
        }

        $filesize = $this->_new_backend->size($file_model->transform_filename ?? $file_model->filename);

        $file_model->filesize = $filesize;
        $file_model->imagesize = json_encode($imagesize);

        $file_model->filename_migrated = $new_filename;
        $file_model->backend_migrated = $this->_new_backend_type;
        $file_model->date_migrated = Pdb::now();

        $file_model->date_added = $file_model->date_added ? $file_model->date_added : $now;
        $file_model->date_modified = $file_model->date_modified ? $file_model->date_modified : $now;
        $file_model->date_file_modified = $file_model->date_file_modified ? $file_model->date_file_modified : $now; // We don't know

        $is_orphan = empty($file_model->id);

        $res = $file_model->save();

        if (!$res) {
            Worker::message("Failed to save file record for file #{$file_model->id} | {$filename}");
            return $res;
        }

        Worker::message("Saved file record for file #{$file_model->id} | {$filename}");

        // Only 'files' can live in categories
        if (!$file_model instanceof FileModel) {
            return true;
        }

        // Add any bulk categories if set
        if (!$is_orphan and !empty($this->_options['category_id_files'])) {
            $res = Category::insertInto('files', $file_model->id, $this->_options['category_id_files']);
            Worker::message("Adding to file category #{$this->_options['category_id_files']} | " . ($res ? 'OK' : 'FAIL'));
        }

        if ($is_orphan and !empty($this->_options['category_id_orphans'])) {
            Category::insertInto('files', $file_model->id, $this->_options['category_id_orphans']);
            Worker::message("Adding to orphan category #{$this->_options['category_id_files']} | " . ($res ? 'OK' : 'FAIL'));
        }

        return true;
    }


    private function prepareFileCopy()
    {
        // Perform the file 'prepare' copying
        $backend_opts = Kohana::config('file.file_backends');

        // Initialise backend classes for use as needed
        foreach ($backend_opts as $backend_type => $backend_config) {
            $class_path = $backend_config['class'];

            /** @var FilesBackend */
            $class = new $class_path();

            // Quick link for the new target
            if ($backend_type == $this->_options['backend_source']) {
                $this->_old_backend = Sprout::instance($backend_config['class']);
            }

            // Quick link for the new target
            if ($backend_type == $this->_options['backend_target']) {
                $this->_new_backend = $class;
            }
        }

        $failed = [];

        Worker::message("Copying files from old backends to new backend");

        // Get a list of all files
        $limit = 100;
        $max_id = 0;

        do {
            $file_models = FileModel::find([
                ['id', '>', $max_id],
                ['backend_type', '!=', $this->_new_backend_type],
                ['backend_migrated', 'IS', 'NULL'],
            ])
            ->limit($limit)
            ->all();

            Worker::message('Processing ' . count($file_models) . ' files from files table');

            foreach ($file_models as $file_model) {
                $max_id = $file_model->id;
                $res = $this->copyFile($file_model);

                if (!$res) $failed[] = $file_model->id;
            }

        } while (count($file_models) > 0);

        Worker::message("Copying transforms from old backends to new backend");

        // Reset max id
        $max_id = 0;

        // File transform records
        do {
            $file_models = FileTransformModel::find([
                ['id', '>', $max_id],
                ['backend_type', '!=', $this->_new_backend_type],
                ['backend_migrated', 'IS', 'NULL'],
            ])
            ->limit($limit)
            ->all();

            Worker::message('Processing ' . count($file_models) . ' files from transforms table');

            foreach ($file_models as $file_model) {
                $max_id = $file_model->id;
                $res = $this->copyFile($file_model);

                if (!$res) $failed[] = $file_model->id;
            }

        } while (count($file_models) > 0);

        if (!empty($failed)) {
            Worker::message('Failed files: ' . implode(', ', $failed));
        }

        Worker::message("Copying remaining orphan files to new backend");

        $globbed = $this->_old_backend->glob('*', 10);

        foreach ($globbed as $filename) {

            $transform_name = $this->getTransformName($filename);

            $now = Pdb::now();
            if (!empty($transform_name)) {
                $file_model = new FileTransformModel();
                $file_model->filename = str_replace(".{$transform_name}.", '.', $filename);
                $file_model->transform_name = $transform_name;
                $file_model->transform_filename = $filename;

                // Attempt to extract the file ID for this transform
                preg_match('!^([0-9]+)_.+!', $filename, $matches);
                if (!empty($matches[1])) {
                    $file_model->file_id = (int) $matches[1];
                }

            } else {
                $file_model = new FileModel();
                $file_model->filename = $filename;
            }

            $file_model->backend_type = $this->_old_backend_type;
            $file_model->date_added = $now;
            $file_model->date_modified = $now;
            $file_model->date_file_modified = $now;

            $res = $this->copyFile($file_model);

            if (!$res) $failed[] = $file_model->id ?? $file_model->transform_filename ?? $filename;

            Worker::message("File '{$filename}: " . ($res ? 'OK' : 'FAIL'));
        }
    }


    /**
     * Big sweeping change to move all migrated data into active cols
     *
     * @return void
     */
    private function updateDatabaseRecords()
    {
        $tables = [
            'files',
            'file_transforms',
        ];

        foreach ($tables as $table) {
            $q = "UPDATE ~{$table}
                SET backend_type = backend_migrated,
                    filename = filename_migrated,
                    backend_migrated = null,
                    filename_migrated = null,
                    date_migrated = null
                WHERE backend_migrated = ?
                AND date_migrated IS NOT NULL";
            $params = [$this->_new_backend_type, $this->_new_backend_type];

            $num_updated = Pdb::query($q, $params, 'count');
            Worker::message("Updated {$num_updated} rows for files with backend_migrated");
        }
    }


    /**
     * Extract a transform name from a filename string, if one exists
     *
     * @param string $filename
     *
     * @return int|string|null
     */
    private function getTransformName(string $filename)
    {
        foreach (array_keys(Kohana::config('file.image_transformations')) as $transform_name) {

            // Check for transform in the file string
            if (strpos($filename, ".{$transform_name}.") !== false) {
                return $transform_name;
            }
        }

        // See if we have a custom resize string
        preg_match('!.+\.([rcm][0-9]+x[0-9]+\.).+!', $filename, $matches);
        if (!empty($matches[1])) return $matches[1];

        return null;
    }

}
