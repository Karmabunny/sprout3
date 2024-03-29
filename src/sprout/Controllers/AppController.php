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

namespace Sprout\Controllers;

use Kohana;
use Sprout\Exceptions\HttpException;
use Sprout\Exceptions\HttpExceptionInterface;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Json;
use Sprout\Helpers\Request;
use Sprout\Helpers\JsErrors;
use Sprout\Models\ExceptionLogModel;
use Throwable;

/**
 * Generic public utilities
 */
class AppController extends Controller
{

    /**
     * A simple 200 response.
     *
     * This is available at: /_healthcheck
     *
     * We want this to do all the ordinary things that a request would do.
     * That includes subsites, pre/post page routing, all of it.
     */
    public function healthcheck()
    {
        echo 'OK';
    }


    /**
     * Log errors from the frontend using a kbtrace compatible library.
     *
     * This endpoint must be configured in the frontend, as well as relevant
     * auth keys.
     *
     * The {@see JsErrors} helper is available to generate the config, which is
     * also available to twig via the `sprout.errors` variable.
     *
     * @return never echos JSON
     */
    public function logJsException()
    {
        set_exception_handler(function(Throwable $error) {
            if ($error instanceof HttpExceptionInterface) {
                http_response_code($error->getStatusCode());
            } else {
                http_response_code(500);
                Kohana::logException($error, false);
            }

            Json::error($error);
        });

        if (!JsErrors::authorize()) {
            throw new HttpException(403, 'Invalid request');
        }

        if (Request::method() !== 'post') {
            throw new HttpException(400, 'Invalid method, expected POST');
        }

        $payload = Request::getBody([
            'application/json',
            'application/x-www-form-urlencoded',
        ]);

        if ($payload === null) {
            throw new HttpException(400, 'Missing body, expected JSON or form data');
        }

        // Additional checks for form data.
        if (Request::getContentType() === 'application/x-www-form-urlencoded') {
            $csrf = Csrf::getTokenValue();

            if ($payload['csrf'] ?? '' !== $csrf) {
                throw new HttpException(403, 'Invalid CSRF token');
            }

            unset($payload['csrf']);
        }

        // Create it.
        $exception = new ExceptionLogModel();
        $exception->parseJsPayload($payload);
        $exception->save();

        // Kinda make a thing.
        // The UID isn't recorded anywhere. It's all for show anyway - I don't
        // believe we have a real use for it (yet).
        Json::confirm([
            'uid' => $exception->getUid(),
            'reference' => 'CE' . $exception->id,
        ]);
    }
}
