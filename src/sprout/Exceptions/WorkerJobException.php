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

use Exception;


/**
 * Exception thrown when a database query fails, or gives an empty result set
 * for a query which required a row to be returned
 */
class WorkerJobException extends Exception
{
    /** @var string */
    public $cmd;

    /** @var string|null */
    public $output;
}
