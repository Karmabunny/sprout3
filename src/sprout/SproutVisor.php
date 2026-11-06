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
    public ?string $docroot = null;


    /** @inheritdoc */
    public function update($config): void
    {
        if ($webroot = $config['webroot'] ?? null) {
            $this->docroot = rtrim($webroot, '/');
        }
    }


    /** @inheritdoc */
    public function init(): void
    {
        if (!$this->docroot) {
            throw new \Exception("Missing 'webroot' config.");
        }
    }


    /** @inheritdoc */
    public function healthCheck(): bool
    {
        $path = $this->getHostUrl() . '/_healthcheck';
        $this->log($path);

        $res = @file_get_contents($path);

        if ($res === false) {
            $this->log('--no response--');
            return false;
        }

        $status = $http_response_header[0] ?? '--no headers--';

        $this->log($status);
        return strpos($status, '200') !== false;
    }


    /** @inheritdoc */
    protected function getTargetScript(): string
    {
        return $this->docroot . '/index.php';
    }
}
