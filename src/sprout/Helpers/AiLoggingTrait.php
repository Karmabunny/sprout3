<?php
/*
 * Copyright (C) 2024 Karmabunny Pty Ltd.
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

use Exception;
use Sprout\Models\SproutAiApiRequest;

/**
 *
 */
trait AiLoggingTrait
{

    /** @var int|null */
    private static $_log_id;


    /** @var float */
    private static $_time_start;


    /**
     * Log a request on its way out to a provider
     *
     * @param string $function The __FUNCTION__ being ran at call time, if part of a class
     * @param string $endpoint The provider endpoint we're calling
     * @param array $data The request data
     * @return SproutAiApiRequest
     */
    public static function logRequest(string $function, string $endpoint, array $data)
    {
        $log = new SproutAiApiRequest([
            'ai_provider_class' => static::class,
            'ai_provider_function' => $function,
            'endpoint' => $endpoint,
            'request' => json_encode($data),
        ]);

        $log->save();
        self::$_log_id = $log->id;

        self::$_time_start = microtime(true);

        return $log;
    }


    /**
     * Log a response from a provider. Associate with current request if possible.
     *
     * Use self::$logError() for error responses
     *
     * @param string $function The __FUNCTION__ being ran at call time
     * @param string $endpoint The provider endpoint we're calling
     * @param array $data The response data
     * @param bool $error Whether this is an error response. Auto set via self::$logError()
     * @return SproutAiApiRequest
     */
    public static function logResponse(string $function, string $endpoint, array $data, bool $error = false)
    {
        if (!empty(self::$_log_id)) {
            $log = SproutAiApiRequest::findOne(['id' => self::$_log_id]);
        } else {
            $log = new SproutAiApiRequest([
                'ai_provider_class' => static::class,
                'ai_provider_function' => $function,
                'endpoint' => $endpoint,
            ]);
        }

        $log->response_status = $error ? 'error' : 'complete';
        $log->response = json_encode($data);
        $log->save();

        if (self::$_time_start) {
            $log->timing = microtime(true) - self::$_time_start;
            $log->save();
        }

        return $log;
    }


    /**
     * Simple response logging wrapper to call in case of error
     *
     * @param string $function The __FUNCTION__ being ran at call time
     * @param string $endpoint The provider endpoint we're calling
     * @param array $data The response data
     * @return SproutAiApiRequest
     */
    public static function logError(string $function, string $endpoint, array $data)
    {
        return self::logResponse($function, $endpoint, $data, true);
    }


    /**
     * Simple response logging wrapper to call in case of error
     *
     * @param string $function The __FUNCTION__ being ran at call time
     * @param string $endpoint The provider endpoint we're calling
     * @param Exception $e The exception thrown
     * @return SproutAiApiRequest
     */
    public static function logAndThrowException(string $function, string $endpoint, Exception $e)
    {
        self::logResponse($function, $endpoint, ['exception' => $e->getMessage()], true);

        throw $e;
    }

}
