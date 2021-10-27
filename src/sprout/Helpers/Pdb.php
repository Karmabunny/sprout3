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
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;

use Kohana;

use Sprout\Exceptions\ConstraintQueryException;
use Sprout\Exceptions\QueryException;
use Sprout\Exceptions\RowMissingException;
use Sprout\Exceptions\TransactionRecursionException;


/**
 * Class for doing database queries via PDO (PDO Database => Pdb)
 */
class Pdb
{
    protected static $prefix = 'sprout_';
    protected static $table_prefixes = [];
    protected static $ro_connection = null;
    protected static $rw_connection = null;
    protected static $override_connection = null;
    protected static $log_func = null;
    protected static $formatters = [];
    protected static $in_transaction = false;
    protected static $last_insert_id;


    /**
     * Set a logging handler function
     *
     * This function will be called after each query
     * to allow calling code to do extra debugging or logging
     *
     * The function signature should be:
     *    function(string $query, array $params, PDOStatement|QueryException $result)
     *
     * @param callable $func The logging function to use
     */
    public static function setLogHandler(callable $func)
    {
        self::$log_func = $func;
    }


    /**
     * Clear the log handler
     */
    public static function clearLogHandler()
    {
        self::$log_func = null;
    }


    /**
     * Set the formatter function for a given class.
     *
     * Formatter functions are called when an object is passed to Pdb
     * as a bind parameter or in other ways.
     *
     * The function signature should be:
     *    function(mixed $val) : string
     *
     * @param string $class_name The class to attach the formatter to
     * @param callable $func The formatter function to use
     */
    public static function setFormatter($class_name, callable $func)
    {
        self::$formatters[$class_name] = $func;
    }


    /**
     * Remove the formatter function for a given class.
     *
     * @param string $class_name The class to remove the formatter from
     */
    public static function removeFormatter($class_name)
    {
        unset(self::$formatters[$class_name]);
    }


    /**
     * Format a object in accordance to the registered formatters.
     *
     * Formatters convert objects to saveable types, such as a string or an integer
     *
     * @param mixed $value The value to format
     * @return string The formatted value
     */
    public static function format($value)
    {
        if (!is_object($value)) return $value;

        $class_name = get_class($value);
        if (!isset(self::$formatters[$class_name])) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
            throw new \InvalidArgumentException("Unable to format objects of type '{$class_name}'");
        }

        $func = self::$formatters[$class_name];
        $value = $func($value);
        if (!is_string($value) and !is_int($value)) {
            throw new \InvalidArgumentException("Formatter for type '{$class_name}' must return a string or int");
        }

