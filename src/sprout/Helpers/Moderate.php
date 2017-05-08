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

abstract class Moderate {
    protected $friendly_name = '- No name -';
    protected $db;



    public function __construct()
    {
    }


    /**
    * Return the 'friendly' name of this item
    **/
    public final function getFriendlyName() {
        return $this->friendly_name;
    }


    /**
    * Return an array of one or more items which need moderating.
    *
    * The array should have the following format:
    * [] = array('id' => 'html')
    *      id      record identifier
    *      html    record preview html
    *
    * Return NULL on error
    **/
    public function getList()
    {
        return NULL;
    }


    /**
    * Approve the specified item.
    * This is called from within a transaction.
    **/
    public abstract function approve($id);


    /**
    * Delete the specified item.
    * Usually the best is to use the controller _deleteSave method.
    * This is called from within a transaction.
    **/
    public abstract function delete($id);

}


