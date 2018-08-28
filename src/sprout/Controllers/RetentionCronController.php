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

namespace Sprout\Controllers;

use DateTime;
use InvalidArgumentException;

use Sprout\Helpers\Cron;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Register;

class RetentionCronController extends Controller
{
    /**
     * Purge old data from the nominated tables
     */
    public function cronRetention()
    {
        Cron::start('Retention');

        $jobs = Register::getRetentionJobs();
        Cron::message('Purging old data for ' . count($jobs) . ' table(s)');

        $now = new DateTime();
        foreach ($jobs as $job_spec) {
            Pdb::validateIdentifier($job_spec['table']);
            Pdb::validateIdentifier($job_spec['column']);

            // Ensure they haven't provided a negative interval as this will cause the
            // threshold to move forward in time.
            if ($job_spec['min_age']->invert) {
                throw new InvalidArgumentException("Minimum age specification for {$table_name} retention job must be positive");
            }

            $threshold = clone $now;
            $threshold->sub($job_spec['min_age']);
            $threshold = $threshold->format('Y-m-d H:i:s');

            Cron::message("Purging records from '{$job_spec['table']}' ({$job_spec['column']}) updated before {$threshold}");

            $conds = [
                [$job_spec['column'], '<', $threshold]
            ];

            if (count($job_spec['extra_conds'])) {
                $conds += $job_spec['extra_conds'];
            }

            $params = [];
            $where = Pdb::buildClause($conds, $params);

            $q = "DELETE FROM ~{$job_spec['table']} WHERE {$where}";
            $count = Pdb::q($q, $params, 'count');

            Cron::message("    - Deleted {$count} records");
        }

        Cron::success();
    }
}
