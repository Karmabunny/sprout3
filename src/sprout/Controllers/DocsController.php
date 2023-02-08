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

use Dompdf\Dompdf;
use Sprout\Controllers\Controller;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Needs;
use Sprout\Helpers\PhpView;
use Sprout\Helpers\ProjectDocs;

/**
 * Project docs.
 */
class DocsController extends Controller
{


    public function __construct()
    {
        parent::__construct();
        AdminAuth::checkLogin();
    }


    /**
     * Intro page.
     *
     * > /admin/docs
     */
    public function index()
    {
        $view = new PhpView('sprout/admin/docs_intro');

        $skin = new PhpView('sprout/admin/main_layout');
        $skin->nav = $this->buildNav();
        $skin->main_title = 'Documentation';
        $skin->browser_title = 'Documentation';
        $skin->controller_name = 'docs';
        $skin->controller_navigation_name = 'docs';
        $skin->live_url = '';
        $skin->nav_tools = [];

        $skin->main_content = $view->render();
        echo $skin->render();
    }


    /**
     * Export a document as a PDF.
     *
     * > /admin/docs/export/{path}
     *
     * Specify ?debug=1 for obvious reasons.
     */
    public function pdf(...$path)
    {
        $path = implode('/', $path);
        $view = ProjectDocs::renderDoc($path);

        $title = ProjectDocs::parseTitle($view);
        $filename = Enc::urlname($title) . '.pdf';

        $skin = new PhpView('sprout/admin/docs_pdf');
        $skin->title = $title;
        $skin->main_content = $view;

        if ($_GET['debug'] ?? false) {
            echo $skin->render();
            exit;
        }

        // Create PDF
        set_time_limit(120);
        $dompdf = new Dompdf();
        $dompdf->setBasePath(DOCROOT);
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->loadHtml($skin->render());
        $dompdf->render();
        $dompdf->stream($filename);
    }


    /**
     * View a document.
     *
     * > admin/docs/view/{path}
     */
    public function view(...$path)
    {
        $path = implode('/', $path);
        $view = ProjectDocs::renderDoc($path);
        $title = ProjectDocs::parseTitle($view);

        $export_url = Enc::html('admin/docs/export/' . $path);

        Needs::fileGroup('sprout/admin_docs');

        $skin = new PhpView('sprout/admin/main_layout');
        $skin->nav = $this->buildNav();
        $skin->main_title = $title;
        $skin->browser_title = "{$title} | Documentation";
        $skin->controller_name = 'docs';
        $skin->controller_navigation_name = 'docs';
        $skin->live_url = '';
        $skin->nav_tools = [
            "<li><a class='tool' href='{$export_url}'>Export as PDF</a></li>",
        ];

        $skin->main_content = $view;
        echo $skin->render();
    }



    /**
     * Build a list of documents.
     *
     * @return string HTML
     */
    private function buildNav(): string
    {
        ob_start();

        $docs = ProjectDocs::listDocs();

        if (empty($docs)) {
            echo '<p>No documentation at this time</p>';
        }

        echo '<ul class="list-style-1">';

        foreach ($docs as $doc) {
            echo "<li><a href='admin/docs/view/{$doc}'>{$doc}</a></li>";
        }

        echo '</ul>';

        return ob_get_clean() ?: '';
    }
}


