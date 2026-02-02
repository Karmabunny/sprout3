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

namespace Sprout\Helpers\DocImport;

use Sprout\Helpers\Enc;
use Sprout\Helpers\ImportCSV;


class DocImportCSV extends DocImport {

    /**
    * The main load function for a document.
    * Throw an exception on error.
    *
    * @param string $filename The file. The file will exist, but may not be valid
    * @return string|DOMDocument Resultant XML data as a string or DOMDocument element
    **/
    public function load($filename) {
        $csv = new ImportCSV($filename);

        $out = '<?xml version="1.0" encoding="UTF-8" ?>' . PHP_EOL;
        $out .= '<doc><body>';
        $out .= '<table><thead><tr>';

        $headings = $csv->getHeadings();
        foreach ($headings as $hdr) {
            $out .= '<th>' . Enc::xml($hdr) . '</th>';
        }

        $out .= '</tr></thead><tbody>';

        while ($data = $csv->getLine()) {
            $out .= '<tr>';
            foreach ($data as $col) {
                $out .= '<td>' . Enc::xml($col) . '</td>';
            }
            $out .= '</tr>';
        }

        $out .= '</tbody></table>';
        $out .= '</body></doc>';

        return $out;
    }

}
