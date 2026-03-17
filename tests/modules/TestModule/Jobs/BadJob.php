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

namespace Sprout\TestModules\TestModule\Jobs;

use Sprout\Helpers\WorkerJob;

/**
 * A worker job that fails to deserialize.
 */
class BadJob extends WorkerJob
{

    /** @inheritdoc */
    public function run()
    {
        $this->log("Bad job running");
    }


    /** @inheritdoc */
    public static function fromJson(array $json): self
    {
        throw new \Exception("failed to deserialize");
    }
}
