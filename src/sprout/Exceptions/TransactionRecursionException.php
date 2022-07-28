<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2021 Karmabunny
 */

namespace Sprout\Exceptions;

// Trigger the autoloader.
class_exists(\karmabunny\pdb\Exceptions\TransactionRecursionException::class);

// @phpstan-ignore-next-line : IDE hints.
if (false) {
    /** @deprecated Use karmabunny\pdb\Exceptions\TransactionRecursionException. */
    abstract class TransactionRecursionException extends \karmabunny\pdb\Exceptions\TransactionRecursionException {}
}
