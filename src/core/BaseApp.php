<?php
/*
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

namespace Sprout\Core;

use karmabunny\kb\Buffer;
use karmabunny\kb\Events;
use karmabunny\router\Action;
use karmabunny\router\Router;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Sprout\Events\BootstrapEvent;
use Sprout\Events\DisplayEvent;
use Sprout\Events\PostControllerEvent;
use Sprout\Events\PostRoutingEvent;
use Sprout\Events\PreControllerEvent;
use Sprout\Events\PreRoutingEvent;
use Sprout\Events\ShutdownEvent;

/**
 * The base application class.
 *
 * This performs buffering and routing.
 */
abstract class BaseApp
{

    /** @var int 1 MiB */
    public static int $SEND_BUFFER_SIZE = 1024 * 1024;


    /**
     * The singleton instance.
     *
     * @var null|BaseApp
     */
    protected static ?BaseApp $_instance = null;

    /**
     * The application output buffer.
     *
     * @var Buffer
     */
    protected Buffer $buffer;

    /**
     * The application router.
     *
     * @var Router
     */
    protected Router $router;

    /**
     * The routes table.
     *
     * @var array
     */
    protected array $routes = [];

    /**
     * The base controller class, all controller must extend this.
     *
     * @var class-string<ControllerInterface>
     */
    protected string $controller;


    public function __construct()
    {
        $this->buffer = new Buffer();
        $this->router = Router::create([]);
        $this->controller = ControllerInterface::class;
    }


    /**
     * Initializes the app.
     *
     * This only runs once.
     *
     * @return void
     * @throws RuntimeException
     */
    protected function init()
    {
        // Process output buffering on shutdown.
        Events::on(self::class, ShutdownEvent::class, function() {
            $event = new DisplayEvent();
            $event->output = $this->buffer->clean();
            Events::trigger(self::class, $event);
            echo $event->output;
        });
    }


    /**
     * Returns the singleton instance of the app.
     *
     * @return BaseApp
     * @throws RuntimeException
     */
    public static function instance(): BaseApp
    {
        if (static::$_instance === null) {
            // @phpstan-ignore-next-line
            $app = new static();
            $app->init();

            if (!is_subclass_of($app->controller, ControllerInterface::class)) {
                throw new RuntimeException("Controller class '{$app->controller}' must extend " . ControllerInterface::class);
            }

            static::$_instance = $app;
        }

        return static::$_instance;
    }


    /**
     * Run the application.
     *
     * This executes controller instance and runs shutdown events.
     *
     * @return never
     */
    public function run()
    {
        $this->buffer->start();

        // Send default text/html UTF-8 header
        header('Content-Type: text/html; charset=UTF-8');

        $event = new BootstrapEvent([
            'routes' => $this->routes,
        ]);
        Events::trigger(self::class, $event);

        $this->routes = $event->routes;
        $this->router->load($this->routes);

        // Begin.
        [$method, $uri] = $this->resolveRequest();

        $event = new PreRoutingEvent([
            'method' => $method,
            'uri' => $uri,
        ]);
        Events::trigger(self::class, $event);

        $method = $event->method;
        $uri = $event->uri;

        // Perform routing.
        $action = $this->resolveAction($method, $uri);

        $event = new PostRoutingEvent([
            'method' => $method,
            'uri' => $uri,
            'action' => $action,
        ]);
        Events::trigger(self::class, $event);

        $action = $event->action;

        // No action raises 404.
        if (!$action) {
            $this->notFound();
        }

        // Invalid targets also raise a 404.
        if (!$action->isController($this->controller)) {
            $this->notFound();
        }

        /** @var class-string<ControllerInterface> $controller */
        [$controller, $method] = $action->target;
        $arguments = $action->args;

        $event = new PreControllerEvent([
            'controller' => $controller,
            'method' => $method,
            'arguments' => $arguments,
        ]);
        Events::trigger(self::class, $event);

        // Execute the request.
        $instance = new $controller();
        $response = $instance->_run($method, $arguments);

        // Controller method has been executed
        $event = new PostControllerEvent([
            'response' => $response,
        ]);
        Events::trigger(self::class, $event);

        $response = $event->response;

        // Send the response.
        if ($response instanceof ResponseInterface) {
            self::send($response);
        }

        // Tie up.
        $this->shutdown();
    }

    /**
     * Get the incoming request method and URI.
     *
     * @return array{0:string,1:string} [method, uri]
     */
    public function resolveRequest(): array
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = strtok($_SERVER['REQUEST_URI'], '?');
        $uri = trim($uri, '/');

        return [$method, $uri];
    }


    /**
     * Resolve an action from a method and URI.
     *
     * @param string $method
     * @param string $uri
     * @return Action|null
     */
    public function resolveAction(string $method, string $uri)
    {
        return $this->router->find($method, $uri);
    }


    /**
     * Trigger the shutdown event and exit.
     *
     * Use this to safely exit the application and ensure all system resources
     * are cleaned up.
     *
     * @param string|null $message
     * @return never
     */
    public function shutdown(?string $message = null)
    {
        Events::trigger(self::class, new ShutdownEvent());
        $this->buffer->end(true);
        exit($message ?? 0);
    }


    /**
     * Exit with a 404 status.
     *
     * @return never
     */
    public function notFound()
    {
        if (!headers_sent()) {
            http_response_code(404);
        }

        $this->shutdown();
    }


    /**
     * Send a response to the client.
     *
     * @param ResponseInterface $response
     * @return void
     */
    public static function send(ResponseInterface $response)
    {
        $version = $response->getProtocolVersion();
        $reason = $response->getReasonPhrase();
        $status = $response->getStatusCode();

        header("HTTP/{$version} {$status} {$reason}", true, $status);

        foreach ($response->getHeaders() as $name => $values) {
            $name = ucwords(strtolower($name), '-');
            $value = implode(', ', $values);
            header("{$name}: {$value}", true);
        }

        $stream = $response->getBody();

        if ($stream->isReadable()) {
            while (!$stream->eof()) {
                echo $stream->read(static::$SEND_BUFFER_SIZE);
            }
        }
    }


    /**
     * Get the buffer instance.
     *
     * @return Buffer
     */
    public function getBuffer(): Buffer
    {
        return $this->buffer;
    }


    /**
     * Get the routes tables.
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->router->getRoutes();
    }


    /**
     * Flush or discard the application buffer.
     *
     * @param bool $flush
     * @return bool
     */
    public static function closeBuffers($flush = true)
    {
        if (!static::$_instance) {
            return false;
        }

        if ($flush) {
            static::$_instance->getBuffer()->flush();
        } else {
            static::$_instance->getBuffer()->discard();
        }

        return true;
    }
}
