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
use Throwable;

/**
 * A generic HTTP error.
 *
 * These are exceptions that are arguably not exceptional. They should generally
 * be restricted to controllers and related helpers. Models/providers/factories/etc
 * should _not_ be emitting these.
 *
 * @package Sprout\Exceptions
 */
class HttpException extends Exception implements HttpExceptionInterface
{
    /** @var int */
    public $status = 500;


    /**
     * Create a new HTTP error.
     *
     * @param int|null $status null implies the 'default' value
     * @param string $message
     * @param Throwable|null $previous
     */
    public function __construct(?int $status = null, string $message = '', ?Throwable $previous = null)
    {
        $this->status = $status ?? $this->status;
        parent::__construct($message, 0, $previous);
    }


    /** @inheritdoc */
    public function getStatusCode(): int
    {
        return $this->status;
    }
}
