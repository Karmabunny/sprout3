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


/**
* Does indexing for various file formats
**/
class FileIndexing
{

    /**
    * Returns the extension of the specified file.
    * By the way, this value is always lowercased, because the other helpers expect that.
    *
    * @param string $filename The filename to get the ext of.
    **/
    static public function getExt($filename)
    {
        $parts = explode ('.', $filename);
        $ext = array_pop ($parts);
        $ext = strtolower($ext);
        return $ext;
    }

    /**
    * Returns true if the specified extension is supported,
    * false if it is not
    *
    * @param string $ext The extension to check
    * @return boolean True if supported, false otherwise
    **/
    static public function isExtSupported($ext)
    {
        if (! function_exists('exec')) return false;

        switch ($ext) {
            case 'txt':
            case 'csv':
                return true;
        }

        if (! function_exists('exec')) return false;
        if (! function_exists('escapeshellarg')) return false;
        if (! function_exists('shell_exec')) return false;

        switch ($ext) {
            case 'pdf':
                exec ('pdftotext -v', $output, $return);
                if ($return != 127) {
                    return true;
                }
                break;

            case 'doc':
                exec ('antiword', $output, $return);
                if ($return != 127) {
                    return true;
                }
                break;

            case 'docx':
                exec ('perl -v', $output, $return);
                if ($return != 127) {
                  return true;
                }
                break;

            case 'odt':
                exec ('odt2txt --version', $output, $return);
                if ($return != 127) {
                  return true;
                }
                break;

            case 'xls':
                exec ('xls2csv', $output, $return);
                if ($return != 127) {
                    return true;
                }
                break;

        }

        return false;
    }


    /**
    * Returns the plaintext version of a formatted file.
    * Returns null on error.
    *
    * @param string $filename The file to process.
    * @param string $ext Allows the file type to be forced.
    * @return string|null The plain text, or null if there was an error.
    **/
    static public function getPlaintext($filename, $ext = null)
    {
        $unlink = false;

        if (! $ext) $ext = self::getExt($filename);

        if ($filename[0] == '/') {
            $index_filename = $filename;
        } else {
            $index_filename = File::createLocalCopy($filename);
            $unlink = true;
         }

        switch ($ext) {
            case 'txt':
            case 'csv':
                return file_get_contents($index_filename);

            case 'pdf':
                return self::getPdf($index_filename);

            case 'doc':
                return self::getDoc($index_filename);

            case 'docx':
                return self::getDocx($index_filename);

            case 'odt':
                return self::getOdt($index_filename);

            case 'xls':
                return self::getXls($index_filename);

        }

        if ($unlink) {
            File::cleanupLocalCopy($index_filename);
        }

        return null;
    }


    /**
    * Uses 'pdftotext' to get the contents of a pdf
    *
    * @param $filename The filename to process.
    * @return string The plaintext version of the file, or null if there was an error.
    **/
    static private function getPdf($filename)
    {
        $filename = escapeshellarg  ($filename);
        return shell_exec("pdftotext {$filename} -");
    }

    /**
    * Uses 'antiword' to get the contents of a doc
    *
    * @param $filename The filename to process.
    * @return string The plaintext version of the file, or null if there was an error.
    **/
    static private function getDoc($filename)
    {
        $filename = escapeshellarg  ($filename);
        return shell_exec("antiword {$filename}");
    }

    /**
    * Uses a perl script to get the contents of a docx
    *
    * @param $filename The filename to process.
    * @return string The plaintext version of the file, or null if there was an error.
    **/
    static private function getDocx($filename)
    {
        $filename = escapeshellarg  ($filename);
        return shell_exec ("perl indexing/docx2txt.pl {$filename} -");
    }

    /**
    * Uses a 'odt2txt' to get the contents of a odt
    *
    * @param $filename The filename to process.
    * @return string The plaintext version of the file, or null if there was an error.
    **/
    static private function getOdt($filename)
    {
        $filename = escapeshellarg  ($filename);
        return shell_exec("odt2txt {$filename}");
    }

    /**
    * Uses a 'xls2csv' to get the contents of a xls
    *
    * @param $filename The filename to process.
    * @return string The plaintext version of the file, or null if there was an error.
    **/
    static private function getXls($filename)
    {
        $filename = escapeshellarg  ($filename);
        return shell_exec("xls2csv {$filename}");
    }
}


