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

namespace Sprout\Helpers;

/**
 * Interface for worker jobs.
 *
 * This is a bridging interface for the old {@see WorkerBase} class.
 *
 * New workers should implement {@see WorkerJobInterface} via {@see WorkerJob}.
 */
interface WorkerInterface
{

    /**
     * Gets the job name
     *
     * @return string
     */
    public function getName(): string;


    /**
     * Gets the metric names.
     *
     * @return array<int,string>
     **/
    public function getMetricNames(): array;

}
