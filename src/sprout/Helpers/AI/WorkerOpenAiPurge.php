<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

namespace Sprout\Helpers\AI;

use InvalidArgumentException;
use Sprout\Helpers\Worker;
use Sprout\Helpers\WorkerBase;

class WorkerOpenAiPurge extends WorkerBase
{
    protected $job_name = 'Bulk Delete OpenAI items';


    /**
     * Delete all the items for a given type
     *
     * @param string $type As per OpenAiApi::ITEM_TYPES
     * @param null|array $config Optional openai config override
    **/
    public function run(string $type, ?array $config = [])
    {
        // Line break in output
        Worker::message('');
        if (!array_key_exists($type, OpenAiApi::ITEM_TYPES)) {
            throw new InvalidArgumentException("Invalid type: $type");
        }

        Worker::message("Deleting all {$type} from OpenAI");

        $deleted_total = 0;
        $items = OpenAiApi::listItems($type, $config);

        while (count($items['data']) > 0) {
            $item_ids = array_column($items['data'], 'id');

            $deleted = OpenAiApi::deleteItems($type, $item_ids, $config);
            $deleted_total += $deleted;

            Worker::message("Deleted {$deleted} items ({$deleted_total} total)");

            $items = OpenAiApi::listItems($type, $config);
        }

        Worker::success();
    }

}

