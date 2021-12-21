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


$lang = array
(
    'there_can_be_only_one' => 'There can be only one instance of Kohana per page request',
    'uncaught_exception'    => 'Uncaught %s: %s in file %s on line %s',
    'invalid_method'        => 'Invalid method %s called in %s',
    'invalid_property'      => 'The %s property does not exist in the %s class.',
    'log_dir_unwritable'    => 'The log directory is not writable: %s',
    'resource_not_found'    => 'The requested %s, %s, could not be found',
    'invalid_filetype'      => 'The requested filetype, .%s, is not allowed in your view configuration file',
    'view_set_filename'     => 'You must set the the view filename before calling render',
    'no_default_route'      => 'Please set a default route in config/routes.php',
    'no_controller'         => 'Kohana was not able to determine a controller to process this request: %s',
    'page_not_found'        => 'The page you requested, %s, could not be found.',
    'stats_footer'          => 'Loaded in {execution_time} seconds, using {memory_usage} of memory. Generated by Kohana v{kohana_version}.',
    'error_file_line'       => '<tt>%s <strong>[%s]:</strong></tt>',
    'stack_trace'           => 'Stack Trace',
    'generic_error'         => 'Unable to Complete Request',
    'unknown_method'        => 'Unknown HTTP method',
    'errors_disabled'       => 'You can go to the <a href="%s">home page</a> or <a href="%s">try again</a>.',

    // Drivers
    'driver_implements'     => 'The %s driver for the %s library must implement the %s interface',
    'driver_not_found'      => 'The %s driver for the %s library could not be found',

    // Resource names
    'config'                => 'config file',
    'controller'            => 'controller',
    'helper'                => 'helper',
    'library'               => 'library',
    'driver'                => 'driver',
    'model'                 => 'model',
    'view'                  => 'view',
);
