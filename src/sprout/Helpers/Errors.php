<?php
/**
 * Copyright (C) 2026 Karmabunny Pty Ltd.
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

use ErrorException;
use karmabunny\interfaces\HttpExceptionInterface;
use karmabunny\kb\Events;
use karmabunny\kb\HttpStatus;
use karmabunny\pdb\Exceptions\QueryException;
use karmabunny\pdb\Exceptions\RowMissingException;
use karmabunny\pdb\Pdb as PdbPdb;
use Kohana;
use Kohana_404_Exception;
use Kohana_Exception;
use PDOStatement;
use ReflectionClass;
use Sprout\App;
use Sprout\Events\ErrorEvent;
use Sprout\Events\ShutdownEvent;
use Sprout\Exceptions\HttpException;
use Sprout\Helpers\Pdb as SproutPdb;
use Throwable;
use Twig\Error\Error as TwigError;
use Twig\Error\RuntimeError as TwigRuntimeError;

/**
 * Error handling and logging.
 *
 * @package Sprout\Helpers
 */
class Errors
{

    public static bool $ENABLE_FATAL_ERRORS = true;


    /**
    * Gets a simplified backtrace with fewer elements and no recursion
    * @param array $trace If empty, the trace is automatically determined
    * @return array
    */
    public static function simpleBacktrace(array $trace = [])
    {
        if (count($trace) == 0) {
            // This is safe because we strip the first two frames.
            // phpcs:ignore
            $trace = debug_backtrace();

            // Remove this and its caller
            array_shift($trace);
            array_shift($trace);
        }

        $simple_trace = [];
        foreach ($trace as $call) {
            $simple_call = [];
            if (isset($call['file'])) {
                $file = $call['file'];
                if (IN_PRODUCTION or @$_SERVER['SERVER_ADDR'] != @$_SERVER['REMOTE_ADDR']) {
                    if (substr($file, 0, strlen(DOCROOT)) == DOCROOT) {
                        $file = substr($file, strlen(DOCROOT));
                    }
                }
                $simple_call['file'] = $file;
            }
            if (isset($call['line'])) {
                $simple_call['line'] = $call['line'];
            }
            if (!empty($call['function'])) {
                $call_func = $call['function'];
                if (isset($call['class'])) {
                    $class = $call['class'];
                    if (isset($call['type'])) {
                        $class .= $call['type'];
                    } else {
                        $class .= '-??-';
                    }
                    $call_func = $class . $call_func;
                }
                $simple_call['function'] = $call_func;
            }
            if (!empty($call['args'])) {
                $args = [];
                foreach ($call['args'] as $akey => $aval) {
                    if (is_object($aval)) {
                        $args[$akey] = get_class($aval);
                    } else if (is_array($aval)) {
                        $len = count($aval);
                        $args[$akey] = "array({$len}): " . self::condenseArray($aval);
                    } else {
                        $args[$akey] = self::readableVar($aval);
                    }
                }
                unset($call['args']);
                $simple_call['args'] = $args;
            }
            $simple_trace[] = $simple_call;
        }
        return $simple_trace;
    }


    /**
     * Converts a variable into something human readable
     * @param mixed $var
     * @return string
     */
    public static function readableVar($var)
    {
        if (is_array($var)) return self::condenseArray($var);
        if (is_bool($var)) return $var? 'true': 'false';
        if (is_null($var)) return 'null';
        if (is_int($var) or is_float($var)) return (string) $var;
        if (is_string($var)) {
            return "'" . str_replace("'", "\\'", $var) . "'";
        }
        if (is_resource($var)) return 'resource';
        if (is_object($var)) return get_class($var);
        return 'unknown';
    }


    /**
     * Condenses an array into a string
     */
    public static function condenseArray(array $arr)
    {
        $keys = array_keys($arr);
        $int_keys = true;
        foreach ($keys as $key) {
            if (!is_int($key)) {
                $int_keys = false;
                break;
            }
        }

        $str = '[';
        $arg_num = 0;
        foreach ($arr as $key => $val) {
            if (++$arg_num != 1) $str .= ', ';
            if (!$int_keys) $str .= self::readableVar($key) . ' => ';
            $str .= self::readableVar($val);
        }
        $str .= ']';
        return $str;
    }


