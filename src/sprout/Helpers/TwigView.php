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
use Twig\Loader\ArrayLoader;
use Twig\Extension\DebugExtension;

/**
 * Renderer for twig engine
 *
 * @todo - There's lots of opportunity to cache these templates.
 */
class TwigView extends View
{
    protected static $EXTENSION = '.twig';

    /** @var Environment */
    protected static $twig;

    /** @var ArrayLoader */
    protected static $loader;


    /** @inheritdoc */
    public function __construct($name, array $data = [])
    {
        // Initialise the twig renderer.
        if (!isset(self::$twig)) {
            self::$loader = new ArrayLoader([]);
            self::$twig = new Environment(self::$loader, [
                'debug' => !IN_PRODUCTION,
                'strict_variables' => !IN_PRODUCTION,
            ]);

            if (!IN_PRODUCTION) {
                self::$twig->addExtension(new DebugExtension());
            }

            self::$twig->addExtension(new SproutExtension());
        }
        parent::__construct($name, $data);
    }


    /** @inheritdoc */
    public function render($print = FALSE, $renderer = FALSE)
    {
        if (empty($this->kohana_filename)) {
            throw new Kohana_Exception('core.view_set_filename');
        }

        // Load in the view, which static/shallow caches in the loader.
        if (!self::$loader->exists($this->kohana_filename)) {
            $view = @file_get_contents($this->kohana_filename);
            if ($view === false) {
                throw new Kohana_Exception('core.view_set_filename');
            }

            self::$loader->setTemplate($this->kohana_filename, $view);
        }

        $output = self::$twig->render($this->kohana_filename, $this->kohana_local_data);

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
}
