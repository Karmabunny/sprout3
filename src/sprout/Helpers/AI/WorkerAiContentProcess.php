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

use Exception;
use Kohana;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Worker;
use Sprout\Helpers\WorkerBase;

class WorkerAiContentProcess extends WorkerBase
{
    protected $job_name = 'Process AI Content Queue';


    /**
    * Process AI Content queue. 1 at a time to prevent overlap
    **/
    public function run(bool $retry_failed = false)
    {
        // Line break in output
        Worker::message('');

        $status = [
            'queued',
        ];
        if ($retry_failed) {
            $status[] = 'failed';
        }

        $conditions = $params = [];
        $conditions[] = ['status', 'IN', $status];

        $where = Pdb::buildClause($conditions, $params);
        $q_items = "SELECT * FROM ~ai_content_queue WHERE {$where} ORDER BY id ASC LIMIT 1";

        $ids = [];
        $total_costs = [];
        $items = Pdb::query($q_items, $params, 'arr');

        // Result tracking vars
        $success = $failed = 0;

        while(count($items) > 0) {
            $item = $items[0];
            Worker::message("Generating AI content for record #{$item['target_id']} in {$item['target_table']}");

            $active_msg = null;
            $active_fields = [];

            $class = new $item['class']();
            $method = $item['method'];

            $transacting = Pdb::inTransaction();
            if (!$transacting) Pdb::transact();

            // Update the queue entry to processing, so we can check for other items cleanly
            Pdb::update('ai_content_queue', ['status' => 'processing'], ['id' => $item['id']]);

            // See if we have any more fields to update for this table/record combo
            $q_count = "SELECT COUNT(*) FROM ~ai_content_queue WHERE target_table = ? AND target_id = ? AND status = ?";

            /** @var int */
            $count = Pdb::query($q_count, [$item['target_table'], $item['target_id'], 'queued'], 'val');

            // If we are finished with AI processing for this record, we can make changes to the active flag
            if ($count == 0) {
                switch ($item['activation_status']) {
                    case 'active':
                        $active_fields['active'] = 1;
                        $active_msg = "Setting record #{$item['target_id']} in {$item['target_table']} to ACTIVE";
                        break;
                    case 'inactive':
                        $active_fields['active'] = 0;
                        $active_msg = "Setting record #{$item['target_id']} in {$item['target_table']} to INACTIVE";
                        break;
                    default:
                        // Make no
                        break;
                }
            }

            try {
                // Fire the actual AI class method to get the content
                $output = $class::$method($item['prompt']);

                // Merge the active flag change with the AI content
                $data = array_merge([$item['target_col'] => $output], $active_fields);
                Pdb::update($item['target_table'], $data, ['id' => $item['target_id']]);

                Pdb::update('ai_content_queue', ['status' => 'complete'], ['id' => $item['id']]);

                Worker::message("Updated content for record #{$item['target_id']} in {$item['target_table']}");
                if ($active_msg) Worker::message($active_msg);

                $success++;

                $cost = $class->getLastRequestCost();
                $unit = $class->getRequestCostUnit();
                Worker::message("Request cost: {$cost} {$unit}");

                if (!isset($total_costs[$unit])) $total_costs[$unit] = 0;
                $total_costs[$unit] += $cost;

            } catch (Exception $e) {
                // We will want this in the logs in case our integration has broken
                Kohana::logException($e);

                $log = $e->getMessage();
                Pdb::update('ai_content_queue', ['status' => 'failed', 'log' => $log], ['id' => $item['id']]);

                Worker::message("AI FAILED for record #{$item['target_id']} in {$item['target_table']}: {$log}");
                $failed++;
            }

            if (!$transacting) Pdb::commit();

            // Line break in output
            Worker::message('');

            // Avoid continuously cycling an error
            $ids[] = $item['id'];

            $conditions = $params = [];
            $conditions[] = ['status', 'IN', $status];
            $conditions[] = ['id', 'NOT IN', $ids];

            $where = Pdb::buildClause($conditions, $params);
            $q_items = "SELECT * FROM ~ai_content_queue WHERE {$where} ORDER BY id ASC LIMIT 1";
            $items = Pdb::query($q_items, $params, 'arr');
        }

        Worker::message('AI Content Processed: ' . $success . ' success, ' . $failed . ' failed');

        foreach ($total_costs as $unit => $cost) {
            Worker::message("Total {$unit} cost: {$cost}");
        }

        Worker::success();
    }

}

