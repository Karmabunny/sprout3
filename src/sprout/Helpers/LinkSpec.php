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
 * Base class for the backend of the extensible link system: {@see Lnk}
 */
abstract class LinkSpec {

    /**
     * Get the URL for a given link
     *
     * @param mixed $specdata
     * @return string|null absolute URL
     */
    abstract public function getUrl($specdata);


    /**
     * Get any extra html attributes to use for a given link
     *
     * @param mixed $specdata
     * @return array
     */
    abstract public function getAttrs($specdata);


    /**
     * If there are any {@see Needs} calls that the edit form requires, they should be loaded here
     *
     * @return void
     */
    public function loadNeeds() {}


    /**
     * Get the HTML to use for editing a given linkspec
     *
     * The HTML should create a HTML field with the name $field_name
     * If there is a spec currently being edited, the specdata will
     * be provided in $curr_specdata
     *
     * @param string $field_name
     * @param mixed $curr_specdata
     * @return string HTML
     **/
    abstract public function getEditForm($field_name, $curr_specdata);


    /**
     * Validate the submission, for instances where certain constraints apply
     *
     * @param mixed $specdata
     * @return bool
     */
    abstract public function isValid($specdata);

}

