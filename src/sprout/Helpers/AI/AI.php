<?php
namespace Sprout\Helpers\AI;

use Exception;
use Kohana;
use Sprout\Helpers\AI\AiApiBase;
use Sprout\Helpers\AI\OpenAiApi;


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

        /** @var AiApiBase */
        $class = new $class();

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