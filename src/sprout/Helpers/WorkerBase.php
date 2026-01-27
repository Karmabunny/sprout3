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

use Sprout\Exceptions\WorkerJobException;

/**
 * Base class for worker jobs, which are run via CLI in a separate process
 */
abstract class WorkerBase implements WorkerInterface
{
    /**
    * Specify a custom job name by overwriting this
    **/
    protected $job_name = '';

    /**
    * Specify up to three metrics
    **/
    protected $metric_names = array(
        1 => '',
        2 => '',
        3 => '',
    );


    /*
    public function run(...)
    {
        //
        // Whatever is provided as arguments to WorkerCtrl::start will be provided in the function call
        //
    }
    */



    /**
    * Constructor
    *
    * Manually checks if the method 'run' exists,
    * because it's a varags,
    * so can't use the normal abstract function approach
    **/
    public final function __construct() {
        if (!method_exists($this, 'run')) {
            throw new WorkerJobException('Worker class must implement run() method');
        }
    }


    /** @inheritdoc */
    public function getName(): string
    {
        if ($this->job_name) return $this->job_name;
        return static::class;
    }


    /** @inheritdoc */
    public function getMetricNames(): array
    {
        return $this->metric_names;
    }

}


