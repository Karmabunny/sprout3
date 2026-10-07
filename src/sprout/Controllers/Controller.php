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
 *
 * This class was originally from Kohana 2.3.4
 * Copyright 2007-2008 Kohana Team
 */
namespace Sprout\Controllers;

use Sprout\Helpers\Profiling;


/**
 * Kohana Controller class. The controller class must be extended to work
 * properly, so this class is defined as abstract.
 */
abstract class Controller extends BaseController
{

    /** @inheritdoc */
    public function _run($method, $args)
    {
        $class = static::class;
        Profiling::begin($method, $class, ['args' => $args]);

        register_shutdown_function(function() use ($method, $class) {
            Profiling::end($method, $class);
        });

        return parent::_run($method, $args);
    }

} // End Controller Class
