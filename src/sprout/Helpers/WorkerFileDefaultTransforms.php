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


class WorkerFileDefaultTransforms extends WorkerBase
{
    protected $job_name = 'File Default Transforms';

    /**
    * Do stuff
    **/
    public function run(int $file_id, bool $delete_existing = false)
    {
        ini_set('memory_limit', '1024M');

        Worker::message("Creating default transforms for file #{$file_id}");
        Worker::message(' ');

        if ($delete_existing) {
            Worker::message('Deleting existing transforms...');

            $res = FileTransform::deleteTransforms($file_id); // Any non-standard sizes will be invalid now
            if (!$res) {
                Worker::message('Failed to create image size versions - unable to delete old transforms');
                Worker::failure();
            }

            Worker::message('Done.');
            Worker::message(' ');
        }

        Worker::message('Creating default sizes...');
        FileTransform::createDefaultTransforms($file_id);

        Worker::message('');
        Worker::success();
    }

}
