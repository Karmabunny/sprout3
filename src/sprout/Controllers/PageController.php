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
use Kohana_404_Exception;

use karmabunny\pdb\Exceptions\RowMissingException;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\BaseView;
use Sprout\Helpers\ContentReplace;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\CustomHeadTags;
use Sprout\Helpers\Email;
use Sprout\Helpers\FrontEndSearch;
use Sprout\Helpers\Lnk;
use Sprout\Helpers\Navigation;
use Sprout\Helpers\Needs;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Page;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Profiling;
use Sprout\Helpers\Request;
use Sprout\Helpers\Router;
use Sprout\Helpers\SocialMeta;
use Sprout\Helpers\SocialNetworking;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\SubsiteSelector;
use Sprout\Helpers\Subsites;
use Sprout\Helpers\Tags;
use Sprout\Helpers\Text;
use Sprout\Helpers\TreenodePathMatcher;
use Sprout\Helpers\TreenodeValueMatcher;
use Sprout\Helpers\TwigView;
use Sprout\Helpers\Url;
use Sprout\Helpers\UserPerms;
use Sprout\Helpers\PhpView;
use Sprout\Helpers\Widgets;


/**
 * Handles front-end processing for pages
 */
class PageController extends Controller implements FrontEndSearch
{
    private $navigation_node = null;

    /**
    * 404 error
    **/
    public function fourOhFour($name)
    {
        Profiling::setEnabled(false);
        throw new Kohana_404_Exception('"' . $name . '"');
    }


    /**
    * Displays a page.
    * The page is specified by the page friendly name
    *
    * @param string $name The URL of the page to display
    **/
    public function viewByName($name)
    {
        $root = Navigation::getRootNode();

        $matcher = new TreenodePathMatcher($name);
        $node = $root->findNode($matcher);
        $this->navigation_node = $node;

        if (! $node) {
            throw new Kohana_404_Exception('"' . $name . '"');
        }

        $this->viewById($node['id']);
    }


    /**
    * Displays a page.
    * The page is specified by the page id
    *
    * @param int $id The id of the page to display
    **/
    public function viewById($id)
    {
        $id = (int) $id;

        // Prep where clauses
        $where = [];
        $params = [];
        $where[] = 'pages.id = :page_id';
        $params['page_id'] = $id;
        $where[] = 'pages.active = 1';

        if (! SubsiteSelector::$mobile) {
            $where[] = 'pages.subsite_id = :subsite_id';
            $params['subsite_id'] = SubsiteSelector::$content_id;
        }

        // Do query
        $where = implode(' AND ', $where);
        $q = "SELECT pages.id, pages.name, pages.meta_keywords, pages.meta_description,
                pages.alt_browser_title, revs.id AS rev_id, pages.alt_template, pages.subsite_id,
                pages.additional_css AS has_additional_css,
                pages.gallery_thumb, pages.banner,
                revs.type, revs.redirect, revs.date_modified,
                revs.controller_entrance, revs.controller_argument
            FROM ~pages AS pages
            INNER JOIN ~page_revisions AS revs
                ON revs.page_id = pages.id
                AND revs.status = :status
            WHERE {$where}";
        $params['status'] = 'live';
        try {
            $page = Pdb::q($q, $params, 'row');
        } catch (RowMissingException $ex) {
            throw new Kohana_404_Exception("Page # {$id}");
        }

        $root = Navigation::getRootNode();
        $node = $root->findNodeValue('id', $id);
        $this->navigation_node = $node;

        if (Kohana::config('sprout.page_stats')) {
            $this->trackVisit($id);
        }

        if ($page['type'] == 'redirect') {
            // URL redirect
            Url::redirect(Lnk::url($page['redirect']));

        } else if ($page['type'] == 'tool') {
            // Front-end controller entrance
            $inst = Sprout::instance(
                $page['controller_entrance'],
                ['Sprout\\Controllers\\Controller', 'Sprout\\Helpers\\FrontEndEntrance']
            );

            $conds_env = $this->widgetCondsEnvironment($page);
            $this->loadWidgets($conds_env, $page);

            Router::$controller = $page['controller_entrance'];

            $inst->entrance($page['controller_argument']);

        } else {
            // Standard page
            echo $this->displayPage($page);
        }
    }


