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

use Nette\Neon\Decoder;

/**
 * Simple parser & generator for Nette Object Notation.
 * @see https://neon.nette.org
 */
final class Neon
{

	/**
	 * Returns value converted to NEON.
	 */
	public static function encode(mixed $value, string $indentation = "    "): string
	{
		$encoder = new Encoder;
		$encoder->indentation = $indentation;
		return $encoder->encode($value);
	}


	/**
	 * Converts given NEON to PHP value.
	 */
	public static function decode(string $input): mixed
	{
		$decoder = new Decoder;
		return $decoder->decode($input);
	}
}
