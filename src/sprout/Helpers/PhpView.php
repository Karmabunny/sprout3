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
namespace Sprout\Helpers;

use Exception;
use Kohana;
use Kohana_Exception;


/**
 * Loads and displays Kohana view files.
 */
class PhpView extends BaseView
{

    protected static $EXTENSION = '.php';


    /** @inheritdoc */
    public function render($print = FALSE, $renderer = FALSE)
    {
        if (empty($this->kohana_filename))
            throw new Kohana_Exception('core.view_set_filename');

        // Merge global and local data, local overrides global with the same name
        $data = array_merge(self::$kohana_global_data, $this->kohana_local_data);
        foreach ($data as &$datum) {
            if ($datum instanceof BaseView) $datum = $datum->render();
        }

        $data_into_view = function($_view_filename, $input_data)
        {
            if ($_view_filename == '') return '';

            ob_start();

            // Import the view variables to local scope
            extract($input_data, EXTR_SKIP);

            try {
                include $_view_filename;
            } catch (Exception $e) {
                ob_end_clean();
                throw $e;
            }

            return ob_get_clean();
        };

        $output = $this->getDebugComment();
        $output .= $data_into_view($this->kohana_filename, $data);

        if ($renderer !== FALSE AND is_callable($renderer, TRUE))
        {
            // Pass the output through the user defined renderer
            $output = call_user_func($renderer, $output);
        }

        if ($print === TRUE)
        {
            // Display the output
            echo $output;
            return null;
        }

        return $output;
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

} // End View
