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

// Trigger the autoloader.
class_exists(\karmabunny\kb\ValidationException::class);

// @phpstan-ignore-next-line : IDE hints.
if (false) {
    /** @deprecated Use karmabunny\kb\ValidationException. */
    abstract class ValidationException extends \karmabunny\kb\ValidationException {}
}
