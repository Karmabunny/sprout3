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

use Sprout\Helpers\FrontEndSearch;
use Sprout\Helpers\Navigation;
use Sprout\Helpers\Register;
use Sprout\Helpers\Search;
use Sprout\Helpers\SearchHandler;
use Sprout\Helpers\View;


/**
 * Performs site-wide searches
 */
class SearchController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Performs a site-wide search for the 'q' term specified via the query string.
     * Basically a wrapper for the Search::query() method
     * @get string q The search query
     * @get int page Page number, for results pagination
     * @return void Outputs HTML directly, containing the search results
     */
    public function index()
    {
        $config_handlers = Register::getSearchHandlers();

        if (! isset($_GET['page'])) $_GET['page'] = 1;
        $_GET['page'] = (int) $_GET['page'];

        // Nonsensical page numbers -> force to 1
        if ($_GET['page'] < 1) $_GET['page'] = 1;

        $search_handlers = array();
        foreach ($config_handlers as $ch) {
            if (is_array($ch)) {
                $search_handlers[] = new SearchHandler($ch[0], $ch[1]);
            } else if ($ch instanceof SearchHandler) {
                $search_handlers[] = $ch;
            } else {
                throw new Exception("Invalid SearchHandler registered");
            }
        }

        $_GET['q'] = trim(@$_GET['q']);

        if ($_GET['q']) {
            $search_result = Search::query($_GET['q'], $search_handlers, $_GET['page'] - 1);
        } else {
            $search_result = null;
        }

        if (! $search_result) {
            // No valid keywords specified
            $page_view = new View('skin/inner');
            $page_view->page_title = 'Search';
            $page_view->main_content = '<div class="site-search-form">' . new View('sprout/search_form') . '</div>';

            echo $page_view->render();
            return;
        }

        list ($res, $keywords, $num_results, $num_pages) = $search_result;

        if ($res->rowCount() == 0) {
            $out = '<p>No results were found which match your search terms.</p>';

        } else {
            // Instantiate search handlers
            $handler_inst = array();
            foreach ($search_handlers as $handler) {
                $class = $handler->getCtlrName();

                $ctlr = new $class;
                if (! $ctlr instanceof FrontEndSearch) throw new Exception("Search handler {$class} does not implement FrontEndSearch");
                $handler_inst[$class] = $ctlr;
            }

            // Iterate through results, passing the rendering task off to the appropriate controller
            $out = '';
            $classes = array();
            foreach ($res as $row) {
                $ctlr = $handler_inst[$row['controller_class']];

                $resp = $ctlr->frontEndSearch($row['record_id'], $row['relevancy'], $keywords);
                if (! $resp) continue;

                $out .= '<div class="search-result">' . $resp . '</div>';
            }
        }
        $res->closeCursor();

        $out .= Search::paginate($_GET['page'], $num_pages, 'search-paginate');

        $page_view = new View('skin/inner');
        $page_view->page_title = 'Search';
        $page_view->browser_title = Navigation::buildBrowserTitle('Search');
        $page_view->main_content = '<div class="site-search-form">' . new View('sprout/search_form') . '</div>' . $out;
        $page_view->controller_name = $this->getCssClassName();

        echo $page_view->render();
    }

}
