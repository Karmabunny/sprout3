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

use DateTime;
use Sprout\Helpers\View;

/**
 * Renders a calendar
 */
class Calendar
{
    /**
     * Renders calendar HTML
     *
     * @param int $month 1 through to 12 (Jan - Dec)
     * @param int $year
     * @param callable $callback Render inner HTML for the cells
     * @param int $week_begins 1 through to 7 (Monday - Sunday)
     * @return string HTML
     */
    public static function render($month, $year, $callback, $week_begins = 7)
    {
        $days = ['', 'Monday', 'Tuesday' , 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $month = (int) $month;
        $year = (int) $year;
        $week_begins = (int) $week_begins;
        $day_names = [];

        $date_start = new DateTime('first day of ' . $year . '-' . $month);
        $date_end = new DateTime('last day of ' . $year . '-' . $month);

        if (empty($week_begins)) {
            $week_begins = 7;
        }

        if ($week_begins == 1) {
            $week_ends = 7;
        } else {
            $week_ends = $week_begins - 1;
        }

        while ($date_start->format('N') != $week_begins) {
            $date_start->modify('-1 day');
        }

        while ($date_end->format('N') != $week_ends) {
            $date_end->modify('+1 day');
        }

        while (count($day_names) < 7) {
            $day_names[] = $days[$week_begins];

            if ($week_begins < 7) {
                $week_begins ++;
            } else {
                $week_begins = 1;
            }
        }

        $view = new View('sprout/components/calendar');
        $view->date_start = $date_start;
        $view->date_end = $date_end;
        $view->year = $year;
        $view->month = $month;
        $view->day_names = $day_names;
        $view->week_begins = $week_begins;
        $view->week_ends = $week_ends;
        $view->callback = $callback;

        return $view->render();
    }
}