    /**
     * Displays a specific revision of a page.
     * @param int $page_id The ID of the page to display
     * @param int $rev_id The ID of the revision to display.
     *        The revision must be live, or the user must be an administrator,
     *        or the correct approval_code for the revision must be provided.
     * @param string $approval_code Code to view the revision without authentication, e.g. via emailed link.
     *        If not provided, the revision must be live, or the user must be logged in as an operator.
     */
    public function viewSpecificRev($page_id, $rev_id, $approval_code = '')
    {
        $page_id = (int) $page_id;
        $rev_id = (int) $rev_id;

        // Fetch revision from DB
        $params = ['page_id' => $page_id, 'rev_id' => $rev_id];
        $code_clause = '';
        if ($approval_code != '') {
            $code_clause = 'AND rev.approval_code = :approval_code';
            $params['approval_code'] = $approval_code;
        }
        $q = "SELECT page.id, page.name, page.meta_keywords, page.meta_description, page.alt_browser_title,
                page.alt_template, page.subsite_id,
                rev.status, rev.id AS rev_id, rev.status, rev.modified_editor, rev.date_modified
            FROM ~pages AS page
            INNER JOIN ~page_revisions AS rev ON page.id = rev.page_id
                AND rev.id = :rev_id {$code_clause}
            WHERE page.id = :page_id";
        try {
            $page = Pdb::q($q, $params, 'row');
        } catch (RowMissingException $ex) {
            throw new Kohana_404_Exception("Page # {$page_id}, rev # {$rev_id}");
        }

        // Verify that the user has rights to view this revision
        if ($approval_code == '' and $page['status'] != 'live') {
            AdminAuth::checkLogin();
        }

        echo $this->displayPage($page, $approval_code);
    }


    /**
    * Rudimentary stats tracking
    **/
    private function trackVisit($page_id)
    {
        try {
            $sect_id = 0;
            if ($this->navigation_node) {
                $anc = $this->navigation_node->findAncestors();
                $sect_id = $anc[0]['id'];
            }

            $q = "INSERT INTO ~page_visits
                SET page_id = ?, section_page_id = ?, date_hits = CURDATE(), num = 1
                ON DUPLICATE KEY UPDATE num = num + 1";
            Pdb::q($q, [$page_id, $sect_id], 'count');
        } catch (Exception $ex) {}
    }


    /**
     * Called by the two display functions.
     * Does the actual display.
     *
     * @param array $page Combined page/revision record from the DB;
     *        see e.g. {@see PageController::viewById} or {@see PageController::viewSpecificRev}
     * @param string $approval_code Approval code, if the page needs to include 'approve' and 'deny' buttons
     *        for the revision being viewed
     * @return string HTML
     */
    private function displayPage(array $page, $approval_code = '')
    {
        if (Request::isAjax()) {
            $page_view_name = 'skin/popup';
        } else if (!empty($page['alt_template'])) {
            $page_view_name = $page['alt_template'];
        } else {
            $page_view_name = 'skin/inner';
        }

        $page_view = BaseView::create($page_view_name);

        // Load navigation
        Navigation::setPageNodeMatcher(new TreenodeValueMatcher('id', $page['id']));
        if (! $this->navigation_node) {
            $this->navigation_node = Navigation::getRootNode()->findNodeValue('id', $page['id']);
        }

        // Page titles
        $page_view->page_title = $page['name'];
        if ($page['alt_browser_title']) {
            $page_view->browser_title = $page['alt_browser_title'];
        } else {
            $page_view->browser_title = Navigation::buildBrowserTitle($page['name']);
        }

        // Load the ancestors for the page node and get the name (and url name) of the top-level ancestor
        if ($this->navigation_node) {
            $anc = $this->navigation_node->findAncestors();
            $page_view->top_level_name = $anc[0]->getNavigationName();
            $page_view->top_level_urlname = $anc[0]->getUrlName();
        }

        // If we don't have access to this page, show a login form
        if (! UserPerms::checkPermissionsTree('pages', $page['id'])) {
            $page_view->main_content = UserPerms::getAccessDenied();
            $_GET['redirect'] = Url::current(true);
            return $page_view->render();
        }

        // Get list of widgets and render their content
        $conds_env = $this->widgetCondsEnvironment($page);
        $this->loadWidgets($conds_env, $page);
        $page_view->main_content = Widgets::renderArea('embedded', true);

        // Inject approval form above content
        if ($page['status'] ?? '' == 'need_approval' and $approval_code) {
            $form_view = new PhpView('sprout/page_approval_form');
            $form_view->rev_id = (int) $page['rev_id'];
            $form_view->code = $approval_code;
            $page_view->main_content = $form_view->render() . $page_view->main_content;

        } else if (isset($page['status']) and @$page['status'] != 'live') {
            // Inject a view with info about the revision
            $info_view = new PhpView('sprout/page_rev_info');
            $info_view->page = $page;
            $page_view->main_content = $info_view->render() . $page_view->main_content;
        }

        SocialNetworking::details($page['name'], $page_view->main_content);
        $this->setSocialMeta($page, $page_view->main_content);

        if (Kohana::config('sprout.tweak_skin') and $page['has_additional_css']) {
            Needs::addCssInclude('SITE/page/additional_css/' . $page['id'] . '/' . strtotime($page['date_modified']) . '.css');
        }

        // Metadata
        if ($page['meta_keywords']) Needs::addMetaName('keywords', $page['meta_keywords']);
        if ($page['meta_description']) Needs::addMetaName('description', $page['meta_description']);

        CustomHeadTags::addHeadTags('pages', $page['id']);

        $page_view->page_attrs = Page::attrs($page['id']);
        $page_view->tags = Tags::byRecord('pages', $page['id']);
        $page_view->controller_name = $this->getCssClassName();
        $page_view->canonical_url = Page::canonicalUrl($page['id']);

        return $page_view->render();
    }


