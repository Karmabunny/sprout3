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
namespace Sprout\Exceptions;

use Throwable;

/**
 * These are errors that can inform the status code.
 *
 * TODO update cors + file-missing, kohana errors to inherit this interface.
 *
 * @package Sprout\Exceptions
 */
interface HttpExceptionInterface extends Throwable
{

    /**
     * The HTTP status code, as recommended by the emitter.
     *
     * @return int
     */
    public function getStatusCode(): int;
}
