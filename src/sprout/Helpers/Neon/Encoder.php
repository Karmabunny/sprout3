<?php
/*
 * Copyright (C) 2024 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 *
 * This file is adapted from the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Sprout\Helpers\Neon;

use Nette\Neon\Entity;
use Nette\Neon\Lexer;
use Nette\Neon\Neon;
use Nette\Neon\Node;

/**
 * Converts value to NEON format.
 *
 * This is modified to perform preferred formatting for NEON forms.
 */
class Encoder
{
	public string $indentation = "    ";

	public array $jsonKeys = [
		'validate',
		'args',
	];


	/**
	 * Returns the NEON representation of a value.
	 */
	public function encode(mixed $val): string
	{
		$node = $this->valueToNode(null, $val);
		return $node->toString();
	}


	public function valueToNode(mixed $key, mixed $val): Node
	{
		if ($val instanceof \DateTimeInterface) {
			return new Node\LiteralNode($val);

		} elseif ($val instanceof Entity && $val->value === Neon::Chain) {
			$node = new Node\EntityChainNode;
			foreach ($val->attributes as $entity) {
				$node->chain[] = $this->valueToNode(null, $entity);
			}

			return $node;

		} elseif ($val instanceof Entity) {
			return new Node\EntityNode(
				$this->valueToNode(null, $val->value),
				$this->arrayToNodes($val->attributes),
			);

		} elseif (is_object($val) || is_array($val)) {
			if ($key and in_array($key, $this->jsonKeys)) {
				$node = new JsonArrayNode($this->indentation);
				$node->items = $val;
				return $node;
			}

			$node = new BlockArrayNode;
			$node->items = $this->arrayToNodes($val);
			return $node;

		} elseif (is_string($val)) {
			// Always add delimiters for strings.
			return new Node\StringNode($val);

		} else {
			return new Node\LiteralNode($val);
		}
	}


	/** @return Node\ArrayItemNode[] */
	private function arrayToNodes(mixed $val): array
	{
		$res = [];
		$counter = 0;
		$hide = true;
		foreach ($val as $k => $v) {
			$res[] = $item = new Node\ArrayItemNode;
			$item->key = null;

			// Retain conditional delimiting for keys.
			if (!($hide && $k === $counter)) {
				$item->key = Lexer::requiresDelimiters($k)
					? new Node\StringNode($k)
					: new Node\LiteralNode($k);
			}

			$item->value = self::valueToNode($k, $v);
			if ($item->value instanceof BlockArrayNode) {
				$item->value->indentation = $this->indentation;
			}

			if ($hide && is_int($k)) {
				$hide = $k === $counter;
				$counter = max($k + 1, $counter);
			}
		}

		return $res;
	}
}
