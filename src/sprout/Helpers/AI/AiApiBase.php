<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */
namespace Sprout\Helpers\AI;

/**
 * Base class to ensure common functions for API tooling
 */
abstract class AiApiBase
{

    /**
     * Get a list of endpoints available for use with this class
     *
     * @return array
     */
    abstract public function getEndpoints(): array;


    /**
     * Class specific check to see if we can use AI. E.g. do we have a key?
     *
     * @return bool
     */
    abstract public function getUsable(): bool;


    /**
     * Get the cost of the last request
     *
     * @return mixed
     */
    abstract public function getLastRequestCost(): mixed;


    /**
     * Describe what the cost unit is for this system (e.g. dollars, tokens)
     *
     * @return string
     */
    abstract public function getRequestCostUnit(): string;

}