    /**
     * Displays nice backtrace information.
     * @see http://php.net/debug_backtrace
     *
     * @param   array   $trace  backtrace generated by an exception or debug_backtrace
     * @return  string
     */
    public static function backtrace($trace)
    {
        if ( ! is_array($trace))
            return '';

        // Final output
        $output = array();

        foreach ($trace as $entry)
        {
            $temp = '<li>';

            if (isset($entry['file'])) {
                if (IN_PRODUCTION or @$_SERVER['SERVER_ADDR'] != @$_SERVER['REMOTE_ADDR']) {
                    $entry['file'] = preg_replace('!^' . preg_quote(DOCROOT) . '!', '', $entry['file']);
                }

                $temp .= sprintf('<tt>%s <strong>[%s]:</strong></tt>', $entry['file'], $entry['line']);
            }

            $temp .= '<pre>';

            if (isset($entry['source'])) {
                $temp .= Enc::html($entry['source']);
                $temp .= '</pre></li>';
                $output[] = $temp;
                continue;
            }

            if (isset($entry['class']))
            {
                // Add class and call type
                $temp .= Enc::html($entry['class'].$entry['type']);
            }

            // Add function
            $temp .= Enc::html($entry['function']) . '( ';

            // Add function args
            if (isset($entry['args']) AND is_array($entry['args']))
            {
                // Separator starts as nothing
                $sep = '';

                while ($arg = array_shift($entry['args']))
                {
                    if (is_string($arg))
                    {
                        $arg = Enc::cleanfunky($arg);

                        // Remove docroot from filename
                        if (is_file($arg))
                        {
                            $arg = preg_replace('!^'.preg_quote(DOCROOT).'!', '', $arg);
                        }
                    }

                    $temp .= $sep.'<span>'.Enc::html(print_r($arg, TRUE)).'</span>';

                    // Change separator to a comma
                    $sep = ', ';
                }
            }

            $temp .= ' )</pre></li>';

            $output[] = $temp;
        }

        return '<ul class="backtrace">'.implode("\n", $output).'</ul>';
    }


    /**
     * Converts PHP errors into {@see ErrorException}s
     *
     * @param int $errno Error code
     * @param string $errmsg Error message
     * @param string $file
     * @param int $line
     * @return boolean
     * @throws ErrorException
     */
    public static function errorHandler($errno, $errmsg, $file, $line)
    {
        // Ignore statments prepended by @
        if ((error_reporting() & $errno) === 0) return false;

        if (self::$ENABLE_FATAL_ERRORS) {
            error_clear_last();
        }

        throw new ErrorException($errmsg, 0, $errno, $file, $line);
    }


    /**
     * Convert fatal errors to error exceptions.
     *
     * This is much like the error->exception helper above but fatal errors
     * are not reported by the error handler. Instead they are caught during
     * the shutdown event.
     *
     * Enable/disable this with the `$enable_fatal_errors` static prop.
     *
     * @return void
     * @throws ErrorException
     */
    public static function handleFatalErrors()
    {
        if (!self::$ENABLE_FATAL_ERRORS) return;

        $error = error_get_last();

        if (!$error) {
            return;
        }

        // Only report those we've enabled.
        if ((error_reporting() & $error['type']) === 0) {
            return;
        }

        // Only report 'fatal' types.
        // Anything else can/should be caught by the regular error/exception handlers.
        if (!in_array($error['type'], [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_CORE_WARNING,
            E_COMPILE_ERROR,
            E_COMPILE_WARNING,
        ])) {
            return;
        }

        $exception = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
        $handler = set_exception_handler(null);

        // Once and done, this helps prevent recursion.
        self::$ENABLE_FATAL_ERRORS = false;

        if ($handler) {
            $handler($exception);
        }
        else {
            throw $exception;
        }
    }


