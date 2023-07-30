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
    public function run(int $file_id)
    {
        ini_set('memory_limit', '1024M');

        Worker::message("Creating default transforms for file #{$file_id}");

        File::createDefaultSizes($file_id, null, null);

        Worker::message('');
        Worker::success();
    }

}