    /**
     * Set the social meta data for a page
     *
     * @param array $page Page details from the database
     * @param string $content_html The rendered content html
     * @return void Sets values in the {@see SocialMeta} helper
     */
    private function setSocialMeta(array $page, $content_html)
    {
        SocialMeta::setTitle($page['name']);

        if (!empty($page['gallery_thumb'])) {
            SocialMeta::setImage($page['gallery_thumb']);
        } else if (!empty($page['banner'])) {
            SocialMeta::setImage($page['banner']);
        } else {
            // Attempt to scrape the first image from the content
            $matches = null;
            if (preg_match('!<img .+?>!', $content_html, $matches)) {
                if (preg_match('!src="([^"]+)"!', $matches[0], $matches)) {
                    SocialMeta::setImage($matches[1]);
                }
            }
        }

        if ($page['meta_description']) {
            SocialMeta::setDescription($page['meta_description']);
        } else {
            $capped = Text::plain($content_html, 20);
            $capped = str_replace(["\r", "\n"], ' ', $capped);
            SocialMeta::setDescription($capped);
        }

        if (!empty($this->navigation_node)) {
            SocialMeta::setUrl($this->navigation_node->getFriendlyUrlNoPrefix());
        }
    }


    /**
    * Makes alterations to the main text content
    **/
    private function textTranslation($page_id, $text)
    {
        $text = ContentReplace::intlinks($text);
        $text = ContentReplace::embedWidgets($text, 'page', $page_id);
        $text = ContentReplace::localanchor($text);
        return $text;
    }


    /**
     * Return the environment which is provided to the widget display conditions logic
     *
     * @param array $page Database record
     * @return array Environment which gets passed to {@see Widgets::checkDisplayConditions}
     */
    private function widgetCondsEnvironment(array $page)
    {
        return [
            'page_id' => $page['id'],
        ];
    }


    /**
    * Loads the widgets from the database for this page.
    *
    * @param array $page The page to load widgets from
    **/
    private function loadWidgets(array $conds_env, array $page)
    {
        $q = "SELECT area_id, type, settings, conditions, heading, template, columns
            FROM ~page_widgets
            WHERE page_revision_id = ? AND active = 1
            ORDER BY area_id, record_order";
        $wids = Pdb::q($q, [$page['rev_id']], 'arr');

        foreach ($wids as $widget) {
            $settings = json_decode($widget['settings'], true);

            $conditions = json_decode($widget['conditions'], true);
            if (!empty($conditions)) {
                $result = Widgets::checkDisplayConditions($conds_env, $conditions);
                if (!$result) {
                    continue;
                }
            }

            Widgets::add($widget['area_id'], $widget['type'], $settings, $widget['heading'], $widget['template'], $widget['columns']);
        }
    }


    /**
    * Gets the additional CSS for a page, if it has any
    **/
    public function additionalCss($page_id, $junk = null)
    {
        $page_id = (int) $page_id;

        $q = "SELECT additional_css FROM ~pages WHERE id = ?";
        $row = Pdb::q($q, [$page_id], 'row');

        header('Content-type: text/css; charset=UTF-8');
        echo $row['additional_css'];
    }


