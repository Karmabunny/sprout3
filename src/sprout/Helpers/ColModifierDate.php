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
use DateTimeZone;
use InvalidArgumentException;

/**
* Converts a MySQL date into something friendlier. Works for DATE, TIME, DATETIME AND [BIG]INT.
*
* The default output format is d/m/Y, but can be changed in the constructor.
* Format strings are anything supported by the PHP function date().
**/
class ColModifierDate extends SortedColModifier
{
    private $_format;
    private $_timezone;
    private $_time_col;

    /**
     * @param string $format The format (see PHP's date function, {@link http://php.net/manual/en/function.date.php})
     * @param string $timezone The text identifier for the timezone to modify to, {@link https://www.php.net/manual/en/timezones.php})
     */
    public function __construct(string $format = 'd/m/Y', ?string $timezone = null, ?string $time_col = null)
    {
        $this->_format = $format;
        $this->_timezone = $timezone;
        $this->_time_col = $time_col;
    }

    /**
    * Modify a column value
    * This value will be html/csv/etc encoded afterwards.
    *
    * @param string $val The incoming value
    * @param string $field_name The name of the field being modified
    * @return string The modified value
    **/
    public function modify($val, $field_name, $row)
    {
        if ($val == '') return '';

        // Unix timestamp stored in an INT or BIGINT column
        if (preg_match('/^[0-9]+$/', $val)) {
            $date = new DateTime('@'.$val);
        } else {
            $date = new DateTime($val);
        }

        if ($this->_time_col) {
            // This will break if the index is incorrect, let it.
            $this->_timezone = $row[$this->_time_col];
        }

        if ($this->_timezone !== null) {
            $this->modifyTimezone($date);
        }

        return $date->format($this->_format);
    }


    /**
     * Modify the timezone of a date object
     *
     * @param DateTime $date The date object to modify directly
     * @return void
     */
    private function modifyTimezone(DateTime &$date)
    {
        // Make sure this is valid before we use it
        if (!in_array($this->_timezone, DateTimeZone::listIdentifiers())) {
            throw new InvalidArgumentException('Timezone value "' . $this->_timezone . '" for date modification is invalid');
        }

        $tz = new DateTimeZone($this->_timezone);
        $date->setTimezone($tz);
    }

}
