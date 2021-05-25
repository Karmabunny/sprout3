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

use Throwable;


/**
 * Methods for handling JSON output, usually for AJAX responses
 */
class Json
{

    /**
    * Sends a JSON message which has the success field set to zero
    * and the specified message in the message field.
    *
    * If $message is an Exception object, the message will be extracted
    * and if on the test server, a stacktrace is included as well.
    *
    * This method halts execution
    **/
    public static function error($message)
    {
        if ($message instanceof Throwable) {
            $json = array('success' => 0, 'message' => $message->getMessage());

            if (!IN_PRODUCTION) {
                $json['file'] = $message->getFile();
                $json['line'] = $message->getLine();
                $json['stacktrace'] = $message->getTraceAsString();
            }

        } else {
            $json = array('success' => 0, 'message' => $message);
        }

        header('Content-type: application/json');
        echo json_encode($json);
        exit;
    }


    /**
    * Sends a JSON confirmation response, and then terminates.
    *
    * The returned data will have the key 'success' => 1 added.
    * If no data is provided, the returned JSON will only contain the 'success' key.
    *
    * This method halts execution
    **/
    public static function confirm($data = null)
    {
        if (!is_array($data)) $data = array();

        $data['success'] = 1;

        header('Content-type: application/json');
        echo json_encode($data);
        exit;
    }


    /**
     * Outputs the JSON data (encoded) + correct mime type, then stops script execution
     *
     * @param mixed $data Any kind of data to be JSON encoded
     * @param int $options E.g. JSON_PRETTY_PRINT, as per http://php.net/manual/en/function.json-encode.php
     * @return void This function calls echo
     */
    public static function out($data, $options = 0)
    {
        ob_end_clean();
        header('Content-type: application/json');
        echo json_encode($data, $options);
        exit(0);
    }

}


