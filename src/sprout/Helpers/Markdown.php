<?php
/**
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

namespace Sprout\Helpers;

use cebe\markdown\Markdown as CebeMarkdown;
use cebe\markdown\Parser;
use InvalidArgumentException;
use Kohana;

/**
 * Markdown helper.
 */
class Markdown
{

    /**
     * Get a markdown parser.
     *
     * Defaults are configured in the 'markdown' config.
     *
     * @param array $options
     * @return Parser
     * @throws InvalidArgumentException
     */
    public static function getParser(array $options = []): Parser
    {
        $options = array_merge(
            Kohana::config('markdown.options'),
            $options,
        );

        $flavor = $options['flavor'] ?? null;
        unset($options['flavor']);

        $flavors = Kohana::config('markdown.flavors');
        $class = $flavor ? $flavors[$flavor] : CebeMarkdown::class;

        if (!$class) {
            throw new InvalidArgumentException("Unknown markdown flavor '{$flavor}'");
        }

        // Create + configure the parser.
        $parser = new $class();

        foreach ($options as $key => $value) {
            if (property_exists($class, $key)) {
                $parser->$key = $value;
            }
        }

        return $parser;
    }


    /**
     * Render markdown text.
     *
     * @param string $text
     * @param array|bool $options or 'true' for inline
     * @return string
     */
    public static function parse(string $text, $options = []): string
    {
        if (!is_array($options)) {
            $options = [
                'inline' => (bool) $options,
            ];
        }

        $parser = self::getParser($options);

        if ($options === true) {
            return $parser->parseParagraph($text);
        }
        else {
            return $parser->parse($text);
        }
    }
}
