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

use Sprout\Helpers\ColModifierContentSubscription;
use Sprout\Helpers\ColModifierDate;
use Sprout\Helpers\Itemlist;
use Sprout\Helpers\RefineBar;
use Sprout\Helpers\RefineWidgetSelect;
use Sprout\Helpers\RefineWidgetTextbox;
use Sprout\Helpers\PhpView;


class ContentSubscriptionAdminController extends ManagedAdminController
{
    protected $controller_name = 'content_subscription';
    protected $friendly_name = 'Content subscriptions';
    protected $main_add = false;
    protected $main_delete = true;
    protected $main_order = 'item.date_added DESC';


    /**
    * Constructor
    **/
    public function __construct()
    {
        parent::__construct();

        $this->main_columns = [
            'Name' => 'name',
            'Email' => 'email',
            'Module' => [new ColModifierContentSubscription(), 'id'],
            'Date' => [new ColModifierDate('d/m/Y - h:i a'), 'date_added'],
        ];

        $this->refine_bar = new RefineBar();
        $this->refine_bar->addWidget(new RefineWidgetTextbox('name', 'Name'));
        $this->refine_bar->addWidget(new RefineWidgetTextbox('email', 'Email'));
    }

    public function _getTools() { return null; }
    public function _getNavigation() { return null; }
    public function _getVisibilityFields() { return []; }

    /**
    * Formats a resultset of items into an Itemlist
    *
    * @param Traversable $items The items to render.
    * @param anything $unused Not used in this controller, but used by has_categories
    **/
    public function _getContentsViewList($items, $unused)
    {
        // Create the itemlist
        $itemlist = new Itemlist();
        $itemlist->main_columns = $this->main_columns;
        $itemlist->items = $items;
        $itemlist->setCheckboxes(true);
        $itemlist->setOrdering(true);
        $itemlist->setActionsClasses('button button-small');

        // Add the actions
        foreach ($this->main_actions as $name => $url) {
            $itemlist->addAction($name, $url, 'button-grey');
        }
        if ($this->getDuplicateEnabled()) {
            $itemlist->addAction('Duplicate', "SITE/admin/duplicate/{$this->controller_name}/%%", 'button-grey icon-before icon-add');
        }
        if ($this->main_delete) {
            $itemlist->addAction('Delete', "SITE/admin/delete/{$this->controller_name}/%%", 'button button-red icon-before icon-delete');
        }

        // Add classes based on visibility fields
        $visibility = $this->_getVisibilityFields();
        $itemlist->setRowClassesFunc(function($row) use($visibility) {
            $out = '';
            foreach ($visibility as $name => $label) {
                $out .= "main-list--{$name}-{$row[$name]} ";
            }
            return rtrim($out);
        });

        // Prepare view which renders the main content area
        $outer = new PhpView("sprout/admin/generic_itemlist_outer");

        // Build the outer view
        $outer->controller_name = $this->controller_name;
        $outer->friendly_name = $this->friendly_name;
        $outer->itemlist = $itemlist->render();
        $outer->allow_add = $this->main_add;
        $outer->allow_del = $this->main_delete;

        return $outer->render();
    }
}
