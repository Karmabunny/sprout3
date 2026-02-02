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

use RuntimeException;
use InvalidArgumentException;
use Sprout\Exceptions\FileConversionException;

/**
 * Converter between various file types
 *
 * For all features to work, the following programs need to be installed:
 * - convert (e.g. from the graphicsmagick-imagemagick-compat Debian package)
 * - exiftool (e.g. from the libimage-exiftool-perl Debian package)
 * - libreoffice (e.g. from the libreoffice-common Debian package)
 */
class FileConvert
{

    // OK so a few things to note here:
    // - the 'background' must be white, in particular for JPGs or they will
    //   come out black (perhaps this could be dependent on the target extension)
    // - the 'flatten' flag is required for PDFs (and probably TIFFs) to
    //    mush the all the layers properly (particularly alpha ones)
    // - 'colorspace' _must_ be sRGB or it'll strip the gamma channel
    // - 'depth' defaults to 16-bit for some reason and 8 is plenty for web
    const IMAGICK_OPTIONS = [
        'flatten' => true,
        'background' => 'white',
        'colorspace' => 'sRGB',
        'depth' => 8,
        'density' => 400,
        'quality' => '100%',
        'resize' => '50%',
    ];

    /**
     * Validates that an output file extension is of a valid format
     *
     * @param string $ext The output extension
     * @throws InvalidArgumentException IF the extension is invalid
     */
    private static function validateExtension($ext)
    {
        if (!preg_match('/^[a-z]{3,4}$/i', $ext)) {
            throw new InvalidArgumentException('Output extension must be 3-4 alphabetic characters');
        }
    }

    /**
     * Convert file using LibreOffice
     *
     * @param string $in_file Input filename, with full path
     * @param string $out_ext Extension to convert file to, e.g. "pdf", "jpg", "txt".
     *        Note that spreadsheets cannot be directly converted to images, but can be converted to PDFs which can
     *        then be converted using {@see FileConvert::imagemagick}
     * @return string Destination file in temp dir
     * @throws InvalidArgumentException The $out_ext argument has an invalid format
     * @throws RuntimeException LibreOffice isn't installed/accessible to PHP
     * @throws FileConversionException LibreOffice failed to convert the file
     */
    public static function libreoffice($in_file, $out_ext)
    {
        static::validateExtension($out_ext);

        $out_arg = escapeshellarg(STORAGE_PATH . 'temp/');
        $tmp_arg = escapeshellarg($in_file);
        $cmd = "libreoffice --headless --convert-to {$out_ext} --outdir {$out_arg} {$tmp_arg} 2>&1";

        $output = [];
        $return_code = null;
        exec($cmd, $output, $return_code);

        if ($return_code !== 0) {
            if (!self::installed('libreoffice')) {
                throw new RuntimeException("Program 'libreoffice' not installed - try the 'libreoffice-common' package");
            }
            throw new FileConversionException('Libreoffice converting to ' . $out_ext . ' failed - exec() error');
        }

        $dest_file = STORAGE_PATH . 'temp/' . File::getNoext(basename($in_file)) . '.' . $out_ext;
        if (!file_exists($dest_file)) {
            throw new FileConversionException('Libreoffice converting to ' . $out_ext . ' failed - destination file "' . $dest_file . '" not found');
        }

        return $dest_file;
    }


    /**
     * Convert file using ImageMagick
     *
     * @param string $in_file Input filename, with full path
     * @param string $out_ext Extension to convert file to, e.g. "png", "jpg".
     * @param int $page_index Page number of document, 0-based (applies to PDFs and other page-based documents)
     * @param int|array $options DPI or array of options
     * @return string Destination file in temp dir
     * @throws InvalidArgumentException The $out_ext argument has an invalid format
     * @throws RuntimeException ImageMagick isn't installed/accessible to PHP
     * @throws FileConversionException ImageMagick failed to convert the file
     */
    public static function imagemagick($in_file, $out_ext, $page_index = 0, $options = self::IMAGICK_OPTIONS) {
        $page_index = (int) $page_index;

        if (!is_array($options)) {
            $density = (int) $options;
            $options = self::IMAGICK_OPTIONS;
            $options['density'] = $density;
        }

        static::validateExtension($out_ext);

        $out_file = STORAGE_PATH . 'temp/' . File::getNoext(basename($in_file)) . '_' . Sprout::randStr(4) . '.' . $out_ext;

        $in_arg = escapeshellarg($in_file . '[' . $page_index . ']');
        $out_arg = escapeshellarg($out_file);

        $opt_args = '';

        foreach ($options as $key => $value) {
            $key = escapeshellarg('-' . $key);

            if ($value === false) {
                continue;
            }

            if ($value === true) {
                $opt_args .= "{$key} ";
                continue;
            }

            $value = escapeshellarg($value);
            $opt_args .= "{$key} {$value} ";
        }

        $cmd = "convert {$opt_args} {$in_arg} {$out_arg} 2>&1";

        $output = [];
        $return_code = null;
        exec($cmd, $output, $return_code);

        if ($return_code !== 0) {
            if (!self::installed('convert')) {
                throw new RuntimeException("Program 'convert' not installed - try the 'graphicsmagick-imagemagick-compat' package");
            }
            throw new FileConversionException('Imagemagick converting to ' . $out_ext . ' failed - exec() error');
        }

        if (!file_exists($out_file)) {
            throw new FileConversionException('Imagemagick converting to ' . $out_ext . ' failed - destination file not found');
        }

        return $out_file;
    }


