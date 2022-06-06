<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace Sprout\Exceptions;

// Trigger the autoloader.
class_exists(\karmabunny\pdb\Exceptions\QueryException::class);

// @phpstan-ignore-next-line : IDE hints.
if (false) {
    /** @deprecated Use karmabunny\pdb\Exceptions\QueryException. */
    abstract class QueryException extends \karmabunny\pdb\Exceptions\QueryException {}
}
