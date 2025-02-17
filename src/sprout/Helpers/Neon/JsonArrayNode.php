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

use Nette\Neon\Node;

/** @internal */
final class JsonArrayNode extends Node
{

	public array $items = [];


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

		$isList = array_is_list($this->items);
		$res = '';

		foreach ($this->items as $key => $item) {
			$v = json_encode($item);

			if ($isList) {
				$res .= "\n" . substr($this->indentation, 2) . '- ' . $v;
			} else {
				$res .= "\n{$this->indentation}{$key}: {$v}";
			}
		}

		return $res;
	}


	/** @inheritdoc */
	public function toValue(): mixed
	{
		return $this->items;
	}
}