    /**
     * Log exceptions in the database
     *
     * @param \Throwable $exception Exception or error to log
     * @param bool $caught
     * @return int Record ID
     */
    public static function logException($exception, bool $caught = true)
    {
        /** @var PdbPdb|null $pdb */
        static $pdb;
        /** @var PDOStatement|null $insert */
        static $insert;
        /** @var PDOStatement|null $delete */
        static $delete;

        $secrets = Security::getSecretSanitizer();

        // A separate connection for logging to skip past transactions.
        if (!$pdb) {
            $config = SproutPdb::getConfig('default');
            $pdb = PdbPdb::create($config);
        }

        if (!$insert) {
            // Using auto prefixing is probably safe now, because it's a
            // separate PDB instance there's little risk from overrides.
            $table = $pdb->config->prefix . 'exception_log';

            $insert_q = "INSERT INTO {$table}
                (date_generated, class_name, message,
                exception_object, exception_trace, server, get_data, session,
                caught, type, ip_address, session_id)
                VALUES
                (:date, :class, :message,
                :exception, :trace, :server, :get, :session,
                :caught, :type, :ip_address, :session_id)";
            $insert = $pdb->prepare($insert_q);

            $delete_q = "DELETE FROM {$table} WHERE date_generated < DATE_SUB(?, INTERVAL 10 DAY)";
            $delete = $pdb->prepare($delete_q);
        }

        // Extract private attributes from Exception which serialize() would provide
        $extract = static function($exception) {
            $reflect = new ReflectionClass($exception);
            $ex_data = [];
            $ignore_props = ['message', 'trace', 'string', 'previous'];
            $props = $reflect->getProperties();
            foreach ($props as $prop) {
                $prop_name = $prop->name;
                if (in_array($prop_name, $ignore_props)) continue;

                $prop->setAccessible(true);
                $ex_data[$prop_name] = $prop->getValue($exception);
            }

            return $ex_data;
        };

        $ex_data = $extract($exception);

        $trace = $exception->getTraceAsString();
        $previous = $exception->getPrevious();

        while ($previous) {
            $ex_data['previous'][] = $extract($previous);

            $trace .= "\n\nCaused by:\n";
            $trace .= ">> " . get_class($previous) . ": " . $previous->getMessage() . "\n";
            $trace .= "-----------------------------------------------------\n";
            $trace .= $previous->getTraceAsString();

            $previous = $previous->getPrevious();
        }

        $pdb->execute($insert, [
            'date' => $pdb->now(),
            'class' => get_class($exception),
            'message' => substr($exception->getMessage(), 0, 500),
            'exception' => substr(json_encode($secrets->mask($ex_data)), 0, 65000),
            'trace' => substr(json_encode($trace), 0, 65000),
            'server' => substr(json_encode($secrets->mask($_SERVER)), 0, 65000),
            'get' => substr(json_encode($secrets->mask($_GET)), 0, 65000),
            'session' => substr(json_encode($secrets->mask($_SESSION ?? [])), 0, 65000),
            'caught' => (int) $caught,
            'type' => 'php',
            'ip_address' => bin2hex(inet_pton(Request::userIp())),
            'session_id' => Session::id(),
        ], 'null');

        $log_id = $pdb->getLastInsertId();

        $pdb->execute($delete, [$pdb->now()], 'null');

        return (int) $log_id;
    }


    /**
     * Log exceptions to a remote server.
     *
     * @param \Throwable $exception
     * @param int $log_id
     * @param null|string $category
     * @return bool
     */
    public static function logRemoteException($exception, $log_id = 0, $category = null)
    {
        $trace = Services::getTrace();

        if ($trace) {
            return $trace::logException($exception, [
                'log_id' => $log_id,
                'category' => $category,
            ]);
        }

        return true;
    }


