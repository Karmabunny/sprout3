<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */
namespace Sprout\Helpers\AI;

use Exception;
use Kohana;
use Sprout\Helpers\AI\AiApiInterface;
use Sprout\Helpers\AI\OpenAiApi;
use Sprout\Helpers\Sprout;

/**
 * General helpers for AI system
 */
class AI
{

    /**
     * Available classes for AI.
     *
     * Core config ai.enabled_class should be one of these values
     */
    const AI_CLASSES = [
        OpenAiApi::class => 'OpenAi',
    ];


    /**
     * Check if AI usage is enabled (class configured and return enabled)?
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        $enabled_class = Kohana::config('ai.enabled_class');
        $class = array_search($enabled_class, self::AI_CLASSES);

        if ($class === false) {
            throw new Exception('Invalid AI class: ' . $enabled_class);
        }

        /** @var AiApiInterface */
        $class = Sprout::instance($class);

        // Ask the class if it has everything it needs to run
        return $class->getUsable();
    }


    /**
     * Get a list of endpoint methods available for each AI class
     *
     * @return array
     */
    public static function classesAndMethod()
    {
        $out = [];
        foreach (self::AI_CLASSES as $class => $name) {
            $out[$class] = $class::ENDPOINTS;
        }

        return $out;
    }

}