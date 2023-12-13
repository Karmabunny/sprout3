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

use Exception;


/**
* Defines a table which can be searched against using the front-end search
**/
class SearchHandler
{
    private $table;
    private $ctlr_name;
    private $where;
    private $joins;
    private $having;


    /**
     * @param string $table The name of the keywords table, e.g. page_keywords
     * @param string $ctlr_name The name of a controller which implements the
     *        FrontEndSearch interface. Must be fully namespaced.
     */
    public function __construct($table, $ctlr_name)
    {
        if (strpos($ctlr_name, '\\') === false) {
            throw new \InvalidArgumentException('Controller name must be fully namespaced');
        }
        $this->table = $table;
        $this->ctlr_name = $ctlr_name;
        $this->where = array();
        $this->joins = array();
        $this->having = array();
    }


    /**
    * Gets the table name for the keywords table, e.g. page_keywords
    **/
    public function getTable()
    {
        return $this->table;
    }

    /**
    * Gets the table name for the main table, e.g. pages
    **/
    public function getMainTable()
    {
        return Inflector::plural(str_replace('_keywords', '', $this->table));
    }

    /**
    * Sets the table name for the keywords table, e.g. page_keywords
    **/
    public function setTable($val)
    {
        $val = trim($val);
        if ($val == '') throw new Exception("No input value specified");
        $this->table = $val;
    }


    /**
    * Gets the controller name, e.g. PageController
    **/
    public function getCtlrName()
    {
        return $this->ctlr_name;
    }

    /**
    * Sets the controller name, e.g. PageController. The specified controller must implement the FrontEndSearch interface.
    **/
    public function setCtlrName($val)
    {
        if (! class_exists($val)) throw new Exception("Specified controller class does not exist");
        $this->ctlr_name = $val;
    }


    /**
    * Gets all where clauses which should be added to the search query
    **/
    public function getWhere()
    {
        return $this->where;
    }

    /**
    * Adds a where clause to this search handler.
    * Where clauses should refer to the main table using the alias 'main'
    * e.g. $handler->addWhere("main.active = 1")
    **/
    public function addWhere($val)
    {
        $this->where[] = $val;
    }


    /**
    * Gets all joins which should be added to the search query
    **/
    public function getJoins()
    {
        return $this->joins;
    }

    /**
    * Adds a join to this search handler.
    * Inner joins should refer to the main table using the alias 'main'
    * e.g. $handler->addJoin("INNER JOIN categories ON categories.item_id = main.id")
    **/
    public function addJoin($val)
    {
        $this->joins[] = $val;
    }


    /**
    * Gets all having clauses which should be added to the search query
    **/
    public function getHaving()
    {
        return $this->having;
    }

    /**
    * Adds a having clause to this search handler.
    * Having clauses should refer to the main table using the alias 'main'
    * e.g. $handler->addHaving("main.active = 1")
    **/
    public function addHaving($val)
    {
        $this->having[] = $val;
    }

}


