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

use Kohana;
use Sprout\Helpers\Router;
use Throwable;

/**
 *
 *
 * @package Sprout\Exceptions
 */
class HttpNotFoundException extends HttpException
{

    public function __construct(string $message = '', ?Throwable $previous = null)
    {
        if (empty($message)) {
            $page = Router::$current_uri . Router::$query_string;
            $message = Kohana::lang('core.page_not_found', $page);
        }

        parent::__construct(404, $message, $previous);
    }

}
