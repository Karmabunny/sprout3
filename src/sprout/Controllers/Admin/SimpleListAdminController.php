<?php
/*
 */

namespace Sprout\Controllers\Admin;

use Sprout\Helpers\Admin;
use Sprout\Helpers\Router;
use Sprout\Helpers\PhpView;


/**
* Any controller which is essentially a short list of items, which are not substantial enough
* to warrant a categories system
*
* Required fields for a simple list controller table:
*   id
**/
abstract class SimpleListAdminController extends ListAdminController
{
    protected $add_defaults = [];
    protected $main_columns = [];
    protected $main_order = 'item.date_added DESC';

    /**
    * Constructor
    **/
    public function __construct()
    {
        parent::__construct();
    }


    /** @inheritdoc */
    public function _getVisibilityFields()
    {
        return [];
    }


    /**
    * Returns the contents of the navigation pane for the list
    **/
    public function _getNavigation()
    {
        $items = [];

        $view = new PhpView('sprout/admin/list_navigation');
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;
        $view->items = $items;
        $view->allow_add = $this->main_add;
        $view->record_id = (int) Admin::getRecordId();

        if (Router::$method == 'contents' or Router::$method == 'export') {
            $view->export_refine = '?' . $_SERVER['QUERY_STRING'];
        } else {
            $view->export_refine = '';
        }

        return $view->render();
    }
}
