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

namespace Sprout\Controllers;

use Exception;

use Kohana;

use Sprout\Helpers\BaseView;
use Sprout\Helpers\Enc;
use Sprout\Helpers\FrontEndEntrance;
use Sprout\Helpers\FrontEndSearch;
use Sprout\Helpers\Navigation;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Register;
use Sprout\Helpers\Search;
use Sprout\Helpers\SearchHandler;
use Sprout\Helpers\Tags;
use Sprout\Helpers\TreenodeFrontendMatcher;
use Sprout\Helpers\PhpView;


/**
* - No description yet -
**/
class AdvancedSearchController extends Controller implements FrontEndEntrance
{

    /**
    * Constructor
    **/
    public function __construct()
    {
        parent::__construct();
    }


    /**
    * Acts as the entrance-point of the controller.
    * Redirects to the target page
    **/
    public function entrance($argument)
    {
        $this->index();
    }

    /**
    * Return an array of valid arguments to the entrance of this controller.
    *
    * @return array The valid arguments, in key-value pairs
    **/
    public function _getEntranceArguments()
    {
        return array('form' => 'Search form');
    }


    /**
    * Does the actual search
    **/
    public function index()
    {
        Navigation::setPageNodeMatcher(new TreenodeFrontendMatcher('advanced_search', 'form'));

        $view = $this->doSearch();

        // Page title
        $page_title = 'Search';
        $page_node = Navigation::matchedNode();
        if ($page_node) {
            $page_title = $page_node->getNavigationName();
        }

        // Prepare the view
        $page_view = BaseView::create('skin/inner');
        $page_view->page_title = $page_title;
        $page_view->main_content = $view;
        $page_view->controller_name = 'user';

        echo $page_view->render();
    }


    /**
    * Does the actual search. Returns HTML
    **/
    private function doSearch()
    {


        // Build typelist
        $config_handlers = Register::getSearchHandlers();
        $avail_types = array();
        foreach ($config_handlers as $ch) {
            if (! $ch instanceof SearchHandler) throw new Exception("Invalid SearchHandler registered");

            $avail_types[$ch->getMainTable()] = ucwords($ch->getMainTable());
        }


        // Check input GET
        if (! isset($_GET['page'])) $_GET['page'] = 1;
        $_GET['page'] = (int) $_GET['page'];

        $_GET['q'] = trim(Enc::cleanfunky(@$_GET['q']));
        $_GET['tag'] = trim(preg_replace('/[^-a-z0-9 ,]/', '', $_GET['tag'] ?? ''));
        $_GET['date'] = trim(Enc::cleanfunky(@$_GET['date']));

        if (empty($_GET['q_type'])) $_GET['q_type'] = 'or';
        if (empty($_GET['tag_type'])) $_GET['tag_type'] = 'or';

        if (empty($_GET['type']) or !is_array($_GET['type'])) {
            $_GET['type'] = [];
        }
        foreach ($_GET['type'] as $idx => $val) {
            if (!isset($avail_types[$val])) unset($_GET['type'][$idx]);
        }

        if (empty($_GET['type'])) {
            $_GET['type'] = array_keys($avail_types);
        }


        $srchform = new PhpView('sprout/advanced_search_form');
        $srchform->avail_types = $avail_types;
        $srchform = $srchform->render();


        if (!$_GET['q'] and !$_GET['tag'] and !$_GET['date']) {
            return $srchform;
        }


        // Work out the WHERE for tags
        if ($_GET['tag']) {
            $conn = Pdb::getConnection();
            $tags = Tags::splitupTags($_GET['tag']);
            $tagwhere = array();
            foreach ($tags as $t) {
                $tagwhere[] = $conn->quote($t);
            }
        }

        // Prep handlers
        $search_handlers = array();
        foreach ($_GET['type'] as $type) {
            $ch = null;
            foreach ($config_handlers as $ch) {
                if ($ch->getMainTable() == $type) break;
            }
            if ($ch === null) continue;

            if ($_GET['tag']) {
                $ch->addJoin("INNER JOIN ~tags AS tags ON tags.record_table = '{$ch->getMainTable()}' AND tags.record_id = main.id");

                $ch->addWhere('tags.name IN (' . implode(', ', $tagwhere) . ')');

                if ($_GET['tag_type'] == 'and') {
                    $ch->addHaving('COUNT(tags.name) = ' . count($tagwhere));
                }
            }

            $where = $this->dateWhere($_GET['date']);
            if ($where) $ch->addWhere($where);

            $search_handlers[] = $ch;
        }


        // Do the search
        Search::queryAnd($_GET['q_type'] == 'and');
        $search_result = Search::query($_GET['q'], $search_handlers, $_GET['page'] - 1);

        if (! $search_result) {
            return $srchform;
        }

        list ($res, $keywords, $num_results, $num_pages) = $search_result;

        if ($res->rowCount() == 0) {
            return $srchform . '<p>No results were found matching your search terms.</p>';
        }


        // Instantiate search handlers
        $handler_inst = array();
        foreach ($search_handlers as $handler) {
            $class = $handler->getCtlrName();

            $ctlr = @new $class;
            if (! $ctlr instanceof FrontEndSearch) throw new Exception("Search handler {$class} does not implement FrontEndSearch");
            $handler_inst[$class] = $ctlr;
        }

        if (empty($_GET['fullform'])) {
            $srchform = new PhpView('sprout/advanced_search_form');
            $srchform->avail_types = $avail_types;
            $srchform = $srchform->render();
        }

        $out = $srchform;

        // Iterate through results, passing the rendering task off to the appropriate controller
        foreach ($res as $row) {

            $ctlr = $handler_inst[$row['controller_class']];

            $out .= '<div class="search-result">';
            $out .= $ctlr->frontEndSearch($row['record_id'], $row['relevancy'], $keywords);
            $out .= '</div>';
        }

        $out .= Search::paginate($_GET['page'], $num_pages, 'search-paginate');

        return $out;
    }


    /**
    * Take the datespec on the key side of Constants::$relative_dates and turn into a WHERE clause
    **/
    private function dateWhere($date)
    {
        $date = trim($date);
        if (! $date) return null;
        if (! preg_match('!^([no])([0-9]+)$!', $date, $matches)) return null;

        if ($matches[1] == 'n') {
            return "main.date_modified > DATE_SUB(NOW(), INTERVAL {$matches[2]} MONTH)";
        } else if ($matches[1] == 'o') {
            return "main.date_modified < DATE_SUB(NOW(), INTERVAL {$matches[2]} MONTH)";
        }
    }


    /**
    * Provides data for the tag autocomplete
    **/
    public static function tagAutocomplete()
    {
        header('Content-type: text/plain');
        echo json_encode(Tags::beginsWith($_GET['q']));
    }
}
