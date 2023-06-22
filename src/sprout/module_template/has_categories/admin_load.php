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

use Sprout\Helpers\Register;


Register::adminControllers([
    'SNAME' => \SproutModules\AUTHOR\MODULE\Admin\CNAMEAdminController::class,
    'SNAME_category' => \SproutModules\AUTHOR\MODULE\Admin\CNAMECategoryAdminController::class,
]);

Register::adminTile(
    'PNICE',
    'description',
    '- no description -',
    [
        'SNAME' => 'PNICE',
    ]
);
