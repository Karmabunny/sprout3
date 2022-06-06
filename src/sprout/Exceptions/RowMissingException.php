<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace Sprout\Exceptions;

// Trigger the autoloader.
class_exists(\karmabunny\pdb\Exceptions\RowMissingException::class);

// @phpstan-ignore-next-line : IDE hints.
if (false) {
    /** @deprecated Use karmabunny\pdb\Exceptions\RowMissingException. */
    abstract class RowMissingException extends \karmabunny\pdb\Exceptions\RowMissingException {}
}
