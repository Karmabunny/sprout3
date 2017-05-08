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
namespace Sprout\Exceptions;

use Exception;


/**
 * Exception thrown when a database query fails, or gives an empty result set
 * for a query which required a row to be returned
 */
class QueryException extends Exception
{
    public $query;
    public $params;

    /**
     * The SQLSTATE error code associated with the failed query.
     * See e.g.:
     * https://en.wikibooks.org/wiki/Structured_Query_Language/SQLSTATE
     * https://dev.mysql.com/doc/refman/5.7/en/error-messages-server.html
     * https://msdn.microsoft.com/en-us/library/ms714687.aspx
     */
    public $state;

}
