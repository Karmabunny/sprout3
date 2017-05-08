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


class WorkerRedoSizes extends WorkerBase
{
    protected $job_name = 'Redo image sizes';


    /**
    * Do stuff
    **/
    public function run($size = null)
    {
        if ($size) {
            Worker::message("Only processing size '{$size}'.");
        } else {
            Worker::message("Processing all sizes.");
        }

        $q = "SELECT filename FROM ~files WHERE type = ?";
        $res = Pdb::query($q, [FileConstants::TYPE_IMAGE], 'pdo');

        foreach ($res as $row) {
            if (!File::exists($row['filename'])) continue;
            File::createDefaultSizes($row['filename'], $size);
            Worker::message($row['filename']);
        }

        $res->closeCursor();

        Worker::success();
    }

}

