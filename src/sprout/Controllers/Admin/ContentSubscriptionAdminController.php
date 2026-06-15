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


    /** @inheritdoc */
    public static function _getContentPermissionGroups(): array
    {
        return [];
    }


    public function _getTools() { return null; }
    public function _getNavigation() { return null; }
    public function _getVisibilityFields() { return []; }


    /** @inheritDoc */
    public function _contentsItemlistPreRender(Itemlist $itemlist): void
    {
        parent::_contentsItemlistPreRender($itemlist);
        $itemlist->removeAction('edit');
    }


    /**
    * Formats a resultset of items into an Itemlist
    *
    * @param \Traversable|array $items The items to render.
    * @param mixed $unused Not used in this controller, but used by has_categories
    **/
    public function _getContentsViewList($items, $unused)
    {
        // Create the itemlist
        $itemlist = new Itemlist();
        $itemlist->main_columns = $this->main_columns;
        $itemlist->items = $items;
        $this->_contentsItemlistPreRender($itemlist);

        // Prepare view which renders the main content area
        $outer = new PhpView("sprout/admin/generic_itemlist_outer");
        $outer->selected_tools = $this->_getSelectedTools();

        // Build the outer view
        $outer->controller_name = $this->controller_name;
        $outer->friendly_name = $this->friendly_name;
        $outer->itemlist = $itemlist->render();
        $outer->allow_add = $this->main_add;
        $outer->allow_del = $this->main_delete;

        return $outer->render();
    }
}