    /**
    * Process the results of a search.
    *
    * @param array $row A single row of data to output
    * @return string The result string
    **/
    public function frontEndSearch($item_id, $relevancy, $keywords)
    {
        $root = Navigation::getRootNode();
        $node = $root->findNodeValue('id', $item_id);
        if (! $node) return false;

        $name = $node->getNavigationName();
        $url = $node->getFriendlyUrl();

        // Collate widgets to produce page text
        $text = Page::getText($item_id);

        $text = Text::plain($text, 0);
        $text = substr($text, 0, 5000);

        if ($text == '') return false;

        // Look for the first keyword in the text
        $pos = 5000;
        $matches = null;
        foreach ($keywords as $k) {
            $k = preg_quote($k);
            if (preg_match("/(^|\W){$k}($|\W)/i", $text, $matches, PREG_OFFSET_CAPTURE)) {
                $pos = min($pos, $matches[0][1]);
            }
        }

        // If anything was found in first 5000 chars, show that bit
        if ($pos < 5000) {
            $pos -= 10;
            if ($pos > 1) {
                $text = '...' . substr($text, $pos);
            }
        }

        // Limit to something more reasonable
        $text = Text::limitWords($text, 40, '...');

        // Bolden keywords
        foreach ($keywords as $k) {
            $k = preg_quote($k);
            $name = preg_replace("/(^|\W)({$k})($|\W)/i", '$1<b>$2</b>$3', $name);
            $text = preg_replace("/(^|\W)({$k})($|\W)/i", '$1<b>$2</b>$3', $text);
        }

        $view = new PhpView('sprout/search_results_page');
        $view->name = $name;
        $view->url = $url;
        $view->text = $text;
        $view->relevancy = $relevancy;

        return $view->render();
    }


    /**
     * Action for reviewing a page - either approves or rejects the revision
     */
    function review($rev_id)
    {
        Csrf::checkOrDie();

        $rev_id = (int) $rev_id;
        $code = (string) ($_POST['code'] ?? '');
        $do = $_POST['do'] ?? '';

        if ($do == 'approve') {
            $approve = true;
        } else if ($do == 'reject') {
            $approve = false;
        } else {
            Notification::error('Unknown action');
            Url::redirect();
        }

        if ($code == '') {
            Notification::error('Invalid approval code');
            Url::redirect();
        }

        try {
            $q = "SELECT rev.id, rev.status, rev.page_id, page.name AS page_name, page.subsite_id,
                    op.id AS op_id, op.email, op.name AS op_name
                FROM ~page_revisions AS rev
                INNER JOIN ~pages AS page ON rev.page_id = page.id
                LEFT JOIN ~operators AS op ON rev.operator_id = op.id
                WHERE rev.id = ? AND rev.approval_code = ?";
            $rev = Pdb::q($q, [$rev_id, $code], 'row');
        } catch (RowMissingException $ex) {
            Notification::error('Invalid revision or approval code');
            Url::redirect();
        }

        if ($approve and $rev['status'] == 'live') {
            Notification::confirm('Revision is already live');
            Url::redirect(Page::url($rev['page_id']));
        } else if ($rev['status'] != 'need_approval') {
            Notification::error('Revision is not awaiting approval');
            Url::redirect();
        }

        if ($approve) {
            Pdb::transact();
            Page::activateRevision($rev_id);
            Pdb::commit();

            // N.B. Fetch URL after updating DB, as slugs may change with revisions
            $url = Subsites::getAbsRoot($rev['subsite_id']) . Page::url($rev['page_id']);

            // Send an email to the operator who requested the change
            if ($rev['op_id'] > 0) {
                $view = new PhpView('sprout/email/page_approved');
                $view->addressee = preg_replace('/\s.*/', '', trim($rev['op_name']));
                $view->page_name = $rev['page_name'];
                $view->url = $url;
                $view->message = @$_POST['message'];

                $mail = new Email();
                $mail->AddAddress($rev['email']);
                $mail->Subject = 'Page change approved on ' . Kohana::config('sprout.site_title');
                $mail->SkinnedHTML($view->render());
                $mail->Send();
            }

            // TODO: add history of approval
            Notification::confirm('Revision is now live');
            Url::redirect($url);
        } else {
            Pdb::update('page_revisions', ['status' => 'rejected'], ['id' => $rev_id]);

            $url = Subsites::getAbsRoot($rev['subsite_id']) . Page::url($rev['page_id']);

            // Send an email to the operator who requested the change
            if ($rev['op_id'] > 0) {
                $view = new PhpView('sprout/email/page_rejected');
                $view->addressee = preg_replace('/\s.*/', '', trim($rev['op_name']));
                $view->page_name = $rev['page_name'];
                $view->url = $url;
                $view->message = @$_POST['message'];

                $mail = new Email();
                $mail->AddAddress($rev['email']);
                $mail->Subject = 'Page change rejected on ' . Kohana::config('sprout.site_title');
                $mail->SkinnedHTML($view->render());
                $mail->Send();
            }

            // TODO: add history of denial
            Notification::confirm('Revision has been rejected');
            Url::redirect($url);
        }
    }

}
