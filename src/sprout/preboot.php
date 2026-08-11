<?php
/**
 * Good spot for namespace aliases.
 *
 * This is loaded on every request - caution please.
 */

class_alias(\karmabunny\pdb\Exceptions\QueryException::class, \Sprout\Exceptions\QueryException::class);
class_alias(\karmabunny\pdb\Exceptions\RowMissingException::class, \Sprout\Exceptions\RowMissingException::class);
class_alias(\karmabunny\pdb\Exceptions\ConstraintQueryException::class, \Sprout\Exceptions\ConstraintQueryException::class);
class_alias(\karmabunny\pdb\Exceptions\TransactionRecursionException::class, \Sprout\Exceptions\TransactionRecursionException::class);
class_alias(\karmabunny\interfaces\HttpExceptionInterface::class, \Sprout\Exceptions\HttpExceptionInterface::class);

class_alias(\karmabunny\kb\ValidationException::class, \Sprout\Exceptions\ValidationException::class);

// Old error constants.
define('E_KOHANA', 42);
define('E_PAGE_NOT_FOUND', 43);
define('E_DATABASE_ERROR', 44);

// Load core files
require __DIR__ . '/core/utf8.php';
require __DIR__ . '/core/Event.php';
require __DIR__ . '/core/Kohana.php';
