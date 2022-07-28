<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace Sprout\Exceptions;

// Trigger the autoloader.
class_exists(\karmabunny\pdb\Exceptions\ConstraintQueryException::class);

// @phpstan-ignore-next-line : IDE hints.
if (false) {
    /** @deprecated Use karmabunny\pdb\Exceptions\ConstraintQueryException. */
    abstract class ConstraintQueryException extends \karmabunny\pdb\Exceptions\ConstraintQueryException {}
}
