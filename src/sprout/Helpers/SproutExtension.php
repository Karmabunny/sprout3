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

use DateTimeImmutable;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 *
 */
final class SproutExtension
    extends AbstractExtension
    implements GlobalsInterface
{

    /** @inheritdoc */
    public function getGlobals()
    {
        return [
            'IN_PRODUCTION' => IN_PRODUCTION,
            'DOCROOT' => DOCROOT,

            'sprout' => new SproutVariable(),
            'now' => new DateTimeImmutable(null),
        ];
    }
}
