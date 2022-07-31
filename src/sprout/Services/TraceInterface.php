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

namespace Sprout\Services;

use Throwable;

/**
 * An interface to send exceptions and messages.
 *
 * @package Sprout\Services
 */
interface TraceInterface extends ServiceInterface
{

    /**
     * Report an error.
     *
     * @param Throwable $exception
     * @param array $meta
     * @return bool
     */
    public static function logException(Throwable $exception, array $meta = []): bool;


    /**
     * Report a message.
     *
     * @param string $message
     * @param array $meta
     * @return bool
     */
    public static function logMessage(string $message, array $meta = []): bool;

}