    /**
     * Exception handler.
     *
     * @param   Throwable  $exception exception object
     * @return  void
     */
    public static function exceptionHandler(Throwable $exception)
    {
        // If either of these are empty then we can't do config loading and the
        // exception handler will just throw more exceptions.
        // if (self::$configuration === null or self::$include_paths === null) {
        //     if ($exception instanceof \Exception) {
        //         die($exception->getMessage());
        //     }
        //     else {
        //         die('Fatal Kohana error.');
        //     }
        // }

        $event = new ErrorEvent(['error' => $exception]);
        Events::trigger(self::class, $event);

        try {
            $log_id = self::logException($exception, false);
        } catch (Throwable $junk) {
            $log_id = 0;
        }

        try {
            self::logRemoteException($exception, $log_id);
        } catch (Throwable $junk) {}

        try {
            // Our twig traces can get quite messy with all the compiled
            // component bits so here we're cleaning up traces.
            $clean_twig = ($exception instanceof TwigError);

            // Unwrap twig runtime errors. When a 'previous' exception is
            // attached, this is wrapping the true exception. This works well
            // in tandem with the trace cleaning above.
            if (
                ($exception instanceof TwigRuntimeError)
                and ($previous = $exception->getPrevious())
            ) {
                $exception = $previous;
            }

            $context = [
                'exception' => $exception,
                'log_id'   => $log_id,
                'code'     => $exception->getCode(),
                'error'    => get_class($exception),
                'type'     => get_class($exception),
                'message'  => $exception->getMessage(),
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine(),
                'level'    => 1,
                'description' => '',
                'trace' => null,
            ];

            // For PHP + kohana errors, look up name and description from the i18n system.
            if ($exception instanceof ErrorException) {
                $severity_info = I18n::lang('errors.' . $exception->getSeverity());
            } else if ($exception instanceof Kohana_Exception) {
                $severity_info = I18n::lang('errors.' . $exception->getCode());
            }

            if (isset($severity_info) and is_array($severity_info)) {
                list($level, $error, $description) = $severity_info;
                $context['level'] = $level;
                $context['error'] = $error;
                $context['description'] = $description;
            }

            // Remove the DOCROOT from the path, as a security precaution
            $context['file'] = str_replace('\\', '/', realpath($context['file']));
            if (IN_PRODUCTION) {
                $context['file'] = preg_replace('|^'.preg_quote(DOCROOT).'|', '', $context['file']);
            }

            // Scrub details for database errors on production sites.
            if (IN_PRODUCTION and $exception instanceof QueryException) {
                $context['file'] = '';
                $context['message'] = '';
                $context['line'] = 0;
                $context['description'] = 'A database error occurred while performing the requested procedure.';
            }

            // Close all output buffers, except our own.
            App::closeBuffers(false);

            // Send the headers if they have not already been sent
            if ($exception instanceof HttpException and !headers_sent()) {
                $exception->sendHeaders();
            }

            // 404 errors are a special case - we load content from the database and put into a nice skin
            if (
                ($exception instanceof RowMissingException and IN_PRODUCTION)
                or
                ($exception instanceof HttpExceptionInterface and $exception->getStatusCode() == 404)
            ) {
                if (!headers_sent()) {
                    header("HTTP/1.0 404 File Not Found");
                }

                if ($exception instanceof RowMissingException) {
                    $context['message'] = 'One of the database records for the page you requested could not be found.';
                }

                if (PHP_SAPI === 'cli') {
                    echo $context['message'], PHP_EOL;
                } else {
                    $page = new PhpView('sprout/404_error');
                    $page->context = $context;
                    $page->message = $context['message'];
                    $page = $page->render();

                    if (BaseView::exists('skin/404')) {
                        $view = BaseView::create('skin/404');
                    } else {
                        $view = BaseView::create('skin/inner');
                    }

                    $view->page_title = '404 File Not Found';
                    $view->main_content = $page;
                    $view->context = $context;
                    $view->controller = '404-error';
                    echo $view->render();
                }
            } else {
                if (!headers_sent()) {
                    $status = 500;

                    if ($exception instanceof HttpExceptionInterface) {
                        $status = $exception->getStatusCode();
                    }

                    header(HttpStatus::getStatus($status));
                }

                if (!IN_PRODUCTION) {
                    $trace = $exception->getTrace();
                    $trace = self::simpleBacktrace($trace);
                    $trace = TwigView::processBacktrace($trace, $clean_twig);
                    $trace = self::backtrace($trace);
                    $context['trace'] = $trace;
                }

                // Decode the twig frame, if available.
                if (
                    !empty($context['file'])
                    and !empty($line = (int) $context['line'])
                    and ($twig_frame = TwigView::decodeErrorFrame($context['file'], $line))
                ) {
                    [$file, $line] = $twig_frame;
                    $context['file'] = $file;
                    $context['line'] = $line;
                }

                // Load the error
                if (PHP_SAPI == 'cli') {
                    self::renderError(APPPATH . 'views/system/error_cli.php', $context);
                } elseif (!IN_PRODUCTION) {
                    self::renderError(APPPATH . 'views/system/error_development.php', $context);
                } else {
                    // No skin defined yet? Use the default one
                    if (! SubsiteSelector::$subsite_code) {
                        SubsiteSelector::setSubsite(['id' => 1, 'code' => 'default']);
                    }

                    // Use the skin template or fall back to a sensible default
                    $file = DOCROOT . 'skin/' . SubsiteSelector::$subsite_code . '/exception.php';

                    if (! file_exists($file)) {
                        $file = APPPATH . 'views/system/error_production.php';
                    }

                    self::renderError($file, $context);
                }
            }

            // Run the shutdown even to ensure a clean exit
            if (!Events::hasRun(Kohana::class, ShutdownEvent::class)) {
                $event = new ShutdownEvent();
                Events::trigger(Kohana::class, $event);
            }

            // Turn off error reporting
            error_reporting(0);
            exit;
        }
        catch (Throwable $e)
        {
            while (set_error_handler(null));
            while (set_exception_handler(null));

            try {
                $log2_id = self::logException($e, false);
            } catch (Throwable $e2) {
                $log2_id = 0;
            }

            try {
                // We can say a bit more in CLI modes.
                // TODO should we use STDOUT?
                if (PHP_SAPI == 'cli') {
                    echo 'Failed to handle ', get_class($exception), " - #{$log_id}\n";
                    echo 'Error: ' . get_class($e) . "\n";
                    echo "Log ID: #{$log2_id}\n";

                    // But not too much.
                    if (!IN_PRODUCTION) {
                        echo $e->getFile(), ' on line ', $e->getLine(), ":\n";
                        echo $e->getMessage(), "\n";
                        echo $e->getTraceAsString(), "\n";
                    }

                    die;
                } else if (IN_PRODUCTION) {
                    echo "Fatal Error, ID: #{$log_id}\n";
                } else {
                    echo "<pre>";
                    echo "<b>Failed to handle ", get_class($exception), " - id #{$log_id}</b>\n";
                    echo "Error: ", get_class($e), "\n";
                    echo "Log ID: #{$log2_id}\n";

                    echo $e->getFile(), ' on line ', $e->getLine(), ":\n";
                    echo $e->getMessage(), "\n\n";

                    echo "<b>Route:</b> ", Router::$controller, '::';
                    echo Router::$method;
                    if (!empty(Router::$arguments)) {
                        echo '(', implode(', ', Router::$arguments), ')';
                    }
                    echo "\n\n";

                    echo "<b>Trace:</b>\n";
                    $trace = print_r($e->getTrace(), true);
                    echo preg_replace('/\n{2,}/', "\n", $trace);
                }

                die;
            } catch (Throwable $e3) {
                // That's it, no more.
                error_log("Fatal error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                error_log("Error handling error: " . $e3->getMessage() . "\n" . $e3->getTraceAsString());
                die("Fatal Error: #{$log_id}");
            }
        }
    }



    /**
     * Render an error template.
     *
     * @param string $template full path
     * @param array $variables
     * @return void
     */
    public static function renderError(string $template, array $variables)
    {
        (static function($_template, $_variables) {
            extract($_variables, EXTR_SKIP);
            require $_template;
        })($template, $variables);
    }
}
