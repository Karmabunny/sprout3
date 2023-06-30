<?php
/*
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

use Kohana_Exception;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Template;

/**
 * Renderer for twig engine
 *
 * Twig view are located with with same loader rules as PHP views,
 * requiring a `sprout/modules/skin` prefix.
 *
 * Sprout provides a set of functions + filters as well as the core extensions:
 * - https://twig.symfony.com/doc/3.x/filters/index.html
 * - https://twig.symfony.com/doc/3.x/functions/index.html
 * - {@see SproutExtension}
 *
 * Sprout exposes a global `sprout` variable for helpers and modules.
 * {@see SproutVariable}
 */
class TwigView extends BaseView
{
    protected static $EXTENSION = '.twig';

    /** @var Environment */
    protected static $twig;

    /** @var TwigSkinLoader */
    protected static $loader;

    /** @var string */
    protected $kohana_template_name;


    public static function getTwig(): Environment
    {
        if (!isset(self::$twig)) {
            $cache_path = STORAGE_PATH . 'cache/twig_templates';

            if (!is_dir($cache_path)) {
                @mkdir($cache_path, 0775, true);
            }

            self::$loader = new TwigSkinLoader();
            self::$twig = new Environment(self::$loader, [
                'cache' => $cache_path,
                'auto_reload' => true,
                'debug' => !IN_PRODUCTION,
                'strict_variables' => !IN_PRODUCTION,
            ]);

            if (!IN_PRODUCTION) {
                self::$twig->addExtension(new DebugExtension());
            }

            self::$twig->addExtension(new SproutExtension());
        }

        return self::$twig;
    }


    /** @inheritdoc */
    public function __construct($name, array $data = [])
    {
        // Initialise the twig renderer.
        self::getTwig();

        $this->kohana_template_name = $name;
        parent::__construct($name, $data);
    }


    /** @inheritdoc */
    public function render($print = FALSE, $renderer = FALSE)
    {
        if (empty($this->kohana_filename)) {
            throw new Kohana_Exception('core.view_set_filename');
        }

        $output = self::getTwig()->render($this->kohana_template_name, $this->kohana_local_data);

        if ($renderer !== FALSE AND is_callable($renderer, TRUE))
        {
            // Pass the output through the user defined renderer
            $output = call_user_func($renderer, $output);
        }

        if ($print === TRUE)
        {
            // Display the output
            echo $output;
            return;
        }

        return $output;
    }


    /**
     * Render an inline twig template.
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    public static function renderString(string $template, array $data = []): string
    {
        return self::getTwig()->createTemplate($template)->render($data);
    }


    /**
     * Not actually deprecated - this will always exist.
     * Just for clarity please use the base class.
     *
     * @deprecated Use BaseView::create().
     */
    public static function create(string $name, $data = []): BaseView
    {
        return parent::create($name, $data);
    }


    /**
     * Fetch real trace information for a twig template.
     *
     * The result includes a mapped file path, line number, and source code.
     *
     * @param string $file Compiled template path
     * @param int|null $line Trace line number
     * @return array|null [ file, line, code ]
     */
    public static function decodeErrorFrame(string $file, int $line = null)
    {
        try {
            // This relies on the cache path being consistent.
            if (strpos($file, 'cache/twig_templates') === false) {
                return null;
            }

            $contents = file_get_contents($file);
            $matches = [];

            if (!preg_match('/^class (\w+)/m', $contents, $matches)) {
                return null;
            }

            $twig = self::getTwig();

            [, $class] = $matches;

            /** @var Template $template */
            $template = new $class($twig);
            $source = $template->getSourceContext();

            // The template file path.
            $sourceFile = $source->getPath();

            // Re-map the line number.
            $sourceLine = null;

            if ($line !== null) {
                foreach ($template->getDebugInfo() as $codeLine => $templateLine) {
                    if ($codeLine <= $line) {
                        $sourceLine = $templateLine;
                        break;
                    }
                }
            }

            // Fetch a sample of the code.
            $sourceCode = null;

            if ($sourceLine !== null) {
                $lines = explode("\n", $source->getCode(), $sourceLine + 1);
                $sourceCode = $lines[$sourceLine - 1] ?? null;

                if ($sourceCode) {
                    $sourceCode = trim($sourceCode);
                }
            }

            return [$sourceFile, $sourceLine, $sourceCode];
        }
        catch (\Throwable $error) {
            // Shush.
        }

        return null;
    }


    /**
     * Process twig components of a backtrace.
     *
     * Backtrace renders should read the 'source' key to get the source code sample
     * for the current line. This should replace the class->method(args) component.
     *
     * @param array $trace
     * @param bool $clean
     * @return array
     */
    public static function processBacktrace(array $trace, bool $clean = true): array
    {
        foreach ($trace as $key => &$frame) {

            if (empty($frame['file'])) {
                if ($clean) unset($trace[$key]);
                continue;
            }

            if ($clean and str_ends_with($frame['file'], 'TwigView.php')) {
                break;
            }

            $twig_frame = TwigView::decodeErrorFrame($frame['file'], $frame['line'] ?? null);
            if (!$twig_frame) {
                if ($clean) unset($trace[$key]);
                continue;
            }

            [$file, $line, $code] = $twig_frame;

            $frame['file'] = $file;
            $frame['line'] = $line;

            if ($code) {
                $frame['source'] = $code;
            }
        }
        unset($frame);

        // die;

        return $trace;
    }
}
