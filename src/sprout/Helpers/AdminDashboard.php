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

use karmabunny\pdb\Exceptions\QueryException;
use karmabunny\pdb\Pdb as KbPdb;


/**
 * Dashboard shown when a user first logs in to the admin
 */
class AdminDashboard
{

    /**
     * Render the admin dashboard
     *
     * @return string HTML
     */
    public static function render()
    {
        $out = '';
        $out .= self::firstRun();

        if (AdminPerms::canAccess('access_noapproval')) {
            $out .= self::needApproval();
        }

        $out .= self::moderationButton();
        $out .= self::newContent();

        return $out;
    }


    /**
     * Show the "first run" message, which welcomes new operators to the admin
     *
     * @return string HTML
     */
    private static function firstRun()
    {
        if (!AdminAuth::hasDatabaseRecord()) {
            return '';
        }

        $q = "SELECT firstrun FROM ~operators WHERE id = ?";
        $firstrun = Pdb::query($q, [AdminAuth::getId()], 'val');
        if ($firstrun !== '1') {
            return '';
        }

        $view = new PhpView('sprout/admin/dashboard/first_run');
        return $view->render();
    }


    /**
     * A list of new content (pages, files, etc)
     *
     * @return string HTML
     */
    private static function newContent()
    {
        $now = Pdb::quote(Pdb::now(), KbPdb::QUOTE_VALUE);

        // New content
        $tables = array();
        $tables['Page'] = 'pages';
        $tables['File'] = 'files';

        $q = [];
        $params = [];
        foreach ($tables as $name => $t) {
            $controller = Inflector::singular($t);
            $where = ($t == 'files' ? "t.name != ''" : '1');
            $q[] = "(
                SELECT CONCAT(?, t.id) AS id, t.name, '{$name}' AS type, DATE_FORMAT(t.date_added, '%W %D') AS d, t.date_added
                FROM ~{$t} AS t
                WHERE t.date_added > DATE_SUB({$now}, INTERVAL 1 WEEK)
                  AND {$where}
                ORDER BY t.date_added DESC
                LIMIT 5
            )";
            $params[] = "SITE/admin/edit/{$controller}/";
        }

        $q = implode (' UNION ', $q);
        $q .= ' ORDER BY date_added DESC LIMIT 20';
        try {
            $res = Pdb::query($q, $params, 'arr');
        } catch (QueryException $ex) {
            // Assume DB has no tables
            $res = [];
        }

        if (count($res) === 0) {
            return '';
        }

        // Create the itemlist
        $itemlist = new Itemlist();
        $itemlist->main_columns = array('Type' => 'type', 'Name' => 'name', 'Added' => 'd');
        $itemlist->items = $res;
        $itemlist->addAction('edit', '%ne%');

        return '<h3>New content</h3>' . $itemlist->render();
    }


    /**
     * A list of "need approval" pages currently in the system
     *
     * @return string HTML
     */
    private static function needApproval()
    {
        $q = "SELECT pages.id, pages.name, DATE_FORMAT(page_revisions.date_modified, '%d/%m/%Y') AS date_modified, page_revisions.modified_editor
            FROM ~page_revisions AS page_revisions
            INNER JOIN ~pages AS pages ON page_revisions.page_id = pages.id
            WHERE page_revisions.status = ? AND subsite_id = ?
            GROUP BY pages.id
            ORDER BY page_revisions.date_modified DESC
            LIMIT 5";
        $params = ['need_approval', $_SESSION['admin']['active_subsite']];
        try {
            $res = Pdb::query($q, $params, 'arr');
        } catch (QueryException $ex) {
            // Assume DB has no tables
            $res = [];
        }

        if (count($res) === 0) {
            return '';
        }

        // Create the itemlist
        $itemlist = new Itemlist();
        $itemlist->main_columns = array('Name' => 'name', 'Date modified' => 'date_modified', 'Editor' => 'modified_editor');
        $itemlist->items = $res;
        $itemlist->addAction('edit', 'SITE/admin/edit/page/%%#main-tabs-revs');

        return '<h3>Pages needing approval</h3>' . $itemlist->render();
    }


    /**
     * A button to moderate content, if there is actually content to moderate
     *
     * @return string HTML
     */
    private static function moderationButton()
    {
        $has_moderation = false;

        $moderators = Register::getModerators();
        foreach ($moderators as $class) {
            $inst = new $class;
            if (! $inst) continue;
            if (! $inst instanceof Moderate) continue;

            $list = $inst->getList();
            if ($list === null) continue;

            if (count($list) > 0) {
                $has_moderation = true;
                break;
            }
        }

        if (!$has_moderation) {
            return '';
        }

        $view = new PhpView('sprout/admin/dashboard/moderation_button');
        return $view->render();
    }

}
