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


abstract class RteLibrary {
    protected $name;


    /**
    * Return the name of this library
    **/
    public final function getName() {
        return $this->name;
    }


    /**
    * Do a library browse
    *
    * @return array of RteLibContainer and RteLibObject objects
    **/
    public abstract function browse($path);


    /**
    * Do a library search
    *
    * @return array of RteLibContainer and RteLibObject objects
    **/
    public abstract function search($term);

}