    /**
     * Use 'exiftool' to determine the number of pages in a file
     *
     * @param string $filename Server filename
     * @return int Number of pages
     * @throws RuntimeException exiftool isn't installed/accessible to PHP
     * @throws FileConversionException exiftool was unable to determine the page count
     */
    public static function getPageCount($filename) {
        $cmd = 'exiftool -json ' . escapeshellarg($filename);

        $output = [];
        $return_code = null;
        exec($cmd, $output, $return_code);

        if ($return_code !== 0) {
            if (!self::installed('exiftool')) {
                throw new RuntimeException("Program 'exiftool' not installed - try the 'libimage-exiftool-perl' package");
            }
            throw new FileConversionException('Exiftool failed - exec() error');
        }

        $data = json_decode(implode('', $output));
        if (isset($data[0]->PageCount)) {
            return $data[0]->PageCount;
        }
        if (isset($data[0]->Pages)) {
            return $data[0]->Pages;
        }

        if (strtolower(File::getExt($filename)) === 'pdf') {
            throw new FileConversionException('Unable to determine page count');
        }

        // Exiftool couldn't process this file. Convert to PDF and try again.
        $dest_file_pdf = self::libreoffice($filename, 'pdf');
        $count = self::getPageCount($dest_file_pdf);
        unlink($dest_file_pdf);
        return $count;
    }


    /**
     * Convert file using PDF to Cairo
     * Needs package 'poppler-utils'
     *
     * @param string $in_file Input filename, with full path
     * @param string $out_ext Extension to convert file to, e.g. "png", "jpg".
     * @param int $page_index Page number of document, 0-based (applies to PDFs and other page-based documents)
     * @param int $density DPI
     * @return string Destination file in temp dir
     * @throws InvalidArgumentException The $out_ext argument has an invalid format
     * @throws RuntimeException ImageMagick isn't installed/accessible to PHP
     * @throws FileConversionException ImageMagick failed to convert the file
     */
    public static function pdftocairo($in_file, $out_ext, $page_index = 1, $density = 300) {
        $page_index = (int) $page_index;
        $density = (int) $density;

        static::validateExtension($out_ext);

        $out_file = STORAGE_PATH . 'temp/' . File::getNoext(basename($in_file)) . '_' . Sprout::randStr(4);

        $in_arg = escapeshellarg($in_file . '[' . $page_index . ']');
        $out_arg = escapeshellarg($out_file);

        $cmd = "pdftocairo -{$out_ext} -r {$density} -f {$page_index} -singlefile {$in_file} {$out_file} 2>&1";

        $output = [];
        $return_code = null;
        exec($cmd, $output, $return_code);

        if ($return_code !== 0) {
            if (!self::installed('convert')) {
                throw new RuntimeException("Program 'pdftocairo' not installed - try the 'poppler-utils' package");
            }
            throw new FileConversionException('Pdftocairo converting to ' . $out_ext . ' failed - exec() error');
        }

        $out_file .= ".{$out_ext}";
        if (!file_exists($out_file)) {
            throw new FileConversionException('Pdftocairo converting to ' . $out_ext . ' failed - destination file not found');
        }

        return $out_file;
    }


    /**
     * Checks to see that a conversion program is installed
     *
     * @param string $program The program name; 'libreoffice', 'convert' (i.e. ImageMagick), 'exiftool'
     * @return bool True if the program is installed
     */
    public static function installed($program)
    {
        $cmd = 'which ' . escapeshellarg($program);

        $out = [];
        $return_code = null;
        exec($cmd, $out, $return_code);

        if ($return_code !== 0) {
            return false;
        }
        return true;
    }
}
