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

namespace Sprout\Controllers;

use BadMethodCallException;
use InvalidArgumentException;
use karmabunny\kb\Events;
use Kohana;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use ReflectionException;
use ReflectionMethod;
use Sprout\Helpers\ModuleInterface;
use Sprout\Helpers\Modules;
use Sprout\Events\AfterActionEvent;
use Sprout\Events\NotFoundEvent;
use Sprout\Events\BeforeActionEvent;
use Sprout\Events\RedirectEvent;
use Sprout\Helpers\BaseView;
use Sprout\Helpers\Html;
use Sprout\Helpers\Json;
use Sprout\Helpers\Request;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\Url;

/**
 * This is a true base controller.
 *
 * It does nothing but base things.
 *
 * @package Sprout\Controllers
 */
abstract class BaseController
{

    // Allow all controllers to run in production by default
    const ALLOW_PRODUCTION = TRUE;


    /**
     * A request object to read from the client.
     *
     * @var ServerRequestInterface
     */
    public ServerRequestInterface $request;


    /**
     * A response object to send to the client.
     *
     * If returned from a controller method this is then processed and sent
     * to the client. Otherwise it's ignored and standard echo output is used.
     *
     * @var ResponseInterface
     */
    public ResponseInterface $response;


    /**
     * @return  void
     */
    public function __construct()
    {
        if (Kohana::$instance == NULL)
        {
            // Set the instance to the first controller loaded
            Kohana::$instance = $this;
        }

        $this->request = Request::getPsrRequest();

        $headers = Sprout::getHeaders();
        $this->response = new Response(200, $headers);
    }


    /**
     * The router/kohana will invoke this method to invoke an action.
     *
     * If you please, you may wrap this method to create before/after hooks.
     *
     * @param mixed $method
     * @param mixed $args
     * @return mixed
     */
    public function _run($method, $args)
    {
        try {
            $reflect = new ReflectionMethod($this, $method);

            // Do not allow access to hidden methods
            if ($method[0] === '_') {
                throw new ReflectionException('hidden controller method');
            }

            // Do not attempt to invoke protected methods
            if ($reflect->isProtected() or $reflect->isPrivate()) {
                throw new ReflectionException('protected controller method');
            }
        }
        catch (ReflectionException $exception) {
            $event = new NotFoundEvent();
            Events::trigger(Kohana::class, $event);
            return;
        }

        $event = new BeforeActionEvent([
            'sender' => $this,
            'method' => $method,
            'arguments' => $args,
        ]);

        Events::trigger(BaseController::class, $event);

        if ($event->cancelled) {
            return null;
        }

        $response = $this->$method(...$args);

        $event = new AfterActionEvent(['result' => $response]);
        Events::trigger(BaseController::class, $event);
        $response = $event->result;

        return $response;
    }


    /**
     * Handles methods that do not exist.
     *
     * @param   string $method Method name
     * @param   array $args Arguments
     * @return  void
     * @throws  BadMethodCallException
     */
    public function __call($method, $args)
    {
        throw new \BadMethodCallException("Method '{$method}' not found");
    }


    /**
     * Set the cache headers for the response.
     *
     * This requires the controller method to return the response object.
     *
     * @param string $expires
     * @return void
     */
    public function setCacheHeaders(string $expires = '1 day'): void
    {
        $expires = strtotime($expires);
        $maxAge = $expires - time();
        $expires = gmdate('D, d M Y H:i:s', $expires) . ' GMT';

        $this->response = $this->response
            ->withHeader('Expires', $expires)
            ->withHeader('Pragma', 'cache')
            ->withHeader('Cache-Control', 'max-age=' . $maxAge);
    }


    /**
     * Stream a response to the client.
     *
     * @param resource|StreamInterface $stream
     * @param null|string $contentType
     * @return ResponseInterface
     * @throws InvalidArgumentException
     */
    public function stream($stream, ?string $contentType = null): ResponseInterface
    {
        if (is_resource($stream)) {
            $stream = new Stream($stream);
        }

        $status = $this->response->getStatusCode();
        $headers = $this->response->getHeaders();

        if ($contentType) {
            $headers['Content-Type'] = [$contentType];
        }

        $this->response = new Response($status, $headers, $stream);
        return $this->response;
    }


    /**
     * Render a JSON response.
     *
     * @param array $data
     * @return ResponseInterface
     */
    public function json(array $data): ResponseInterface
    {
        $status = $this->response->getStatusCode();
        $headers = $this->response->getHeaders();
        $headers['Content-Type'] = ['application/json; charset=utf-8'];

        $body = Json::encode($data);

        $this->response = new Response($status, $headers, $body);
        return $this->response;
    }


