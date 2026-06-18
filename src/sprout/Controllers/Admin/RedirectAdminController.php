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

namespace Sprout\Controllers\Admin;

use Sprout\Helpers\ColModifierLnk;
use Sprout\Helpers\ColModifierLookupTable;
use Sprout\Helpers\Enc;
use Sprout\Helpers\RefineBar;
use Sprout\Helpers\RefineWidgetTextbox;
use Sprout\Helpers\Validator;


/**
* Handles most processing for Redirects
**/
class RedirectAdminController extends HasCategoriesAdminController
{
    protected string $friendly_name = 'Redirects';
    protected array $add_defaults = [
        'active' => 1,
        'type' => 'Temporary',
    ];
    protected string $main_order = 'item.path_exact';

    /**
    * Constructor
    **/
    public function __construct()
    {
        $this->main_columns = [
            'Conditions' => function($row) {
                $conds = [];
                if ($row['path_exact']) {
                    $conds[] = 'path exactly matches "' . Enc::html($row['path_exact']) . '"';
                }
                if ($row['path_contains']) {
                    $conds[] = 'path contains "' . Enc::html($row['path_contains']) . '"';
                }
                if ($row['domain_contains']) {
                    $conds[] = 'domain contains "' . Enc::html($row['domain_contains']) . '"';
                }
                return ucfirst(implode(' and ', $conds));
            },

            'Subsite' => [new ColModifierLookupTable('subsites'), 'subsite_id'],
            'Destination' => [new ColModifierLnk(), 'destination'],
        ];

        $this->refine_bar = new RefineBar();
        $this->refine_bar->setGroup('Redirect');
        $this->refine_bar->addWidget(new RefineWidgetTextbox('path_exact', 'Path exact match'));
        $this->refine_bar->addWidget(new RefineWidgetTextbox('path_contains', 'Path contains'));

        parent::__construct();
    }


    /**
     * Do any additional validation prior to saving the record
     *
     * @param int $id Record ID or 0 for adds
     * @param Validator $validator Validator instance to attach your errors to
     * @return void
     */
    protected function jsonExtraValidate($id, Validator $validator)
    {
        $validator->multipleCheck(
            ['path_exact', 'path_contains', 'subsite_id', 'domain_contains'],
            'Validity::oneRequired'
        );
    }

}
