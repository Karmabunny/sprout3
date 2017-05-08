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

/**
* Defines that a specific controller is able to handle front-end entrances from the pages module
**/
interface FrontEndEntrance {

    /**
     * Return the list of [argument => label] options a tool page can call on this controller.
     *
     * See {@see UserController::_getEntranceArguments} for an example implementation
     *
     * @return array The keys are the valid arguments, and the values are their human-friendly labels
     */
    public function _getEntranceArguments();


    /**
     * Acts as the entrance-point of the controller, calling internal methods specific to each argument
     *
     * See {@see UserController::entrance} for an example implementation
     *
     * @param string $argument A key in the array returned by {@see FrontEndEntrance::_getEntranceArguments},
     *        each of which must be handled, usually by mapping onto one of the controller's own methods.
     * @return void The underlying method should either output HTML or redirect
     */
    public function entrance($argument);

}
