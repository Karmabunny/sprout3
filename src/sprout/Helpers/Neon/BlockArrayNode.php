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

use Nette\Neon\Node\ArrayNode;

/** @internal */
final class BlockArrayNode extends ArrayNode
{
	public function __construct(
		public string $indentation = '',
	) {
	}


	/** @inheritdoc */
	public function toString(): string
	{
		if (count($this->items) === 0) {
			return '[]';
		}

		$res = '';

		foreach ($this->items as $item) {
			$v = $item->value->toString();

			if ($item->key) {
				$res .= $item->key->toString() . ':';

				if ($item->value instanceof BlockArrayNode and $item->value->items) {
					$v = preg_replace('#^(?=.)#m', $this->indentation . substr($this->indentation, 2), $v);
					$res .= "\n" . $v . (substr($v, -2, 1) === "\n" ? '' : "\n");
				} else if ($item->value instanceof JsonArrayNode and $item->value->items) {
					$res .= "" . $v . (substr($v, -2, 1) === "\n" ? '' : "\n");
				} else {
					$res .= ' ' . $v . "\n";
				}
			}
			else {
				$res .= substr($this->indentation, 2) . '- ';

				if ($item->value instanceof BlockArrayNode and $item->value->items) {
					$res .= $v . (substr($v, -2, 1) === "\n" ? '' : "\n");
				} else {
					$res .= $v . "\n";
				}
			}
		}

		return $res;
	}
}
