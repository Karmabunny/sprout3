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
     * @param array $options [week_begins,day_format,show_month,month_format]
     * @return string HTML
     */
    public static function render($month, $year, $callback, $options = null)
    {
        $month = (int) $month;
        $year = (int) $year;
        $day_names = [];

        // Backwards compatibility
        if (is_int($options)) {
            $day = (int) $options;
            $options = [];
            $options['week_begins'] = $day;
            unset($day);
        }

        if (!is_array($options)) $options = [];

        if (empty($options['week_begins'])) $options['week_begins'] = 7;
        if (empty($options['day_format'])) $options['day_format'] = 'l';
        if (empty($options['show_month'])) $options['show_month'] = true;
        if (empty($options['month_format'])) $options['month_format'] = 'F Y';

        $options['date_start'] = new DateTime('first day of ' . $year . '-' . $month);
        $options['date_end'] = new DateTime('last day of ' . $year . '-' . $month);

        if ($options['week_begins'] == 1) {
            $options['week_ends'] = 7;
        } else {
            $options['week_ends'] = $options['week_begins'] - 1;
        }

        while ($options['date_start']->format('N') != $options['week_begins']) {
            $options['date_start']->modify('-1 day');
        }

        while ($options['date_end']->format('N') != $options['week_ends']) {
            $options['date_end']->modify('+1 day');
        }

        while (count($day_names) < 7) {
            $day_names[] = date($options['day_format'], strtotime($options['week_begins'] + 4 . '-01-1970'));

            if ($options['week_begins'] < 7) {
                $options['week_begins'] ++;
            } else {
                $options['week_begins'] = 1;
            }
        }

        $view = new View('sprout/components/calendar');
        $view->year = $year;
        $view->month = $month;
        $view->day_names = $day_names;
        $view->callback = $callback;
        $view->options = $options;

        return $view->render();
    }
}
