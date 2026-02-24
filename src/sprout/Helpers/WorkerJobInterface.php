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

use JsonSerializable;
use karmabunny\interfaces\JobInterface;
use karmabunny\interfaces\JsonDeserializable;

/**
 * Interface for worker jobs.
 *
 * Unlike the old {@see WorkerBase}, the worker configuration is stored in the
 * instance rather than passed to the `run()` method.
 *
 * Use the Json serialisation interfaces to convert the job to and from JSON.
 */
interface WorkerJobInterface extends
    WorkerInterface,
    JobInterface,
    JsonSerializable,
    JsonDeserializable
{
}
