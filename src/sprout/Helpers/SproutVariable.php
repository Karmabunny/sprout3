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

use BadMethodCallException;
use Closure;
use Exception;
use karmabunny\kb\PropertiesTrait;
use Kohana;
use Sprout\Exceptions\FileMissingException;
use Twig\Markup;

/**
 * Access to Sprout helpers from Twig templates.
 *
 * Additional variables can be added to this namespace using
 * `SproutVariable::register(name, item)` in a module's `sprout_load.php` file.
 *
 * This is registered by SproutExtension.
 *
 * Use this in your Twig templates like so:
 *
 * ```twig
 * {# Get a file:// url with absroot. #}
 * {% set fileUrl = sprout.url.file(filePath) %}
 *
 * {# Require the 'fb' module JS/CSS files. #}
 * {% do sprout.needs.module('fb') %}
 *
 * {# Render the CSRF token input. #}
 * {{ sprout.csrf.token|raw }}
 * ```
 *
 * Important things:
 *  - read the relevant docs for each helper.
 *  - helpers that return HTML will likely need to be un-escaped with the `|raw` filter.
 */
class SproutVariable
{
    use PropertiesTrait {
        getProperties as protected;
    }

    static protected $extra = [];

    /** @var Url */
    public $url;

    /** @var Request */
    public $request;

    /** @var Skin */
    public $skin;

    /** @var Needs */
    public $needs;

    /** @var Enc */
    public $enc;

    /** @var Session */
    public $session;

    /** @var Navigation */
    public $navigation;

    /** @var Notification */
    public $notification;

    /** @var Widgets */
    public $widgets;

    /** @var array [SocialMedia, SocialNetworking] */
    public $social;

    /** @var ContentReplace */
    public $replace;

    /** @var File */
    public $file;

    /** @var Lnk */
    public $lnk;

    /** @var AdminAuth */
    public $admin;

    /** @var Csrf */
    public $csrf;

    /** @var Captcha */
    public $captcha;

    /** @var Cookie */
    public $cookie;

    /** @var MultiEdit */
    public $multiedit;

    /** @var Text */
    public $text;

    /** @var UserAuth */
    public $user;

    /** @var UserPerms */
    public $permissions;

    /** @var Page */
    public $page;


    public function __construct()
    {
        $this->url = new Url();
        $this->request = new Request();
        $this->skin = new Skin();
        $this->needs = new Needs();
        $this->enc = new Enc();
        $this->session = new Session();
        $this->navigation = new Navigation();
        $this->notification = new Notification();
        $this->widgets = new Widgets();
        $this->social = [
            'meta' => new SocialMeta(),
            'networking' => new SocialNetworking(),
        ];
        $this->replace = new ContentReplace();
        $this->file = new File();
        $this->lnk = new Lnk();
        $this->admin = new AdminAuth();
        $this->csrf = new Csrf();
        $this->captcha = new Captcha();
        $this->cookie = new Cookie();
        $this->multiedit = new MultiEdit();
        $this->text = new Text();
        $this->page = new Page();

        if ($user = UserAuth::realUserAuthInst()) {
            $this->user = $user;
        } else {
            $this->user = new UserAuth();
        }

        if ($permissions = UserPerms::realUserPermsInst()) {
            $this->permissions = $permissions;
        } else {
            $this->permissions = new UserPerms();
        }
    }


    /**
     * Get all GET query params.
     *
     * aka. `$_GET`
     *
     * @return array
     */
    public function getParams()
    {
        return $_GET;
    }


    /**
     * Get a single GET query parameter, or `null` if missing.
     *
     * aka. `$_GET[name]`
     *
     * @return string|array|null
     */
    public function getParam($name)
    {
        return $_GET[$name] ?? null;
    }


    /**
     * Get the GET query string.
     *
     * aka. `Router::query_string`
     *
     * @return string
     */
    public function getQueryString()
    {
        return Router::$query_string;
    }


    /**
     * The request URI.
     *
     * aka. `Router::current_uri`
     *
     * @return string
     */
    public function getPath()
    {
        return Router::$current_uri;
    }


    /**
     * Render a template, whether it's PHP or Twig.
     *
     * Note, if importing a Twig template use the native `{% include %}` directive.
     *
     * @param string $name
     * @param array $data
     * @return Markup
     * @throws Exception
     */
    public function include($name, $data = [])
    {
        return new Markup(View::include($name, $data), 'UTF-8');
    }


    /**
     * Render a file, without performing any pre-processing.
     *
     * This uses the skin lookup rules (modules/sprout/skin).
     *
     * @param string $path formatted `name.ext`
     * @return Markup
     */
    public function require($path)
    {
        $matches = [];

        // TODO Is this too aggressive?
        // findTemplate() doesn't really care about the extension...
        if (!preg_match('!^(.+)(\.[^.]+)$!', $path, $matches)) {
            throw new FileMissingException("File missing: {$path}");
        }

        [$_, $name, $extension] = $matches;
        $path = Skin::findTemplate($name, $extension);
        return new Markup(file_get_contents(DOCROOT . $path), 'UTF-8');
    }


    /**
     * Load a config file or config variable.
     *
     * aka. `Kohana::config()`
     *
     * @param string $name
     * @param bool $slash
     * @param bool $required
     * @return mixed
     */
    public function config($name, $slash = false, $required = true)
    {
        return Kohana::config($name, $slash, $required);
    }


    /**
     * This proxies calls to functions or variables registered by modules.
     *
     * Use `SproutVariable::register(name, item)` in your `sprout_load.php` files.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $item = self::$extra[$name] ?? null;

        if (!$item) {
            throw new BadMethodCallException("Variable {$name} does not exist");
        }

        if (is_object($item) and $item instanceof Closure) {
            return $item(...$arguments);
        }

        if (!is_object($item) and is_callable($item)) {
            return $item(...$arguments);
        }

        if (is_string($item) and class_exists($item)) {
            return new $item(...$arguments);
        }

        return $item;
    }


    /**
     * Register a variable or function on the sprout namespace.
     *
     * This is for modules to expose additional functionality via
     * the `sprout.*` namespace in Twig templates.
     *
     * Call this method in your module `sprout_load.php` file.
     *
     * Note, trying to register a name that is a reserved variables (those
     * explicitly declared in this class) will throw an exception.
     *
     * @param string $name
     * @param mixed $item a function reference/closure or variable
     * @return void
     * @throws Exception if the name is a reserved variable
     */
    public static function register($name, $item)
    {
        $properties = self::getProperties();
        if (in_array($name, $properties)) {
            throw new Exception("Cannot register reserved variable '{$name}'");
        }

        self::$extra[$name] = $item;
    }
}