    /**
     * Render a text response.
     *
     * @param string $text
     * @return ResponseInterface
     */
    public function text(string $text): ResponseInterface
    {
        $status = $this->response->getStatusCode();
        $headers = $this->response->getHeaders();
        $headers['Content-Type'] = ['text/plain; charset=utf-8'];

        $this->response = new Response($status, $headers, $text);
        return $this->response;
    }


    /**
     * Render an html response.
     *
     * @param string $html
     * @return ResponseInterface
     */
    public function html(string $html): ResponseInterface
    {
        $status = $this->response->getStatusCode();
        $headers = $this->response->getHeaders();
        $headers['Content-Type'] = ['text/html; charset=utf-8'];

        $this->response = new Response($status, $headers, $html);
        return $this->response;
    }


    /**
     * Render a view response.
     *
     * @param string $view
     * @param array $data
     * @return ResponseInterface
     */
    public function render(string $view, array $data = []): ResponseInterface
    {
        $status = $this->response->getStatusCode();
        $headers = $this->response->getHeaders();
        $headers['Content-Type'] = ['text/html; charset=utf-8'];

        $view = BaseView::create($view, $data);
        $body = $view->render();

        $this->response = new Response($status, $headers, $body);
        return $this->response;
    }


    /**
     * Redirect to a new URL.
     *
     * @param string $uri
     * @param int|string $method
     * @return ResponseInterface
     */
    public function redirect(string $uri, $method = 302): ResponseInterface
    {
        static $CODES = [
            'refresh' => 'Refresh',
            '300' => 'Multiple Choices',
            '301' => 'Moved Permanently',
            '302' => 'Found',
            '303' => 'See Other',
            '304' => 'Not Modified',
            '305' => 'Use Proxy',
            '307' => 'Temporary Redirect'
        ];

        // HTTP headers expect absolute URLs
        if (strpos($uri, '://') === false) {
            $uri = Url::site($uri, true);
        }

        // Validate the method and default to 302
        $method = isset($CODES[$method]) ? (string) $method : '302';

        $body = "<h1>{$method} - {$CODES[$method]}</h1>";

        if ($method === '300') {
            $uri = (array) $uri;

            $body .= '<ul>';
            foreach ($uri as $link) {
                $body .= '<li>' . Html::anchor($link) . '</li>';
            }
            $body .= '</ul>';

            // The first URI will be used for the Location header
            $uri = $uri[0];
        } else {
            $body .= '<p>'.Html::anchor($uri).'</p>';
        }

        // Run the redirect event
        $event = new RedirectEvent(['uri' => $uri]);
        Events::trigger(static::class, $event);
        $uri = $event->uri;

        $headers = $this->response->getHeaders();

        if ($method === 'refresh') {
            $status = 200;
            $headers['Refresh'] = [0, "url={$uri}"];
        } else {
            $status = (int) $method;
            $headers['Location'] = [$uri];
        }

        $this->response = new Response($status, $headers, $body);
        return $this->response;
    }


    /**
     * Get the module that this controller belongs to.
     *
     * If the controllers belongs to Sprout itself, this returns null.
     *
     * @return ModuleInterface|null
     */
    public function getModule(): ?ModuleInterface
    {
        return Modules::getModuleForClass($this);
    }


    /**
     * Get the absolute path of the current module for this controller.
     *
     * TODO this might be too broad. It's only used for loading JSON forms
     * and perhaps encourages bad behaviour. Such as assuming common path
     * structures for all modules, where instead this should be written into
     * the module class.
     *
     * @return string
     */
    public function getAbsModulePath(): string
    {
        $module = $this->getModule();

        // Assume it's a core sprout controller.
        if (!$module) {
            return rtrim(APPPATH, '/');
        }

        return $module->getPath();
    }


    /**
     * Get the a prefix suitable for finding views for this controller.
     *
     * Do not assume that all modules, or even core sprout live relative to
     * each other or the DOCROOT (as they previously did).
     *
     * TODO rename this - like `getViewPrefix()`
     *
     * @return string 'sprout' or 'modules/AwesomeModule'
     */
    public function getModulePath(): string
    {
        $module = $this->getModule();

        // Assume it's a core sprout controller.
        if (!$module) {
            return 'sprout';
        }

        return 'modules/' . $module->getName();
    }


    /**
     * Return the class name for this controller, expressed in CSS style, i.e. with dashes
     *
     * Example: When called from BlogPostController --> 'blog-post-controller'
     *
     * @return string Name of this PHP class, in a format suitable for use in CSS
     */
    public function getCssClassName()
    {
        return Html::className($this);
    }
}