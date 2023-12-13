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

use JsonException;
use karmabunny\kb\Json as KbJson;
use Throwable;


/**
 * Methods for handling JSON output, usually for AJAX responses
 */
class Json
{


    /**
     * Encode a json array as a string.
     *
     * @param mixed $json
     * @param bool|int $flags Applies pretty flags if `true`.
     * @return string
     * @throws JsonException Any parsing error
     */
    public static function encode($json, $flags = 0): string
    {
        return KbJson::encode($json, $flags);
    }


    /**
     * Decode a JSON string, with objects converted into arrays
     *
     * @param string $str A JSON string. As per the spec, this should be UTF-8 encoded
     * @param int $flags Default JSON_INVALID_UTF8_SUBSTITUTE (if available)
     * @return mixed The decoded value
     * @throws JsonException Any parsing error
     */
    public static function decode(string $str, $flags = 0)
    {
        return KbJson::decode($str, $flags);
    }


    /**
     * Convert an error/exception into a JSON body.
     *
     * @param Throwable $error
     * @param bool $serialized use JsonSerializable if available.
     * @return array
     */
    public static function encodeError(Throwable $error, bool $serialized = true): array
    {
        $json = KbJson::error($error, $serialized);
        $json['stacktrace'] = $error->getTraceAsString();
        return $json;
    }


    /**
    * Sends a JSON message which has the success field set to zero
    * and the specified message in the message field.
    *
    * If $message is an Exception object, the message will be extracted
    * and if on the test server, a stacktrace is included as well.
    *
    * This method halts execution
    *
    * @param mixed $message
    * @return never echos
    **/
    public static function error($message)
    {
        if ($message instanceof Throwable) {
            $json = array('success' => 0, 'message' => $message->getMessage());

            if (!IN_PRODUCTION) {
                $error = self::encodeError($message, false);
                $json = array_merge($json, $error);
            }

        } else if (is_array($message)) {
            $message['success'] = 0;
            return $message;

        } else {
            $json = array('success' => 0, 'message' => $message);
        }

        self::out($json);
    }


    /**
    * Sends a JSON confirmation response, and then terminates.
    *
    * The returned data will have the key 'success' => 1 added.
    * If no data is provided, the returned JSON will only contain the 'success' key.
    *
    * This method halts execution
    *
    * @param mixed $data
    * @param never echos
    **/
    public static function confirm($data = null)
    {
        if (!is_array($data)) $data = array();

        $data['success'] = 1;

        self::out($data);
    }


    /**
     * Outputs the JSON data (encoded) + correct mime type, then stops script execution
     *
     * @param mixed $data Any kind of data to be JSON encoded
     * @param int $options E.g. JSON_PRETTY_PRINT, as per http://php.net/manual/en/function.json-encode.php
     * @return never This function calls echo
     */
    public static function out($data, $options = 0)
    {
        if (is_array($data) and empty($data)) {
            $data = (object)[];
        }

        ob_end_clean();
        header('Content-type: application/json');
        echo json_encode($data, $options);
        exit(0);
    }

}


