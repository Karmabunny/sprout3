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


Register::adminControllers('Karmabunny\Demo', [
    'demo_item' => 'Admin\DemoItemAdminController',
    'demo_item_category' => 'Admin\DemoItemCategoryAdminController',
    'word' => 'Admin\WordAdminController',
    'word_category' => 'Admin\WordCategoryAdminController',
]);

Register::adminTile(
    'Demo items',
    'live_help',
    'Just some test items',
    [
        'demo_item' => 'Demo items',
        'word' => 'Words',
    ]
);
