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

namespace Sprout;

use karmabunny\visor\Server;

/**
 * A Visor server for Sprout.
 *
 * This is intended for testing and development environments only.
 */
class SproutVisor extends Server
{

    /** @var string */
    public $docroot;


    /** @inheritdoc */
    public function __construct(array $config = [])
    {
        $webroot = $config['webroot'] ?? null;
        unset($config['webroot']);

        if (!$webroot) {
            throw new \Exception("Missing 'webroot' config.");
        }

        $this->docroot = rtrim($webroot, '/');
        parent::__construct($config);
    }


    /** @inheritdoc */
    protected function getTargetScript(): string
    {
        return $this->docroot . '/index.php';
    }
}
