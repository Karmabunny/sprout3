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
use Sprout\Helpers\Text;


class DocImportPlaintext extends DocImport {

    /**
    * The main load function for a document.
    * Throw an exception on error.
    *
    * @param string $filename The file. The file will exist, but may not be valid
    * @return string|DOMDocument $data Resultant XML data as a string or DOMDocument element
    **/
    public function load($filename) {
        $text = trim(file_get_contents($filename));

        $text = Enc::cleanfunky($text);
        $text = Text::richtext($text);

        // The below is an XML file, so convert BRs to valid XML
        $text = str_replace('<br>', '<br/>', $text);

        $out = '<?xml version="1.0" encoding="UTF-8" ?>' . PHP_EOL;
        $out .= '<doc><body>';
        $out .= $text;
        $out .= '</body></doc>';

        return $out;
    }

}
