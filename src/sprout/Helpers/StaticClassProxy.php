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
use ReflectionClass;

/**
 * Wrap a class object or string and proxy _both_ instance and static methods.
 *
 * This tries to emulate the same calling conventions builtin to twig.
 *
 * Order of lookup:
 *
 * - static
 * - constant
 * - property
 * - method
 * - get/is/has method
 *
 */
class StaticClassProxy
{


    /** @var object|string */
    protected $target;


    /**
     *
     * @param object|string $target
     */
    public function __construct($target)
    {
        $this->target = $target;
    }


    /**
     *
     * @param string $name
     * @return string|null
     */
    protected function resolveMethod($name)
    {
        foreach (['', 'get', 'is', 'has'] as $prefix) {
            $method = $prefix . $name;

            if (method_exists($this->target, $method)) {
                return $method;
            }
        }

        return null;
    }


    /**
     * @param string $field
     * @return bool
     */
    public function __isset($field)
    {
        $class = new ReflectionClass($this->target);

        if (array_key_exists($field, $class->getStaticProperties())) {
            return true;
        }

        if (array_key_exists($field, $class->getConstants())) {
            return true;
        }

        if (is_object($this->target) and $class->hasProperty($field)) {
            return true;
        }

        if ($this->resolveMethod($field)) {
            return true;
        }

        return false;
    }


    /**
     * @param string $field
     * @return mixed
     */
    public function __get($field)
    {
        if (is_object($this->target) and property_exists($this->target, $field)) {
            return $this->target->{$field};
        }

        $class = new ReflectionClass($this->target);

        if (array_key_exists($field, $class->getConstants())) {
            return $class->getConstant($field);
        }

        if (array_key_exists($field, $class->getStaticProperties())) {
            return $class->getStaticPropertyValue($field);
        }

        if ($method = $this->resolveMethod($field)) {
            return call_user_func([$this->target, $method]);
        }

        return null;
    }


    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if ($method = $this->resolveMethod($method)) {
            return call_user_func_array([$this->target, $method], $arguments);
        }

        throw new BadMethodCallException("Method {$method} does not exist");
    }
}
