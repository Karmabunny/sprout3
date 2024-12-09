<?php
namespace Sprout\Exceptions;

use Exception;

class JwtException extends Exception
{
    public function __construct(string $message = 'Invalid Token')
    {
        parent::__construct($message);
    }
}