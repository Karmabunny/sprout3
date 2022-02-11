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


/**
 * Exception thrown when a CORS request fails.
 *
 * This is not thrown, only logged for debugging in production environments.
 *
 * @see Sprout\Helpers\Cors
 */
class CorsException extends \Exception
{

    /** @var string */
    public $headers = [];

    /** @var string */
    public $method = '';

    /** @var string */
    public $origin = '';
}