        return $value;
    }


    /**
     * Gets the prefix to prepend to table names
     */
    public static function prefix()
    {
        return self::$prefix;
    }


    /**
     * Sets the prefix to prepend to table names.
     * Only use this to override the existing prefix; e.g. in the Preview helper
     * @param string $prefix The overriding prefix
     */
    public static function setPrefix($prefix)
    {
        self::$prefix = $prefix;
    }


    /**
     * Sets an overriding prefix to prepend to an individual table
     * This supports the Preview helper, which creates temporary copies of one or more tables
     * @param string $table The table to use a specific prefix for, e.g. 'pages'
     * @param string $prefix The prefix to use, e.g. 'temp_only_'
     */
    public static function setTablePrefixOverride($table, $prefix)
    {
        self::$table_prefixes[$table] = $prefix;
    }


    /**
     * Replaces tilde placeholders with table prefixes, and quotes tables according to the rules of the underlying DBMS
     * @param PDO $pdo The database connection, for determining table quoting rules
     * @param string $query Query which contains tilde placeholders, e.g. 'SELECT * FROM ~pages WHERE id = 1'
     * @return string Query with tildes replaced by prefixes, e.g. 'SELECT * FROM `sprout_pages` WHERE id = 1'
     */
    public static function insertPrefixes(PDO $pdo, $query)
    {
        $lquote = $rquote = '';
        switch ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)) {
        case 'mysql':
            $lquote = $rquote = '`';
            break;
        }

        $replacer = function(array $matches) use ($lquote, $rquote) {
            if (isset(self::$table_prefixes[$matches[1]])) {
                return $lquote . self::$table_prefixes[$matches[1]] . $matches[1] . $rquote;
            } else {
                return $lquote . self::prefix() . $matches[1] . $rquote;
            }
        };
        return preg_replace_callback('/\~([\w0-9_]+)/', $replacer, $query);
    }


    /**
     * Bind the array of parameters to a PDO statement
     *
     * Unlike PDOStatement::execute which binds everything as PARAM_STR,
     * this method will bind integers as PARAM_INT
     *
     * @param PDOStatement $st Statement to bind parameters to
     * @param array $params Parameters to bind
     */
    protected static function bindParams(PDOStatement $st, array $params)
    {
        foreach ($params as $key => $val) {
            // Numeric (question mark) params require 1-based indexing
            if (!is_string($key)) {
                $key += 1;
            }

            if (is_int($val)) {
                $st->bindValue($key, $val, PDO::PARAM_INT);
            } else {
                $st->bindValue($key, $val, PDO::PARAM_STR);
            }
        }
    }


    /**
     * Loads a Kohana DB config and creates a new PDO connection with it
     *
     * Note - You probably want {@see Pdb::getConnection} instead as it uses
     * the same connection across multiple calls.
     *
     * @param string $config Name of the database config key, e.g. 'default'
     * @return PDO
     */
    public static function connect($config)
    {
        $conf = Kohana::config('database.' . $config);
        $type = $conf['connection']['type'];
        $type = str_replace('mysqli', 'mysql', $type);
        $host = $conf['connection']['host'];
        $user = $conf['connection']['user'];
        $pass = $conf['connection']['pass'];
        $db = $conf['connection']['database'];
        $charset = $conf['character_set'];

        $dsn = "{$type}:host={$host};dbname={$db};charset={$charset}";
        if ($port = @$conf['connection']['port']) {
            $dsn .= ";port={$port}";
        }
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->query("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");
        return $pdo;
    }


    /**
     * Set a PDO connection to use instead of the internal connection logic
     * The specified connection may even be for a different database
     *
     * NOTE: The flag indicating if the driver is currently in a transaction
     * ({@see inTransaction}) does not get changed by calls to this function,
     * so it may not produce correct results in that case.
     *
     * @example
     * $mssql = new PDO('obdc:ms-sql-server');
     * Pdb::setOverrideConnection($mssql);
     * $res = Pdb::query('SELECT TOP 3 FROM [my table]', [], 'row');
     *
     * @param PDO $connection Any PDO connection.
     */
    public static function setOverrideConnection(PDO $connection)
    {
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$override_connection = $connection;
    }


    /**
     * Clear any overridden connection, reverting behaviour back to
     * the default connection logic.
     */
    public static function clearOverrideConnection()
    {
        self::$override_connection = null;
    }


    /**
     * Gets a PDO connection, creating a new one if necessary
     *
     * @param string $type 'RW': read-write, or 'RO': read-only. If replication
     *        isn't being used, then only 'RW' is ever necessary.
     * @return PDO
     * @throws PDOException If connection fails
     */
    public static function getConnection($type = 'RW')
    {
        if (self::$override_connection !== null) {
            return self::$override_connection;
        }

        if (strcasecmp($type, 'RO') == 0) {
            if (!self::$ro_connection) {
                self::$ro_connection = self::connect('read_only');
            }
            return self::$ro_connection;
        }

        if (!self::$rw_connection) {
            self::$rw_connection = self::connect('default');
        }
        return self::$rw_connection;
    }


    /**
     * Strips string elements from a query, e.g. 'hey', "yeah", and 'it\'s nice'.
     * This function is used to support {@see Pdb::getBindSubset}
     * @param string $q The query
     * @return string The modified query
     */
    protected static function stripStrings($q)
    {
        $q = preg_replace('/\'(?:\\\\\'|[^\'\\\\])*\'/', '', $q);
        $q = preg_replace('/"(?:\\\\"|[^"\\\\])*"/', '', $q);
        return $q;
    }


    /**
     * Gets the subset of bind params which are associated with a particular query from a generic list of bind params.
     * This is used to support the SQL DB tool.
     * N.B. This probably won't work if you mix named and numbered params in the same query.
     * @param string $q
     * @param array $binds generic list of binds
     * @return array
     */
    public static function getBindSubset($q, $binds)
    {
        $q = self::stripStrings($q);

        // Strip named params which aren't required
        // N.B. identifier format matches self::validateIdentifier
        $params = [];
        preg_match_all('/:[a-z_][a-z_0-9]*/i', $q, $params);
        $params = $params[0];
        foreach ($binds as $key => $val) {
            if (is_int($key)) continue;

            if (count($params) == 0) {
                unset($binds[$key]);
                continue;
            }

            $required = false;
            foreach ($params as $param) {
                if ($key[0] == ':') {
                    if ($param[0] != ':') {
                        $param = ':' . $param;
                    }
                } else {
                    $param = ltrim($param, ':');
                }
                if ($key == $param) {
                    $required = true;
                }
            }
            if (!$required) {
                unset($binds[$key]);
            }
        }

        // Strip numbered params which aren't required
        $params = [];
        preg_match_all('/\?/', $q, $params);
        $params = $params[0];
        if (count($params) == 0) {
            foreach ($binds as $key => $bind) {
                if (is_int($key)) {
                    unset($binds[$key]);
                }
            }
            return $binds;
        }

        foreach ($binds as $key => $val) {
            if (!is_int($key)) unset($binds[$key]);
        }
        while (count($params) < count($binds)) {
            array_pop($binds);
        }

        return $binds;
    }


    /**
     * Create a QueryException from a given PDOException object
     *
     * Uses the SQLSTATE code to return different exception classes, which are subclasses of QueryException
     *
     * @param PDOException $ex
     * @return ConstraintQueryException Integrity constraint violation (SQLSTATE 23xxx)
     * @return QueryException All other query errors
     */
    protected static function createQueryException(PDOException $ex)
    {
        $pdo_ex = $ex;
        $state_class = substr($ex->getCode(), 0, 2);

        switch ($state_class) {
            case '23':
                $ex = new ConstraintQueryException($ex->getMessage());
                break;

            default:
                $ex = new QueryException($ex->getMessage());
                break;
        }

        $ex->state = $pdo_ex->getCode();

        return $ex;
    }


    /**
     * Alias for {@see Pdb::query}
     **/
    public static function q($query, array $params, $return_type)
    {
        return self::query($query, $params, $return_type);
    }


    /**
     * Executes a PDO query
     *
     * For the return type 'pdo', a PDOStatement is returned. You need to close it using $res->closeCursor()
     *     once you're finished.
     * For the return type 'prep', a prepared (but not executed) PDOStatement is returned.
     *     This should be executed using the {@see Pdb::execute} method.
     * For the return type 'null', nothing is returned.
     * For the return type 'count', a count of rows is returned.
     * Additional return types are available; {@see Pdb::formatRs} for a full list.
     *
     * When working with datasets larger than about 50 rows, you may run out of ram when using
     * return types other than 'pdo', 'null', 'count' or 'val' because the other types all return the values as arrays
     *
     * @param string $query The query to execute. Prefix a table name with a tilde (~) to automatically include the
     *        table prefix, e.g. ~pages will be converted to sprout_pages
     * @param array $params Parameters to bind to the query
     * @param string $return_type 'pdo', 'prep', 'count', 'null', or a format type {@see Pdb::formatRs}
     * @return PDOStatement|array|int|string|null
     *  - `PDOStatement` for type 'pdo' and 'prep'
     *  - `int` for type 'count'
     *  - `null` for type 'null'
     *  - `array` or `string` for all other types; see {@see Pdb::formatRs}
     * @throws InvalidArgumentException If $query isn't a string
     * @throws InvalidArgumentException If the return type isn't valid
     * @throws QueryException If the query execution or formatting failed
     */
    public static function query($query, array $params, $return_type)
    {
        if (!is_string($query)) {
            if ($query instanceof PDOStatement) {
                $err = '$query must be a string. You must call Pdb::execute on a PDOStatement';
                throw new InvalidArgumentException($err);
            }
            throw new InvalidArgumentException('$query must be a string');
        }

        static $types = [
            'pdo', 'prep', 'null', 'count', 'arr', 'arr-num', 'row', 'row-num', 'map', 'map-arr', 'val', 'col'
        ];

        $return_type = strtolower($return_type);
        if (!in_array($return_type, $types)) {
            $err = 'Invalid $return_type; see documentation';
            throw new InvalidArgumentException($err);
        }

        if ($return_type === 'prep' and count($params) !== 0) {
            $err = 'You cannot provide parameters when preparing statements';
            throw new InvalidArgumentException($err);
        }

        if (preg_match('/^[(\s]*(?:EXPLAIN|SELECT|SHOW|DESC|HELP)/i', $query) and !self::$in_transaction) {
            if (!empty(Kohana::config('database.read_only'))) {
                $pdo = self::getConnection('RO');
            } else {
                $pdo = self::getConnection('RW');
            }
        } else {
            $pdo = self::getConnection('RW');
        }

        $query = self::insertPrefixes($pdo, $query);

        // Format objects into strings
        foreach ($params as &$p) {
            $p = self::format($p);
        }
        unset($p);

        $ex = null;
        try {
            $st = $pdo->prepare($query);
            if ($return_type === 'prep') {
                return $st;
            }
            static::bindParams($st, $params);
            $st->execute();
            $res = $st;

            // Save the insert ID specifically, so it doesn't get clobbered by IDs generated by logging
            if (stripos($query, 'INSERT') === 0) {
                self::$last_insert_id = $pdo->lastInsertId();
            }

        } catch (PDOException $ex) {
            $ex = self::createQueryException($ex);
            $ex->query = $query;
            $ex->params = $params;
        }

        // The log method needs to be disabled so log functions can
        // make queries without logging themselves
        if (self::$log_func != null) {
            $func = self::$log_func;
            self::$log_func = null;
            if ($ex) {
                $func($query, $params, $ex);
            } else {
                $func($query, $params, $res);
            }
            self::$log_func = $func;
        }

        // This is thrown after logging
        if ($ex) {
            throw $ex;
        }

        if ($return_type == 'pdo') {
            $res->setFetchMode(PDO::FETCH_ASSOC);
            return $res;
        } else if ($return_type == 'null') {
            $res->closeCursor();
            return null;
        } else if ($return_type == 'count') {
            $count = $res->rowCount();
            $res->closeCursor();
            return $count;
        }

        try {
            $ret = self::formatRs($res, $return_type);
        } catch (RowMissingException $ex) {
            $res->closeCursor();

            $ex->query = $query;
            $ex->params = $params;
            throw $ex;
        }
        $res->closeCursor();
        $res = null;
        return $ret;
    }


    /**
     * Prepare a query into a prepared statement
     * This is just a wrapper for {@see Pdb::query} with the return type of 'prep'.
     *
     * @param string $query The query to execute. Prefix a table name with a tilde (~) to automatically include the
     *        table prefix, e.g. ~pages will be converted to sprout_pages
     * @return PDOStatement The prepared statement, for execution with {@see Pdb::execute}
     * @throws QueryException If the query execution or formatting failed
     */
    public static function prepare($query) {
        return self::query($query, [], 'prep');
    }


    /**
     * Executes a prepared statement
     *
     * For the return type 'pdo', a PDOStatement is returned. You need to close it using $res->closeCursor()
     *     once you're finished.
     * For the return type 'null', nothing is returned.
     * For the return type 'count', a count of rows is returned.
     * Additional return types are available; {@see Pdb::formatRs} for a full list.
     *
     * When working with datasets larger than about 50 rows, you may run out of ram when using
     * return types other than 'pdo', 'null', 'count' or 'val' because the other types all return the values as arrays
     *
     * @param PDOStatement $st The query to execute. Prepare using {@see Pdb::query} and the return type 'prep'.
     * @param array $params Parameters to bind to the query
     * @param string $return_type 'pdo', 'prep', 'count', 'null', or a format type {@see Pdb::formatRs}
     * @return PDOStatement For type 'pdo'
     * @return int For type 'count'
     * @return null For type 'null'
     * @return mixed For all other types; see {@see Pdb::formatRs}
     * @throws InvalidArgumentException If the return type isn't valid
     * @throws QueryException If the query execution or formatting failed
     */
    public static function execute(PDOStatement $st, array $params, $return_type)
    {
        static $types = [
            'pdo', 'null', 'count', 'arr', 'arr-num', 'row', 'row-num', 'map', 'map-arr', 'val', 'col'
        ];

        $return_type = strtolower($return_type);
        if (!in_array($return_type, $types)) {
            $err = 'Invalid $return_type; see documentation';
            throw new InvalidArgumentException($err);
        }

        // Format objects into strings
        foreach ($params as &$p) {
            $p = self::format($p);
        }
        unset($p);

        $ex = null;
        try {
            static::bindParams($st, $params);
            $st->execute();
            $res = $st;
            $count = $res->rowCount();

            // Save the insert ID specifically, so it doesn't get clobbered by IDs generated by logging
            if (stripos($st->queryString, 'INSERT') === 0) {
                $pdo = self::getConnection('RW');
                self::$last_insert_id = $pdo->lastInsertId();
            }

        } catch (PDOException $ex) {
            $ex = self::createQueryException($ex);
            $ex->params = $params;
        }

        // This is thrown after logging
        if ($ex) {
            throw $ex;
        }

        if ($return_type == 'pdo') {
            $res->setFetchMode(PDO::FETCH_ASSOC);
            return $res;
        } else if ($return_type == 'null') {
            $res->closeCursor();
            return null;
        } else if ($return_type == 'count') {
            $count = $res->rowCount();
            $res->closeCursor();
            return $count;
        }

        try {
            $ret = self::formatRs($res, $return_type);
        } catch (RowMissingException $ex) {
            $res->closeCursor();

            $ex->params = $params;
            throw $ex;
        }
        $res->closeCursor();
        $res = null;
        return $ret;
    }


    /**
     * Converts a PDO result set to a common data format:
     *
     * arr      An array of rows, where each row is an associative array.
     *          Use only for very small result sets, e.g. <= 20 rows.
     *
     * arr-num  An array of rows, where each row is a numeric array.
     *          Use only for very small result sets, e.g. <= 20 rows.
     *
     * row      A single row, as an associative array
     *
     * row-num  A single row, as a numeric array
     *
     * map      An array of identifier => value pairs, where the
     *          identifier is the first column in the result set, and the
     *          value is the second
     *
     * map-arr  An array of identifier => value pairs, where the
     *          identifier is the first column in the result set, and the
     *          value an associative array of name => value pairs
     *          (if there are multiple subsequent columns)
     *
     * val      A single value (i.e. the value of the first column of the
     *          first row)
     *
     * col      All values from the first column, as a numeric array.
     *          DO NOT USE with boolean columns; see note at
     *          http://php.net/manual/en/pdostatement.fetchcolumn.php
     *
     * @param string $type One of 'arr', 'arr-num', 'row', 'row-num', 'map', 'map-arr', 'val' or 'col'
     * @return array For most types
     * @return string For 'val'
     * @throws RowMissingException If the result set didn't contain the required row
     */
    public static function formatRs(PDOStatement $rs, $type)
    {
        switch ($type) {
        case 'arr':
            return $rs->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'arr-num':
            return $rs->fetchAll(PDO::FETCH_NUM);
            break;

        case 'row':
            $row = $rs->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new RowMissingException('Expected a row');
            return $row;
            break;

        case 'row-num':
            $row = $rs->fetch(PDO::FETCH_NUM);
            if (!$row) throw new RowMissingException('Expected a row');
            return $row;
            break;

        case 'map':
            if ($rs->columnCount() < 2) {
                throw new Exception('Two columns required');
            }
            $map = array();
            while ($row = $rs->fetch(PDO::FETCH_NUM)) {
                $map[$row[0]] = $row[1];
            }
            return $map;
            break;

        case 'map-arr':
            $map = array();
            while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
                $id = reset($row);
                $map[$id] = $row;
            }
            return $map;
            break;

        case 'val':
            $row = $rs->fetch(PDO::FETCH_NUM);
            if (!$row) throw new RowMissingException('Expected a row');
            return $row[0];
            break;

        case 'col':
            $arr = [];
            while (($col = $rs->fetchColumn(0)) !== false) {
                $arr[] = $col;
            }
            return $arr;
            break;

        default:
            $err = "Unknown return type: {$type}";
            throw new InvalidArgumentException($err);
        }
    }


    /**
     * Validates a identifier (column name, table name, etc)
     * @param string $name The identifier to check
     * @return void
     * @throws InvalidArgumentException If the identifier is invalid
     */
    public static function validateIdentifier($name)
    {
        if (!preg_match('/^[a-z_][a-z_0-9]*$/i', $name)) {
            throw new InvalidArgumentException("Invalid identifier: {$name}");
        }
    }


    /**
     * Validates a identifier in extended format -- table.column
     * Also accepts short format like {@see validateIdentifier} does.
     *
     * @param string $name The identifier to check
     * @return void
     * @throws InvalidArgumentException If the identifier is invalid
     */
    public static function validateIdentifierExtended($name)
    {
        if (!preg_match('/^(?:[a-z_][a-z_0-9]*\.)?[a-z_][a-z_0-9]*$/i', $name)) {
            throw new InvalidArgumentException("Invalid identifier: {$name}");
        }
    }


    /**
     * Runs an INSERT query
     * @param string $table The table (without prefix) to insert the data into
     * @param array $data Data to insert, column => value
     * @return int The id of the newly-inserted record, if applicable
     * @throws InvalidArgumentException
     * @throws QueryException
     */
    public static function insert($table, array $data)
    {
        self::validateIdentifier($table);
        if (count($data) == 0) {
            $err = 'An INSERT must set at least 1 column';
            throw new InvalidArgumentException($err);
        }

        $pf = self::$prefix;
        $q = "INSERT INTO ~{$table}";

        $cols = '';
        $values = '';
        $insert = [];
        foreach ($data as $col => $val) {
            self::validateIdentifier($col);
            if ($cols) $cols .= ', ';
            $cols .= self::quoteField($col);
            if ($values) $values .= ', ';
            $values .= ":{$col}";
            $insert[":{$col}"] = $val;
        }
        $q .= " ({$cols}) VALUES ({$values})";

        self::q($q, $insert, 'count');
        return self::$last_insert_id;
    }


    /**
     * Return the value from the autoincement of the most recent INSERT query
     *
     * @return int The record id
     * @return null If there hasn't been an insert yet
     */
    public static function getLastInsertId()
    {
        return self::$last_insert_id;
    }


    /**
     * Builds a clause string by combining conditions, e.g. for a WHERE or ON clause.
     * The resultant clause will contain ? markers for safe use in a prepared SQL statement.
     * The statement and the generated $values can then be run via {@see Pdb::query}.
     *
     * Each condition (see $conditions) is one of:
     *   - The scalar value 1 or 0 (to match either all or no records)
     *   - A column => value pair
     *   - An array with three elements: [column, operator, value(s)].
     *
     * Conditions are usually combined using AND, but can also be OR or XOR; see the $combine parameter.
     *
     * @param array $conditions
     * Conditions for the clause. Each condition is either:
     * - The scalar value 1 (to match ALL records -- BEWARE if using the clause in an UPDATE or DELETE)
     * - The scalar value 0 (to match no records)
     * - A column => value pair for an '=' condition.
     *   For example: 'id' => 3
     * - An array with three elements: [column, operator, value(s)]
     *   For example:
     *       ['id', '=', 3]
     *       ['date_added', 'BETWEEN', ['2010', '2015']]
     *       ['status', 'IN', ['ACTIVE', 'APPROVE']]
     *   Simple operators:
     *       =  <=  >=  <  >  !=  <>
     *   Operators for LIKE conditions; escaping of characters like % is handled automatically:
     *       CONTAINS  string
     *       BEGINS    string
     *       ENDS      string
     *   Other operators:
     *       IS        string 'NULL' or 'NOT NULL'
     *       BETWEEN   array of 2 values
     *       (NOT) IN  array of values
     *       IN SET    string -- note the order matches other operators; ['column', 'IN SET', 'val1,ca']
     * @param array $values Array of bind parameters. Additional parameters will be appended to this array
     * @param string $combine String to be placed between the conditions. Must be one of: 'AND', 'OR', 'XOR'
     * @return string A clause which is safe to use in a prepared SQL statement
     * @example
     * $conditions = ['active' => 1, ['date_added', 'BETWEEN', ['2015-01-01', '2016-01-01']]];
     * $params = [];
     * $where = Pdb::buildClause($conditions, $params);
     * //
     * // Variable contents:
     * // $where == "active = ? AND date_added BETWEEN ? AND ?"
     * // $params == [1, '2015-01-01', '2016-01-01'];
     * //
     * $q = "SELECT * FROM ~my_table WHERE {$where}";
     * $res = Pdb::query($q, $params, 'pdo');
     * foreach ($res as $row) {
     *     // Record processing here
     * }
     * $res->closeCursor();
     */
    public static function buildClause(array $conditions, array &$values, $combine = 'AND')
    {
        if ($combine != 'AND' and $combine != 'OR' and $combine != 'XOR') {
            throw new InvalidArgumentException('Combine paramater must be of of: "AND", "OR", "XOR"');
        }
        $combine = " {$combine} ";

        $where = '';
        foreach ($conditions as $key => $cond) {
            if ($where) $where .= $combine;
            if (is_scalar($cond) or is_null($cond)) {
                if (preg_match('/^[0-9]+$/', $key)) {
                    $cond = (string) $cond;
                    if ($cond != '1' and $cond != '0') {
                        $err = '1 and 0 are the only accepted scalar conditions';
                        throw new InvalidArgumentException($err);
                    }
                    $where .= $cond;
                } else {
                    self::validateIdentifierExtended($key);
                    if (is_null($cond)) {
                        $where .= sprintf('%s IS NULL', self::quoteField($key));
                    } else {
                        $where .= sprintf('%s = ?', self::quoteField($key));
                        $values[] = $cond;
                    }
                }
                continue;
            }

            if (!is_array($cond)) {
                throw new InvalidArgumentException('Condition must be scalar, array, or null - not ' . gettype($cond));
            }

            if (count($cond) != 3) {
                $err = 'An array condition needs exactly 3 elements: ';
                $err .= 'column, operator, value(s); ' . count($cond) . ' provided';
                throw new InvalidArgumentException($err);
            }
            list($col, $op, $val) = $cond;
            self::validateIdentifierExtended($col);

            switch ($op) {
            case '=':
            case '<=':
            case '>=':
            case '<':
            case '>':
            case '!=':
            case '<>':
                if (!is_scalar($val)) {
                    $err = "Operator {$op} needs a scalar value";
                    throw new InvalidArgumentException($err);
                }
                $where .= sprintf('%s %s ?', self::quoteField($col), $op);
                $values[] = $val;
                break;

            case 'IS':
                if ($val === null) $val = 'NULL';
                if ($val == 'NULL' or $val == 'NOT NULL') {
                    $where .= sprintf('%s %s %s', self::quoteField($col), $op, $val);
                } else {
                    $err = "Operator IS value must be NULL or NOT NULL";
                    throw new InvalidArgumentException($err);
                }
                break;

            case 'BETWEEN':
                $err = "Operator BETWEEN value must be an array of two scalars";
                if (!is_array($val)) {
                    throw new InvalidArgumentException($err);
                } else if (count($val) != 2 or !is_scalar($val[0]) or !is_scalar($val[1])) {
                    throw new InvalidArgumentException($err);
                }
                $where .= sprintf('%s BETWEEN ? AND ?', self::quoteField($col));
                $values[] = $val[0];
                $values[] = $val[1];
                break;

            case 'IN':
            case 'NOT IN';
                $err = "Operator {$op} value must be an array of scalars";
                if (!is_array($val)) {
                    throw new InvalidArgumentException($err);
                } else {
                       foreach ($val as $idx => $v) {
                        if (!is_scalar($v)) {
                            throw new InvalidArgumentException($err . " (index {$idx})");
                        }
                    }
                }
                $where .= sprintf('%s %s (%s)', self::quoteField($col), $op, rtrim(str_repeat('?, ', count($val)), ', '));
                foreach ($val as $v) {
                    $values[] = $v;
                }
                break;

            case 'CONTAINS':
                $where .= sprintf('%s LIKE CONCAT("%%", ?, "%%")', self::quoteField($col));
                $values[] = Pdb::likeEscape($val);
                break;

            case 'BEGINS':
                $where .= sprintf('%s LIKE CONCAT(?, "%%")', self::quoteField($col));
                $values[] = Pdb::likeEscape($val);
                break;

            case 'ENDS':
                $where .= sprintf('%s LIKE CONCAT("%%", ?)', self::quoteField($col));
                $values[] = Pdb::likeEscape($val);
                break;

            case 'IN SET':
                $where .= sprintf('FIND_IN_SET(?, %s) > 0', self::quoteField($col));
                $values[] = Pdb::likeEscape($val);
                break;

            default:
                $err = 'Operator not implemented: ' . $op;
                throw new InvalidArgumentException($err);
            }
        }
        return $where;
    }


    /**
     * Runs an UPDATE query
     * @param string $table The table (without prefix) to insert the data into
     * @param array $data Data to update, column => value
     * @param array $conditions Conditions for updates. {@see Pdb::buildClause}
     * @return int The number of affected rows
     * @throws InvalidArgumentException
     * @throws QueryException
     */
    public static function update($table, array $data, array $conditions)
    {
        self::validateIdentifier($table);
        if (count($data) == 0) {
            $err = 'An UPDATE must apply to at least 1 column';
            throw new InvalidArgumentException($err);
        }
        if (count($conditions) == 0) {
            $err = 'An UPDATE requires at least 1 condition';
            throw new InvalidArgumentException($err);
        }

        $pf = self::$prefix;
        $q = "UPDATE ~{$table} SET ";

        $cols = '';
        $values = [];
        foreach ($data as $col => $val) {
            self::validateIdentifier($col);
            if ($cols) $cols .= ', ';
            $cols .= sprintf('%s = ?', self::quoteField($col));
            $values[] = $val;
        }
        $q .= $cols;

        $q .= " WHERE " . self::buildClause($conditions, $values);

        return self::query($q, $values, 'count');
    }


    /**
     * Runs a DELETE query
     * @param string $table The table (without prefix) to insert the data into
     * @param array $conditions Conditions for updates. {@see Pdb::buildClause}
     * @return int The number of affected rows
     * @throws InvalidArgumentException
     * @throws QueryException
     */
    public static function delete($table, array $conditions)
    {
        self::validateIdentifier($table);
        if (count($conditions) == 0) {
            $err = 'A DELETE requires at least 1 condition';
            throw new InvalidArgumentException($err);
        }

        $values = [];
        $q = "DELETE FROM ~{$table} WHERE " . self::buildClause($conditions, $values);
        return self::q($q, $values, 'count');
    }


    /**
     * Checks if there's a current transaction in progress
     * @return bool True if inside a transaction
     */
    public static function inTransaction()
    {
        return self::$in_transaction;
    }


    /**
     * Starts a transaction
     * @return void
     * @throws TransactionRecursionException if already in a transaction
     */
    public static function transact()
    {
        if (self::$in_transaction) {
            throw new TransactionRecursionException();
        }

        // Always use the RW connection, because it makes no sense to run a
        // transaction which doesn't do any writes
        $pdo = self::getConnection('RW');
        $pdo->beginTransaction();
        self::$in_transaction = true;
    }


    /**
     * Commits a transaction
     * @return void
     */
    public static function commit()
    {
        $pdo = self::getConnection('RW');
        $pdo->commit();
        self::$in_transaction = false;
    }


    /**
     * Rolls a transaction back
     * @return void
     */
    public static function rollback()
    {
        $pdo = self::getConnection('RW');
        $pdo->rollBack();
        self::$in_transaction = false;
    }


    /**
     * Gets a datetime value for the current time.
     * This is used to implement MySQL's NOW() function in PHP, but may change
     * if the decision is made to use INT columns instead of DATETIMEs. This
     * will probably happen at some point, so this function should only be used
     * for generating values right before an INSERT or UPDATE query is run
     * @return string
     */
    public static function now()
    {
        return date('Y-m-d H:i:s');
    }


    /**
     * Escapes the special characters % and _ for use in a LIKE clause
     * @param string $str
     * @return string
     */
    public static function likeEscape($str)
    {
        return str_replace(['_', '%'], ['\\_', '\\%'], $str);
    }


    /**
     * Fetches a mapping of id => value values from a table, using the 'name' values by default
     *
     * @param string $table The table name, without prefix
     * @param array $conditions Optional where clause {@see Pdb::buildClause}
     * @param array $order Optional columns to ORDER BY. Defaults to 'name'
     * @param string $name The field to use for the mapped values
     * @return array A lookup table
     **/
    public static function lookup($table, array $conditions = [], array $order = ['name'], $name = 'name')
    {
        self::validateIdentifier($table);
        foreach ($order as $ord) {
            self::validateIdentifier($ord);
        }
        self::validateIdentifier($name);

        $values = [];
        $q = sprintf('SELECT id, %s FROM ~%s', self::quoteField($name), $table);
        if (count($conditions)) $q .= "\nWHERE " . self::buildClause($conditions, $values);
        if (count($order)) $q .= "\nORDER BY " . implode(', ', $order);
        return self::q($q, $values, 'map');
    }


    /**
     * Return all columns for a single row of a table.
     * The row is specified using its id.
     *
     * @param string $table The table name, not prefixed
     * @param int $id The id of the record to fetch
     * @return array The record data
     * @throws QueryException If the query fails
     * @throws RowMissingException If there's no row
     */
    public static function get($table, $id)
    {
        self::validateIdentifier($table);

        $q = "SELECT * FROM ~{$table} WHERE id = ?";
        return Pdb::q($q, [(int) $id], 'row');
    }


    /**
     * Check to see that at least one record exists for certain conditions.
     *
     * @param string $table The table name, not prefixed
     * @param array $conditions Conditions for the WHERE clause, formatted as per {@see Pdb::buildClause}
     * @return bool True if a matching record exists
     * @throws QueryException If the query fails
     * @example if (!Pdb::recordExists('users', ['id' => 123, 'active' => 1])) {
     *     // ...
     * }
     */
    public static function recordExists($table, array $conditions)
    {
        self::validateIdentifier($table);

        $params = [];
        $clause = Pdb::buildClause($conditions, $params);
        $q = "SELECT 1
            FROM ~{$table}
            WHERE {$clause}
            LIMIT 1";
        try {
            self::query($q, $params, 'row');
        } catch (RowMissingException $ex) {
            return false;
        }
        return true;
    }


    /**
     * Returns definition list from column of type ENUM
     * @param string $table The DB table name, without prefix
     * @param string $column The column name
     * @return array
     */
    public static function extractEnumArr($table, $column)
    {
        Pdb::validateIdentifier($table);
        Pdb::validateIdentifier($column);

        $q = "SHOW COLUMNS FROM ~{$table} LIKE ?";
        $res = Pdb::q($q, [$column], 'row');

        $arr = self::convertEnumArr($res['Type']);
        return array_combine($arr, $arr);
    }


    /**
     * Convert an ENUM or SET definition from MySQL into an array of values
     *
     * @param string $enum_defn The definition from MySQL, e.g. ENUM('aa','bb','cc')
     * @return array Numerically indexed
     */
    public static function convertEnumArr($enum_defn)
    {
        $pattern = '/^(?:ENUM|SET)\s*\(\s*\'/i';
        if (!preg_match($pattern, $enum_defn)) {
            throw new InvalidArgumentException("Definition is not an ENUM or SET");
        }

        // Remove enclosing ENUM('...') or SET('...')
        $enum_defn = preg_replace($pattern, '', $enum_defn);
        $enum_defn = preg_replace('/\'\s*\)\s*$/', '', $enum_defn);

        // SQL escapes ' characters with ''
        // So split on all ',' which aren't followed by a ' character
        $vals = preg_split("/','(?!')/", $enum_defn);

        // Then convert any '' characters back into ' characters
        foreach ($vals as &$v) {
            $v = str_replace("''", "'", $v);
        }

        return $vals;
    }


    /**
     * Validates a value meant for an ENUM field, e.g.
     * $valid->addRules('col1', 'required', 'Pdb::validateEnum[table, col]');
     * @param string $val The value to find in the ENUM
     * @param array $field [0] Table name [1] Column name
     * @return bool
     */
    public static function validateEnum($val, $field)
    {
        list($table, $col) = $field;
        $enum = self::extractEnumArr($table, $col);
        if (count($enum) == 0) return false;
        if (in_array($val, $enum)) return true;
        return false;
    }


    /**
     * Gets all of the dependent foreign key columns (i.e. with the CASCADE delete rule) in other tables
     * which link to the id column of a specific table
     * @param string $table The table which contains the id column which the foreign key columns link to
     * @return array Each element is an array: ['table' => table_name, 'column' => column_name]
     */
    public static function getDependentKeys($table)
    {
        $params = [Kohana::config('database.default.connection.database')];
        $params[] = self::$prefix . $table;

        $q = "SELECT K.TABLE_NAME, K.COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K
            INNER JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS AS C
                ON K.CONSTRAINT_NAME = C.CONSTRAINT_NAME
                AND K.TABLE_SCHEMA = C.CONSTRAINT_SCHEMA
                AND C.DELETE_RULE = 'CASCADE'
            WHERE K.TABLE_SCHEMA = ?
                AND K.CONSTRAINT_NAME != ''
                AND K.REFERENCED_TABLE_NAME = ?
                AND K.REFERENCED_COLUMN_NAME = 'id'
            ORDER BY K.TABLE_NAME, K.COLUMN_NAME";
        $res = self::query($q, $params, 'pdo');

        $rows = [];
        $pattern = '/^' . preg_quote(self::$prefix, '/') . '/';
        while ($row = $res->fetch(PDO::FETCH_NUM)) {
            $rows[] = [
                'table' => preg_replace($pattern, '', $row[0]),
                'column' => $row[1]
            ];
        }
        $res->closeCursor();
        return $rows;
    }


    /**
     * Gets all of the columns which have foreign key constraints in a table
     * @param string $table The table to check for columns which link to other tables
     * @return array Each element is an array with elements as follows:
     *         source_column => name of column in the specified table
     *         target_table => name of table that the source column links to
     *         target_column => name of column that the source column links to
     * @example
     * $fks = Pdb::getForeignKeys('files_cat_join');
     * // $fk has value: [
     * //     ['source_column' => 'cat_id', 'target_table' => 'files_cat_list', 'target_column' => 'id']
     * //     ['source_column' => 'file_id', 'target_table' => 'files', 'target_column' => 'id'],
     * // ];
     */
    public static function getForeignKeys($table)
    {
        $params = [Kohana::config('database.default.connection.database')];
        $params[] = self::$prefix . $table;

        $q = "SELECT K.COLUMN_NAME, K.REFERENCED_TABLE_NAME, K.REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K
            INNER JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS AS C
                ON K.CONSTRAINT_NAME = C.CONSTRAINT_NAME
                AND K.TABLE_SCHEMA = C.CONSTRAINT_SCHEMA
            WHERE K.TABLE_SCHEMA = ?
                AND K.CONSTRAINT_NAME != ''
                AND K.TABLE_NAME = ?
            ORDER BY K.TABLE_NAME, K.COLUMN_NAME";
        $res = self::query($q, $params, 'pdo');

        $rows = [];
        $pattern = '/^' . preg_quote(self::$prefix, '/') . '/';
        while ($row = $res->fetch(PDO::FETCH_NUM)) {
            $rows[] = [
                'source_column' => $row[0],
                'target_table' => preg_replace($pattern, '', $row[1]),
                'target_column' => $row[2],
            ];
        }
        $res->closeCursor();
        return $rows;
    }


    /**
     * Makes a query have pretty indentation.
     *
     * Typically a query is written as a multiline string embedded within PHP code, and when printed as-is, it looks
     * horrible as the PHP indentation is basically incorporated into the SQL.
     *
     * N.B. All tabs are converted to 4 spaces each
     *
     * @param string $query
     * @return string
     */
    public static function prettyQueryIndentation($query)
    {
        $lines = explode("\n", $query);
        $lowest_indent = 10000;

        foreach ($lines as $num => &$line) {
            if ($num == 0) continue;

            $line = str_replace("\t", '    ', $line);

            $matches = [];
            preg_match('/^ +/', $line, $matches);
            $lowest_indent = min($lowest_indent, strlen(@$matches[0]));
        }

        if ($lowest_indent == 0) return implode("\n", $lines);

        $pattern = '/^' . str_repeat(' ', $lowest_indent) . '/';
        foreach ($lines as $num => &$line) {
            if ($num == 0) continue;

            $line = preg_replace($pattern, '', $line);
        }
        return implode("\n", $lines);
    }


    /**
     * Escape field names
     *
     * @param string $field
     * @return string
     */
    public static function quoteField($field)
    {
        // Integer-ish fields are ok
        if (is_numeric($field) and (int) $field == (float) $field) {
            return $field;
        }

        $pdo = self::getConnection();

        $lquote = $rquote = '';
        switch ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            case 'mysql':
                $lquote = $rquote = '`';
                break;

            case 'mssql':
                $lquote = '[';
                $rquote = ']';
                break;

            case 'sqlite':
            case 'pgsql':
            case 'oracle':
            default:
                $lquote = $rquote = '"';
                break;
        }

        $field = str_replace([$lquote, $rquote], '', $field);
        $parts = explode('.', $field, 2);

        foreach ($parts as &$part) {
            $part = sprintf('%s%s%s', $lquote, trim($part, '\'"[]`'), $rquote);
        }
        unset($part);

        return implode('.', $parts);
    }

}
