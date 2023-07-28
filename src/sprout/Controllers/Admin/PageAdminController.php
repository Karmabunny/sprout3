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

use DOMDocument;
use Exception;

use Kohana;

use Sprout\Controllers\PageController;
use karmabunny\pdb\Exceptions\QueryException;
use karmabunny\pdb\Exceptions\RowMissingException;
use Sprout\Exceptions\ValidationException;
use Sprout\Exceptions\WorkerJobException;
use Sprout\Helpers\Admin;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\AdminError;
use Sprout\Helpers\AdminPerms;
use Sprout\Helpers\AdminSeo;
use Sprout\Helpers\Category;
use Sprout\Helpers\ColModifierDate;
use Sprout\Helpers\Constants;
use Sprout\Helpers\Cron;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\CustomHeadTags;
use Sprout\Helpers\DocImport\DocImport;
use Sprout\Helpers\Email;
use Sprout\Helpers\Enc;
use Sprout\Helpers\File;
use Sprout\Helpers\FileConstants;
use Sprout\Helpers\FileTransform;
use Sprout\Helpers\FileUpload;
use Sprout\Helpers\Form;
use Sprout\Helpers\FrontEndEntrance;
use Sprout\Helpers\Inflector;
use Sprout\Helpers\Itemlist;
use Sprout\Helpers\Json;
use Sprout\Helpers\Lnk;
use Sprout\Helpers\MultiEdit;
use Sprout\Helpers\Navigation;
use Sprout\Helpers\NavigationGroups;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Page;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Preview;
use Sprout\Helpers\RefineBar;
use Sprout\Helpers\RefineWidgetTextbox;
use Sprout\Helpers\Register;
use Sprout\Helpers\Search;
use Sprout\Helpers\Security;
use Sprout\Helpers\Slug;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\Subsites;
use Sprout\Helpers\TinyMCE4RichText;
use Sprout\Helpers\Treenode;
use Sprout\Helpers\Upload;
use Sprout\Helpers\Url;
use Sprout\Helpers\UserPerms;
use Sprout\Helpers\Validator;
use Sprout\Helpers\PhpView;
use Sprout\Helpers\WidgetArea;
use Sprout\Helpers\WorkerCtrl;


/**
 * Handles admin processing for pages
 */
class PageAdminController extends TreeAdminController
{
    protected $friendly_name = 'Pages';
    protected $main_delete = true;
    protected $edit_type;
    protected $in_preview;


    /**
    * Constructor
    **/
    public function __construct()
    {
        $this->refine_bar = new RefineBar();
        $this->refine_bar->setGroup('Pages');
        $this->refine_bar->addWidget(new RefineWidgetTextbox('name', 'Name'));

        $this->refine_bar->setGroup('Page content');
        $this->refine_bar->addWidget(new RefineWidgetTextbox('_keyword', 'Keyword search'));
        $this->refine_bar->addWidget(new RefineWidgetTextbox('_phrase', 'Exact phrase'));

        parent::__construct();

        $this->main_columns = [
            'Name' => 'name',
            'Added' => [new ColModifierDate('g:ia d/m/Y'), 'date_added']
        ];
    }


    /**
     * Pages controller provides it's own per-record permissions.
     *
     * @inheritdoc
     */
    public static function _getContentPermissionGroups(): array
    {
        $permissions = parent::_getContentPermissionGroups();
        unset($permissions['record']);
        return $permissions;
    }


    /**
     * Return the WHERE clause to use for a given key which is provided by the RefineBar
     *
     * Allows custom non-table clauses to be added.
     * Is only called for key names which begin with an underscore.
     * The base table is aliased to 'item'.
     *
     * @param string $key The key name, including underscore
     * @param string $val The value which is being refined.
     * @param array &$query_params Parameters to add to the query which will use the WHERE clause
     * @return string WHERE clause, e.g. "item.name LIKE CONCAT('%', ?, '%')", "item.status IN (?, ?, ?)"
     */
    protected function _getRefineClause($key, $val, array &$query_params)
    {

        switch ($key) {
            case '_keyword':
                $query_params[] = Pdb::likeEscape($val);
                return "item.id IN (SELECT record_id FROM ~page_keywords AS pk
                    INNER JOIN ~search_keywords AS k ON pk.keyword_id = k.id WHERE k.name LIKE ?)";

            case '_phrase':
                $query_params[] = 'live';
                $query_params[] = Pdb::likeEscape($val);
                return "item.id IN (SELECT page_id FROM ~page_revisions AS rev
                    WHERE rev.status = ? AND rev.text LIKE CONCAT('%', ?, '%'))";
        }

        return parent::_getRefineClause($key, $val, $query_params);
    }


    /**
    * This is called after every add, edit and delete, as well as other (i.e. bulk) actions.
    * Use it to clear any frontend caches. The default is an empty method.
    *
    * @param string $action The name of the action (e.g. 'add', 'edit', 'delete', etc)
    * @param int $item_id The item which was affected. Bulk actions (e.g. reorders) will have this set to NULL.
    **/
    public function _invalidateCaches($action, $item_id = null)
    {
        Navigation::clearCache();
    }


    /**
    * Returns the contents of the navigation pane for the tree
    **/
    public function _getNavigation()
    {
        $nodes_string = '';
        if (!empty($_SESSION['admin'][$this->controller_name . '_nav'])) {
            $nodes_string = "'" . implode ("', '", $_SESSION['admin'][$this->controller_name . '_nav']) . "'";
        }

        $q = "SELECT id FROM ~homepages WHERE subsite_id = ?";

        $view = new PhpView('sprout/admin/page_navigation');
        $view->home_page_id = Pdb::q($q, [$_SESSION['admin']['active_subsite']], 'val');
        $view->nodes_string = $nodes_string;
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;
        $view->record_id = Admin::getRecordId();
        $view->root = Navigation::getRootNode();

        return $view->render();
    }


    public function _getCustomAddSaveHTML()
    {
        $view = new PhpView('sprout/admin/page_add_save');
        return $view->render();
    }


    /**
     * Return the sub-actions for adding; for spec {@see AdminController::renderSubActions}
     * @return array
     */
    public function _getAddSubActions()
    {
        $actions = parent::_getAddSubActions();
        // Add your actions here, like this: $actions[] = [ ... ];

        if ($_GET['type'] ?? '' == 'tool') {
            $actions[] = [
                'url' => 'admin/add/page',
                'name' => 'Add a standard page',
            ];
        } else {
            $actions[] = [
                'url' => 'admin/add/page?type=tool',
                'name' => 'Add a tool page',
            ];
        }

        return $actions;
    }


    /**
    * Returns the add form for adding a page
    *
    * @return array|AdminError The HTML code & title which represents the add form
    **/
    public function _getAddForm()
    {
        // Defaults
        $data = array(
            'active' => 1,
            'show_in_nav' => 1,
            'admin_perm_type' => Constants::PERM_INHERIT,
            'parent_id' => (int) @$_GET['parent_id'],
            'status' => 'live',
            'admin_perm_specific' => 0,
            'admin_permissions' => [],
            'user_perm_specific' => 0,
            'user_permissions' => [],
        );

        if (! AdminPerms::canAccess('access_noapproval')) {
            $data['status'] = 'need_approval';
        }

        // Fields in session
        if (!empty($_SESSION['admin']['field_values'])) {
            $data = $_SESSION['admin']['field_values'];
            unset ($_SESSION['admin']['field_values']);
        }

        $errors = [];
        if (!empty($_SESSION['admin']['field_errors'])) {
            $errors = $_SESSION['admin']['field_errors'];
            unset($_SESSION['admin']['field_errors']);
        }

        $templates = Subsites::getConfigAdmin('skin_views');
        if (! $templates) $templates = array('skin/inner' => 'Inner');

        // Controller list for entrance dropdown
        $front_end_controllers = Register::getFrontEndControllers();
        asort($front_end_controllers);

        // Load entrance arguments
        $controller_arguments = [];
        if (!empty($data['controller_entrance'])) {
            $inst = Sprout::instance($data['controller_entrance']);
            if ($inst instanceof FrontEndEntrance) {
                $controller_arguments = $inst->_getEntranceArguments();
                if (empty($controller_arguments)) {
                    $controller_arguments = ['' => '- Nothing available -'];
                }
            }
        }

        $title = 'Add a page';
        if ($_GET['type'] ?? '' == 'tool') {
            $data['type'] = 'tool';
            $title = 'Add a tool page';
        } else {
            $data['type'] = 'standard';
        }
        $view = new PhpView('sprout/admin/page_add');

        $view->data = $data;
        $view->errors = $errors;
        $view->admin_category_options = AdminAuth::getAllCategories();
        $view->user_category_options = UserPerms::getAllCategories();
        $view->front_end_controllers = $front_end_controllers;
        $view->controller_arguments = $controller_arguments;
        $view->templates = $templates;

        return array(
            'title' => $title,
            'content' => $view->render()
        );
    }

    /**
    * Saves the provided POST data into a new page in the database
    *
    * @param int $page_id After saving, the new record id will be returned in this parameter
    * @return bool True on success, false on failure
    **/
    public function _addSave(&$page_id)
    {
        // Boolean values
        $_POST['admin_perm_specific'] = (empty($_POST['admin_perm_specific']) ? 0 : 1);
        $_POST['active'] = (empty($_POST['active']) ? 0 : 1);
        $_POST['show_in_nav'] = (empty($_POST['show_in_nav']) ? 0 : 1);

        // Checkbox sets
        if (!isset($_POST['user_permissions'])) $_POST['user_permissions'] = [];

        $valid = new Validator($_POST);
        $valid->required(['name']);
        $valid->check('name', 'Validity::length', 1, 200);
        $valid->check('meta_description', 'Validity::length', 0, 200);

        // HACK: we should attempt to generate a unique slug rather than just failing out
        try {
            $conds = [
                'subsite_id' => $_SESSION['admin']['active_subsite'],
                'parent_id' => (int)@$_POST['parent_id']
            ];
            Slug::unique(Enc::urlname((string)@$_POST['name'], '-'), 'pages', $conds);
        } catch (ValidationException $exp) {
            $valid->addFieldError('name', 'this will result in a conflicting URL, please try another.');
        }

        if ($_POST['type'] == 'tool') {
            // Validate fields specific to tool pages
            $valid->required(['controller_entrance']);
            $valid->check('controller_entrance', 'Validity::length', 1, 200);
            if (!self::checkControllerEntrance($_POST['controller_entrance'], $page_id)) {
                $valid->addFieldError('controller_entrance', 'Invalid value');
            }

            // Tell core AdminController the right URL to redirect to upon validation error
            $_POST['current_url'] = 'admin/add/page?type=tool';
        }

        if ($valid->hasErrors()) {
            $_SESSION['admin']['field_values'] = $_POST;
            $_SESSION['admin']['field_errors'] = $valid->getFieldErrors();
            $valid->createNotifications();
            return false;
        }

        if (!in_array($_POST['type'], Pdb::extractEnumArr('page_revisions', 'type'))) {
            return false;
        }

        $operator = AdminAuth::getDetails();
        if (! $operator) return false;

        // Start transaction
        Pdb::transact();

        // Add page
        $update_fields = [];
        $update_fields['name'] = $_POST['name'];
        $update_fields['meta_description'] = $_POST['meta_description'];
        $update_fields['parent_id'] = $_POST['parent_id'];
        $update_fields['active'] = $_POST['active'];
        $update_fields['slug'] = Enc::urlname($_POST['name'], '-');
        $update_fields['show_in_nav'] = $_POST['show_in_nav'];
        $update_fields['subsite_id'] = $_SESSION['admin']['active_subsite'];
        $update_fields['modified_editor'] = $operator['name'];
        $update_fields['alt_template'] = trim(preg_replace('![^-_a-z0-9/]!i', '', $_POST['alt_template'] ?? ''));
        $update_fields['menu_group'] = (int) @$_POST['menu_group'];
        $update_fields['date_added'] = Pdb::now();
        $update_fields['date_modified'] = Pdb::now();

        if ($_POST['admin_perm_specific'] == 1) {
            $update_fields['admin_perm_type'] = Constants::PERM_SPECIFIC;
        } else {
            $update_fields['admin_perm_type'] = Constants::PERM_INHERIT;
        }

        if (Register::hasFeature('users')) {
            // TODO should this be a boolean cast/check?
            if ($_POST['user_perm_specific'] ?? 0 == 1) {
                $update_fields['user_perm_type'] = Constants::PERM_SPECIFIC;
            } else {
                $update_fields['user_perm_type'] = Constants::PERM_INHERIT;
            }
        }

        $page_id = Pdb::insert('pages', $update_fields);

        $this->fixRecordOrder($page_id);

        // History item
        $res = $this->addHistoryItem($page_id, 'Created empty new page');
        if (! $res) return false;

        // Add first (blank) revision
        $update_fields = array();
        $update_fields['page_id'] = $page_id;
        $update_fields['type'] = $_POST['type'];
        $update_fields['changes_made'] = 'New page';


        // Normal pages have more data to add on the edit form, but tool pages go live straight away
        if ($_POST['type'] == 'tool') {
            $update_fields['status'] = 'live';

            $update_fields['controller_entrance'] = $_POST['controller_entrance'];
            $update_fields['controller_argument'] = $_POST['controller_argument'];
        } else {
            $update_fields['status'] = 'wip';
        }

        $update_fields['modified_editor'] = $operator['name'];
        $update_fields['date_added'] = Pdb::now();
        $update_fields['date_modified'] = Pdb::now();
        Pdb::insert('page_revisions', $update_fields);

        // Admin permissions
        if ($_POST['admin_perm_specific'] == 1 and !empty($_POST['admin_permissions'])) {
            foreach ($_POST['admin_permissions'] as $id) {
                $id = (int) $id;
                if ($id == 0) continue;

                // Create a new permission record
                $update_fields = array();
                $update_fields['item_id'] = $page_id;
                $update_fields['category_id'] = $id;

                Pdb::insert('page_admin_permissions', $update_fields);
            }
        }

        // User permissions
        if (Register::hasFeature('users')) {
            // TODO should this be a boolean cast/check?
            if ($_POST['user_perm_specific'] ?? 0 == 1 and !empty($_POST['user_permissions'])) {
                foreach ($_POST['user_permissions'] as $id) {
                    $id = (int) $id;
                    if ($id == 0) continue;

                    // Create a new permission record
                    $update_fields = array();
                    $update_fields['item_id'] = $page_id;
                    $update_fields['category_id'] = $id;

                    Pdb::insert('page_user_permissions', $update_fields);
                }
            }
        }

        // Commit
        Pdb::commit();

        Navigation::clearCache();

        Notification::confirm('Your page has been created. Add your content below.');

        return 'admin/edit/' . $this->controller_name . '/' . $page_id . '?suppress=true';
    }


    /**
    * Return HTML for the import upload form
    **/
    public function _importUploadForm()
    {
        $types = [];
        foreach (Register::getDocImports() as $ext => $details) {
            $types[$details[1]] = ['name' => $details[1], 'ext' => $ext];
        }
        ksort($types);

        $list = new Itemlist();
        $list->main_columns = ['Type' => 'name', 'File extension' => 'ext'];
        $list->items = $types;

        $view = new PhpView('sprout/admin/page_import_upload');
        $view->list = $list->render();

        return $view;
    }


    /**
    * Upload and do initial processing on the file
    **/
    public function importUploadAction()
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();
        $timestamp = time();

        // Validate upload
        if (! is_array($_FILES['import'])) {
            $error = 'No file provided';
        } else if (! Upload::required($_FILES['import'])) {
            $error = 'No file provided';
        } else if (! Upload::valid($_FILES['import'])) {
            $error = 'File upload error';
        } else if (! FileUpload::checkFilename($_FILES['import']['name'])) {
            $error = 'File type not allowed';
        } else {
            $error = null;
        }

        // Instantiate the importer library
        if (! $error) {
            try {
                $inst = DocImport::instance($_FILES['import']['name']);
                $ext = File::getExt($_FILES['import']['name']);
            } catch (Exception $ex) {
                $error = $ex->getMessage();
            }
        }

        // Upload file to temp dir
        if (! $error) {
            $temporig = STORAGE_PATH . "temp/import_{$timestamp}.{$ext}";

            $res = @copy($_FILES['import']['tmp_name'], $temporig);
            if (! $res) {
                $error = 'Unable to copy file to temporary directory';
            }
        }

        // Do file processing
        if (! $error) {
            try {
                $result = $inst->load($temporig);
            } catch (Exception $ex) {
                $error = $ex->getMessage();
            }

            @unlink($temporig);
        }

        // Check the result is valid XML
        if (! $error) {
            if (!($result instanceof DOMDocument)) {
                $dom = new DOMDocument();
                libxml_use_internal_errors(true);
                $success = @$dom->loadXML($result);
                if (! $success) {
                    $error = 'Conversion failed';

                    foreach (libxml_get_errors() as $err) {
                        Notification::error($err->message . ' (' . $err->code . ') at ' . $err->line . ':' . $err->column);
                    }

                    file_put_contents(STORAGE_PATH . "temp/conversion_failure.xml", $result);
                    chmod(STORAGE_PATH . "temp/conversion_failure.xml", 0666);
                }
                libxml_use_internal_errors(false);
                unset($dom);
            }
        }

        // Save XML file
        if (! $error) {
            $tempxml = STORAGE_PATH . "temp/import_{$timestamp}.xml";

            if ($result instanceof DOMDocument) {
                $result->save($tempxml);

            } else if (is_string($result)) {
                file_put_contents($tempxml, $result);

            } else {
                $error = 'Unable to save temp xml';
            }
        }

        // Report error
        if ($error) {
            Notification::error($error);
            Url::redirect("admin/import_upload/page");
        }

        Url::redirect("admin/import_options/page?timestamp={$timestamp}&ext=xml");
    }


    /**
    * Preview the pages which will be created
    **/
    public function importPreviewAjax()
    {
        AdminAuth::checkLogin();
        $_GET['timestamp'] = (int) $_GET['timestamp'];
        $filename = STORAGE_PATH . "temp/import_{$_GET['timestamp']}.xml";

        echo '<div class="info highlight-confirm">This is a preview of the pages which will be created</div>';

        switch ($_GET['import_type']) {
            case 'none':
                echo '<ul>';
                echo '<li>', ($_GET['page_name'] ? Enc::html($_GET['page_name']) : '<i>Enter a page name into the field above</i>'), '</li>';
                echo '</ul>';
                break;

            case 'heading':
                $tree = DocImport::getHeadingsTree($filename, $_GET['heading_level']);

                if (trim($_GET['top_page_name'])) {
                    $tree['name'] = trim($_GET['top_page_name']);
                    $new_root = new Treenode();
                    $new_root->children = array($tree);
                    $tree->parent = $new_root;
                    $tree = $new_root;
                }

                echo '<ul>';
                foreach ($tree->children as $child) {
                    self::renderPreviewTreenode($child);
                }
                echo '</ul>';
                break;

            default:
                echo '<p><i>Invalid import type</i></p>';
        }
    }


    /**
    * Render a single node for the preview tree we return in `import_preview_ajax`
    **/
    private static function renderPreviewTreenode($node)
    {
        echo '<li>', Enc::html($node['name']);

        if (count($node->children)) {
            echo '<ul>';
            foreach ($node->children as $child) {
                self::renderPreviewTreenode($child);
            }
            echo '</ul>';
        }

        echo '</li>';
    }


    /**
    * Returns a form which contains options for doing an import
    **/
    public function _getImport($filename)
    {
        $view = new PhpView('sprout/admin/page_import_options');

        return array(
            'title' => 'Document import',
            'content' => $view->render(),
        );
    }


    /**
    * Facebox info box
    **/
    public function importNotes($view_name)
    {
        AdminAuth::checkLogin();
        $view = new PhpView('sprout/doc_import_notes/' . $view_name);

        echo '<div class="import-notes">';
        echo $view->render();
        echo '</div>';
    }


    /**
    * Does the actual import
    *
    * @param string $filename The location of the import data, in a temporary directory
    **/
    public function _importData($filename)
    {
        set_time_limit(0);
        $images = array();
        $headings = array();

        $operator = AdminAuth::getDetails();
        if (! $operator) return false;

        // Basic validation
        if ($_POST['import_type'] != 'none' and $_POST['import_type'] != 'heading') {
            return false;
        }

        if ($_POST['import_type'] == 'none' and !$_POST['page_name']) {
            return false;
        }

        // Load images, save mapping into $images
        $cat_id = Category::lookupOrCreate('files', 'Imported document images');
        $resources = DocImport::getResources($filename);
        foreach ($resources as $resname => $blob) {
            $image_filename = File::filenameMakeSane('doc_' . $resname);


            Pdb::transact();

            // Add file record
            $update_fields = array();
            $update_fields['name'] = $resname;
            $update_fields['type'] = FileConstants::TYPE_IMAGE;
            $update_fields['date_added'] = Pdb::now();
            $update_fields['date_modified'] = Pdb::now();
            $update_fields['date_file_modified'] = Pdb::now();
            $update_fields['sha1'] = hash('sha1', $blob, false);

            $file_id = Pdb::insert('files', $update_fields);
            $image_filename = $file_id . '_' . $image_filename;

            // Set filename to contain file id
            $update_fields = array();
            $update_fields['filename'] = $image_filename;
            Pdb::update('files', $update_fields, ['id' => $file_id]);

            // categorise
            Category::insertInto('files', $file_id, $cat_id);

            // insert the blob of data
            File::putString($image_filename, $blob);

            File::createDefaultSizes($image_filename);

            Pdb::commit();


            // If the image is large, auto-switch in the medium size
            $size = File::imageSize($image_filename);
            $small = FileTransform::getTransformFilename($image_filename, 'medium');
            if ($size[0] > 300 and File::exists($small)) {
                $image_filename = $small;
            }

            $images[$resname] = File::relUrl($image_filename);
        }

        // Split into pages based on options
        switch ($_POST['import_type']) {
            case 'none':
                $dom = new DOMDocument();
                $dom->loadXML(file_get_contents($filename));
                $body = $dom->saveXML($dom->getElementsByTagName('body')->item(0));

                $tree = new Treenode();
                $node = new Treenode();
                $node['name'] = $_POST['page_name'];
                $node['body'] = $body;
                $tree->children[] = $node;

                $headings[1] = 2;
                break;

            case 'heading':
                $tree = DocImport::getHeadingsTree($filename, $_POST['heading_level'], true);

                if ($_POST['heading_level'] > 1) {
                    for ($i = $_POST['heading_level'] + 1; $i < 6; $i++) {
                        $headings[$i] = $i - 1;
                    }
                }

                if (trim($_POST['top_page_name'])) {
                    $tree['name'] = trim($_POST['top_page_name']);
                    $new_root = new Treenode();
                    $new_root->children = array($tree);
                    $tree->parent = $new_root;
                    $tree = $new_root;
                }
                break;

            default:
                throw new Exception("Invalid import type '{$_POST['import_type']}'.");
        }

        // Walk page tree and create pages
        $count = 0;
        Pdb::transact();
        foreach ($tree->children as $child) {
            $result = $this->createPageTreenode($child, (int)$_POST['parent_id'], $images, $headings, $operator);
            if ($result === false) return false;
            $count += $result;
        }
        Pdb::commit();

        Notification::confirm('Imported ' . $count . ' ' . Inflector::plural('page', $count));
        return true;
    }


    /**
    * Create a page
    **/
    private function createPageTreenode($node, $parent_id, $images, $headings, $operator)
    {
        $dom = new DOMDocument();
        $success = $dom->loadXML('<doc><body>' . $node['body'] . '</body></doc>');
        $html = DocImport::getHtml($dom, $images, $headings);

        // Add page
        $update_fields = [];
        $update_fields['name'] = trim($node['name']);
        $update_fields['slug'] = Enc::urlname(trim($node['name']), '-');
        $update_fields['active'] = 1;
        $update_fields['show_in_nav'] = 1;
        $update_fields['parent_id'] = $parent_id;
        $update_fields['subsite_id'] = $_SESSION['admin']['active_subsite'];
        $update_fields['modified_editor'] = $operator['name'];
        $update_fields['date_added'] = Pdb::now();
        $update_fields['date_modified'] = Pdb::now();
        $page_id = Pdb::insert('pages', $update_fields);

        $this->fixRecordOrder($page_id);

        // Add revision
        $update_fields = array();
        $update_fields['page_id'] = $page_id;
        $update_fields['type'] = 'standard';
        $update_fields['changes_made'] = 'Imported page from uploaded file';
        $update_fields['status'] = 'live';
        $update_fields['modified_editor'] = $operator['name'];
        $update_fields['date_added'] = Pdb::now();
        $update_fields['date_modified'] = Pdb::now();
        $rev_id = Pdb::insert('page_revisions', $update_fields);

        // Add content block
        $settings = [
            'text' => $html,
        ];
        $update_fields = array();
        $update_fields['page_revision_id'] = $rev_id;
        $update_fields['area_id'] = 1;
        $update_fields['active'] = 1;
        $update_fields['type'] = 'RichText';
        $update_fields['settings'] = json_encode($settings);
        $update_fields['record_order'] = 1;
        Pdb::insert('page_widgets', $update_fields);

        // History page
        $res = $this->addHistoryItem($page_id, "Imported page from uploaded file");
        if (! $res) return false;

        // Do indexing on the page text
        $res = $this->reindexItem($page_id, $node['name'], $html);
        if (! $res) return false;

        // Children pages
        $count = 1;
        foreach ($node->children as $child) {
            $count += $this->createPageTreenode($child, $page_id, $images, $headings, $operator);
        }

        return $count;
    }


    /** @inheritdoc */
    public function _getCustomEditSaveHTML($item_id)
    {
        // N.B. this is called after the edit form has been rendered

        $user_id = AdminAuth::getId();
        $q = "SELECT operators.id, operators.name
            FROM ~operators AS operators
            INNER JOIN ~operators_cat_join AS joiner ON joiner.operator_id = operators.id
                AND operators.id != ?
            INNER JOIN ~operators_cat_list AS cat ON joiner.cat_id = cat.id
            WHERE cat.access_noapproval = 1
            ORDER BY operators.name";
        $approval_admins = Pdb::q($q, [$user_id], 'map');

        $view = new PhpView('sprout/admin/page_edit_save');
        $view->id = (int) $item_id;
        $view->preview_url = Subsites::getAbsRootAdmin() . 'admin/call/page/preview/' . $item_id;
        $view->approval_admins = $approval_admins;
        $view->allow_delete = $this->_isDeleteSaved($item_id);

        $view->type = $this->edit_type;

        return $view->render();
    }


    /**
     * Return the URL to use for the 'view live site' button, when editing a given record
     *
     * @param int $item_id Record which is being editied
     * @return string URL, either absolute or relative
     * @return null Default url should be used
     */
    public function _getEditLiveUrl($item_id)
    {
        return Page::url($item_id);
    }


    /**
    * Returns the edit form for editing the specified page
    *
    * @param int $id The record to show the edit form for
    * @return string The HTML code which represents the edit form
    **/
    public function _getEditForm($id)
    {
        $id = (int) $id;

        // Get subsite info
        $q = "SELECT * FROM ~subsites WHERE id = ?";
        try {
            $subsite = Pdb::q($q, [$_SESSION['admin']['active_subsite']], 'row');
        } catch (QueryException $ex) {
            return new AdminError("Invalid id specified - current subsite does not exist");
        }

        // Load the page
        $q = "SELECT * FROM ~pages WHERE id = ?";
        try {
            $page = Pdb::q($q, [$id], 'row');
        } catch (QueryException $ex) {
            return new AdminError("Invalid id specified - page does not exist");
        }

        TinyMCE4RichText::needs();

        // If no slug set, create one
        if (empty($page['slug'])) $page['slug'] = Enc::urlname($page['name']);

        // Check the permissions of the page - can the operator edit this page?
        $res = AdminPerms::checkPermissionsTree('pages', $id);
        if (! $res) return new AdminError("Access denied to modify this page");

        // Load the revisions
        $q = "SELECT page_revisions.*, DATE_FORMAT(date_modified, '%d/%m/%Y %h:%i %p') AS date_modified
            FROM ~page_revisions AS page_revisions
            WHERE page_id = ?
            ORDER BY page_revisions.date_modified DESC";
        $revs = Pdb::q($q, [$id], 'arr');
        if (count($revs) == 0) {
            return new AdminError("Invalid id specified - page does not have any revisions");
        }

        // Select the revision to edit
        // If there is a rev in the session, use it.
        // Otherwise, get the latest live revision.
        // If all else fails, use the latest revision
        $sel_rev = ['id' => 0];
        $rev_num = 0;
        if (!empty($_SESSION['admin']['field_values']['rev_id'])) {
            foreach ($revs as $i => $rev) {
                if ($rev['id'] == $_SESSION['admin']['field_values']['rev_id']) {
                    $sel_rev = $rev;
                    $rev_num = count($revs) - $i;
                    break;
                }
            }

        } else if (!empty($_GET['revision'])) {
            $rev_id = (int)$_GET['revision'];
            foreach ($revs as $i => $rev) {
                if ($rev['id'] == $rev_id) {
                    $sel_rev = $rev;
                    $rev_num = count($revs) - $i;
                    break;
                }
            }

        } else {
            $sel_rev = $revs[0];
            foreach ($revs as $rev) {
                if ($rev['status'] == 'live') {
                    $sel_rev = $rev;
                    break;
                }
            }
        }

        // If there's no live revision, inform the user
        $has_live_rev = false;
        foreach ($revs as $rev) {
            if ($rev['status'] == 'live') {
                $has_live_rev = true;
                break;
            }
        }

        $data = array_merge($page, $sel_rev);

        // Type override caused by clicking a sidebar 'change to' option
        $_GET['type'] ??= '';
        if (in_array($_GET['type'], Pdb::extractEnumArr('page_revisions', 'type'))) {
            $data['type'] = $_GET['type'];
        }

        // Remember the edit type for use in sidebar; i.e. _getCustomEditSaveHTML
        $this->edit_type = $data['type'];

        if ($sel_rev['status'] != 'wip') {
            $data['changes_made'] = '';
        }

        if ($data['status'] != 'wip' and $data['status'] != 'auto_launch') {
            if (AdminPerms::canAccess('access_noapproval')) {
                $data['status'] = 'live';
            } else {
                $data['status'] = 'need_approval';
            }
        }

        if ($data['type'] == 'standard') {
            // Load widgets and collate rich text as page text
            $text = '';
            $widgets = [];
            $q = "SELECT area_id, type, settings, conditions, active, heading, template, columns
                FROM ~page_widgets
                WHERE page_revision_id = ?
                ORDER BY area_id, record_order";
            $wids = Pdb::q($q, [$sel_rev['id']], 'arr');

            foreach ($wids as $widget) {
                $widgets[$widget['area_id']][] = $widget;

                // Embedded rich text widgets
                if ($widget['area_id'] == 1 and $widget['type'] == 'RichText') {
                    $settings = json_decode($widget['settings'], true);
                    if ($text) $text .= "\n";
                    $text .= $settings['text'];
                }
            }


            // Load media
            $media = [];
            preg_match_all('/<img.*?src="(.*?)"/', $text, $matches);
            foreach ($matches[1] as $match) {
                $media[] = $match;
            }

            AdminSeo::setTopic($page['name']);
            AdminSeo::setSlug($data['slug']);
            AdminSeo::addContent($text);
            AdminSeo::addLinks(Page::determineRelatedLinks($id));

        } else if (in_array($data['type'], ['tool', 'redirect'])) {
            $widgets = [];

        } else {
            return new AdminError("Invalid page type");
        }

        // Load permissions for page
        $admin_permissions = AdminPerms::getAccessableGroups('pages', $id);
        if ($data['admin_perm_type'] == Constants::PERM_SPECIFIC) {
            $data['admin_perm_specific'] = 1;
        } else {
            $data['admin_perm_specific'] = 0;
        }

        $data['admin_permissions'] = $admin_permissions;

        $user_permissions = UserPerms::getAccessableGroups('pages', $id);
        if ($data['user_perm_type'] == Constants::PERM_SPECIFIC) {
            $data['user_perm_specific'] = 1;
        } else {
            $data['user_perm_specific'] = 0;
        }

        $data['user_permissions'] = $user_permissions;


        // Overlay session data
        if (!empty($_SESSION['admin']['field_values'])) {
            $data = array_merge($data, $_SESSION['admin']['field_values']);
            unset ($_SESSION['admin']['field_values']);
        }

        // Load history
        $q = "SELECT modified_editor, changes_made,
                    DATE_FORMAT(date_added, '%d/%m/%Y %h:%i %p') AS date_added
            FROM ~page_history_items AS page_history_items
            WHERE page_id = ?
            ORDER BY page_history_items.date_added DESC";
        $history = Pdb::q($q, [$id], 'arr');

        // Controller list for entrance dropdown
        $front_end_controllers = Register::getFrontEndControllers();
        asort($front_end_controllers);

        // Load entrance arguments
        $controller_arguments = array();
        if ($data['controller_entrance']) {
            $inst = Sprout::instance($data['controller_entrance']);
            if ($inst instanceof FrontEndEntrance) {
                $controller_arguments = $inst->_getEntranceArguments();
                if (empty($controller_arguments)) {
                    $controller_arguments = array('' => '- Nothing available -');
                }
            }
        }

        $templates = Subsites::getConfigAdmin('skin_views');
        if (! $templates) $templates = array('skin/inner' => 'Inner');


        // Custom attributes
        $attributes = MultiEdit::load('page_attributes', ['page_id' => $id]);
        if (! isset($data['multiedit_attrs'])) {
            $data['multiedit_attrs'] = $attributes;
        }

        // Load admin notes, if found
        $admin_notes = null;
        foreach ($attributes as $row) {
            if ($row['name'] == 'sprout.admin_notes') {
                $admin_notes = trim($row['value']);
                break;
            }
        }

        // Special case for redirect pages
        if (!$admin_notes and $data['type'] == 'redirect') {
            if (!empty($data['redirect'])) {
                $typename = Lnk::typename($data['redirect']);
                if (preg_match('/^[aeiou]/i', $typename)) {
                    $admin_notes = 'This page redirects to an ' . $typename . '. Content blocks on this page have been disabled.';
                } else {
                    $admin_notes = 'This page redirects to a ' . $typename . '. Content blocks on this page have been disabled.';
                }
            } else {
                $admin_notes = 'This page will redirect. Content blocks on this page have been disabled.';
            }
        }

        // Richtext width and height
        $richtext_width = Kohana::config('sprout.admin_richtext_width');
        $richtext_height = Kohana::config('sprout.admin_richtext_height');
        if (!$richtext_width) $richtext_width = 700;
        if (!$richtext_height) $richtext_height = 500;

        // View
        $view = new PhpView('sprout/admin/page_edit');

        $errors = [];
        if (!empty($_SESSION['admin']['field_errors'])) {
            $errors = $_SESSION['admin']['field_errors'];
            unset($_SESSION['admin']['field_errors']);
        }

        $view->id = $id;
        $view->page = $page;
        $view->subsite = $subsite;
        $view->data = $data;
        $view->errors = $errors;
        $view->widgets = $widgets;
        $view->history = $history;
        $view->admin_category_options = AdminAuth::getAllCategories();
        $view->admin_permissions = $admin_permissions;
        $view->user_category_options = UserPerms::getAllCategories();
        $view->user_permissions = $user_permissions;
        $view->can_approve_revisions = AdminPerms::canAccess('access_noapproval');
        $view->front_end_controllers = $front_end_controllers;
        $view->controller_arguments = $controller_arguments;
        $view->templates = $templates;
        $view->admin_notes = $admin_notes;
        $view->richtext_width = $richtext_width;
        $view->richtext_height = $richtext_height;

        $view->revs = $revs;
        $view->sel_rev_id = $sel_rev['id'];
        $view->has_live_rev = $has_live_rev;

        if ($data['type'] == 'standard') {
            $view->text = $text;
            $view->media = $media;
        }

        $view->show_tour = !Admin::isTourCompleted('page_edit');

        if ($rev_num) {
            $title = 'Editing revision ' . $rev_num . ' of page <strong>' . Enc::html($page['name']) . '</strong>';
        } else {
            $title = 'Editing page <strong>' . Enc::html($page['name']) . '</strong>';
        }

        return array(
            'title' => $title,
            'content' => $view->render()
        );
    }

    /**
    * Gets the text of a single revision
    *
    * @param int $id The revision to get the text of
    **/
    public function ajaxGetRev($id)
    {
        $id = (int) $id;

        $rev = Pdb::get('page_revisions', $id);

        $out = array();
        $out['text'] = $rev['text'];
        $out['date_launch'] = '';

        switch ($rev['status']) {
            case 'wip':
            case 'auto_launch':
                $out['changes_made'] = $rev['changes_made'];
                $out['status'] = $rev['status'];
                $out['date_launch'] = $rev['date_launch'];
                break;

            case 'need_approval':
                $out['changes_made'] = $rev['changes_made'];
                $out['status'] = 'live';
                break;

            case 'live':
                $out['changes_made'] = '';
                $out['status'] = 'live';
                break;

            case 'old':
            case 'rejected':
                $changes = preg_replace('/ ?\(based.+/', '', $rev['changes_made']);
                $out['changes_made'] = "...... (based on revision {$rev['id']}: '{$changes}')";
                $out['status'] = 'live';
                break;
        }

        Json::out($out);
    }


    /**
    * Return a list of menu groups for a selected parent page
    **/
    public function ajaxGetMenuGroups($parent_page_id)
    {
        AdminAuth::checkLogin();
        $parent_page_id = (int) $parent_page_id;

        // Special case for top-level
        if ($parent_page_id === 0) {
            Json::confirm(array('groups' => array()));
            return;
        }

        // Find the page
        $root = Navigation::loadPageTree($_SESSION['admin']['active_subsite'], true);
        $node = $root->findNodeValue('id', $parent_page_id);
        if ($node === null) Json::error('Invalid parent page');

        // Get the groups
        $anc = $node->findAncestors();
        $top_parent = $anc[0];
        $groups = NavigationGroups::getGroupsAdmin($top_parent['id']);

        $names = array();
        foreach ($groups as $id => $row) {
            $names[$id] = $row['name'];
        }

        Json::confirm(array('groups' => $names));
    }


    /**
    * Saves the provided POST data into this page in the database
    *
    * @param int $page_id The record to update
    * @return bool True on success, false on failure
    **/
    public function _editSave($page_id)
    {
        $res = AdminPerms::checkPermissionsTree('pages', $page_id);
        if (! $res) return false;

        $page_id = (int) $page_id;
        $rev_id = (int) $_POST['rev_id'];

        $q = "SELECT page.*, rev.type, rev.controller_entrance, rev.controller_argument, rev.redirect
            FROM ~pages AS page
            INNER JOIN ~page_revisions AS rev ON page.id = rev.page_id
                AND rev.id = ?
            WHERE page.id = ?";
        $orig_page = Pdb::q($q, [$rev_id, $page_id], 'row');

        $revision_changed = false;
        $page_type = (string) @$_POST['type'];
        if (!in_array($page_type, Pdb::extractEnumArr('page_revisions', 'type'))) {
            $page_type = $orig_page['type'];
        }

        if ($page_type != $orig_page['type']) $revision_changed = true;

        // Collate POSTed widgets.
        $new_widgets = [];
        if (!empty($_POST['widgets'])) {
            foreach ($_POST['widgets'] as $area_name => $widgets) {
                $area = WidgetArea::findAreaByName($area_name);
                if ($area == null) continue;

                $order = 0;
                foreach ($widgets as $info) {
                    list ($index, $type) = explode(',', $info, 2);

                    // If it's been deleted, then skip over all other processing
                    if ($_POST['widget_deleted'][$area_name][$index] == '1') {
                        continue;
                    }

                    $settings = @$_POST['widget_settings_' . $index];
                    if (!is_array($settings)) $settings = [];

                    $settings = json_encode($settings);

                    $active = 1;
                    if (isset($_POST['widget_active'][$area_name][$index])) {
                        $active = (int) (bool) $_POST['widget_active'][$area_name][$index];
                    }

                    $conditions = '';
                    if (isset($_POST['widget_conds'][$area_name][$index])) {
                        $conditions = $_POST['widget_conds'][$area_name][$index];
                    }

                    $heading = '';
                    if (isset($_POST['widget_heading'][$area_name][$index])) {
                        $heading = $_POST['widget_heading'][$area_name][$index];
                    }

                    $template = '';
                    if (isset($_POST['widget_template'][$area_name][$index])) {
                        $template = $_POST['widget_template'][$area_name][$index];
                    }

                    $columns = '1st';
                    if (isset($_POST['widget_columns'][$area_name][$index])) {
                        $columns = $_POST['widget_columns'][$area_name][$index];
                    }

                    $new_widgets[] = [
                        'area_id' => $area->getIndex(),
                        'active' => $active,
                        'type' => $type,
                        'settings' => $settings,
                        'conditions' => $conditions,
                        'heading' => $heading,
                        'template' => $template,
                        'columns' => $columns,
                        'record_order' => $order++,
                    ];
                }
            }
        }

        // Compare new widgets with old ones -- if changed, need a new revision
        $q = "SELECT area_id, active, type, settings, conditions, heading, template, columns, record_order
            FROM ~page_widgets
            WHERE page_revision_id = ?
            ORDER BY area_id, record_order";
        $old_widgets = Pdb::query($q, [$rev_id], 'arr');
        if (count($new_widgets) != count($old_widgets)) {
            $revision_changed = true;
        } else {
            foreach ($old_widgets as $key => $widget) {
                if ($widget != @$new_widgets[$key]) {
                    $revision_changed = true;
                    break;
                }
            }
        }

        // Get the original revision, see if it has changed
        try {
            $q = "SELECT rev.*, page.parent_id
                FROM ~page_revisions AS rev
                INNER JOIN ~pages AS page ON page.id = rev.page_id
                WHERE rev.id = ?";
            $orig_rev = Pdb::q($q, [$rev_id], 'row');
        } catch (RowMissingException $ex) {
            // Pretend there's an existing revision when going a page preview
            $orig_rev = ['status' => null, 'date_launch' => null];
        }

        if ($_POST['status'] != $orig_rev['status']) $revision_changed = true;

        if ($_POST['status'] == 'auto_launch') {
            if ($_POST['date_launch'] != $orig_rev['date_launch']) $revision_changed = true;
        } else {
            $_POST['date_launch'] = null;
        }

        if ($page_type == 'standard') {
            if (!empty($_POST['mediareplace_fr'])) {
                foreach($_POST['mediareplace_fr'] as $idx => $replace_from) {
                    $replace_to = $_POST['mediareplace_to'][$idx];

                    if (! $replace_to) continue;
                    if ($replace_to == $replace_from) continue;

                    $resized = FileTransform::getTransformFilename($replace_to, 'medium');
                    if (File::exists($resized)) $replace_to = $resized;
                    $replace_to = File::relUrl($replace_to);

                    foreach ($new_widgets as &$widget) {
                        if ($widget['area_id'] != 1 or $widget['type'] != 'RichText') continue;
                        $settings = json_decode($widget['settings'], true);
                        $new_text = preg_replace('/<img(.*?)src="' . preg_quote($replace_from, '/') . '"(.*?)>/', "<img\$1src=\"{$replace_to}\"\$2/>", $settings['text']);
                        if ($new_text != $settings['text']) {
                            $settings['text'] = $new_text;
                            $widget['settings'] = json_encode($settings);
                            $revision_changed = true;
                        }
                    }
                    unset($widget);
                }
            }
        } else if ($page_type == 'tool') {
            if ($orig_page['controller_entrance'] != $_POST['controller_entrance']) $revision_changed = true;
            if ($orig_page['controller_argument'] != $_POST['controller_argument']) $revision_changed = true;
        } else if ($page_type == 'redirect') {
            if ($orig_page['redirect'] != $_POST['redirect']) $revision_changed = true;
        }

        // Check if the parent changed
        $parent_changed = false;
        if ((int) $orig_page['parent_id'] != (int) $_POST['parent_id']) {
            $parent_changed = true;
        }

        if (empty($_POST['controller_argument'])) {
            $_POST['controller_argument'] = '';
        }

        // Set up validation rules
        $valid = new Validator($_POST);
        $valid->setLabels([
            'parent_id' => 'Parent page',
            'slug' => 'URL slug',
            'gallery_thumb' => 'Gallery thumbnail',
        ]);

        $valid->required(['name', 'slug']);
        $valid->check('name', 'Validity::length', 1, 200);
        $valid->check('slug', 'Validity::length', 1, 200);
        $valid->check('slug', 'Slug::valid');

        $slug_conditions = [
            'subsite_id' => $_SESSION['admin']['active_subsite'],
            'parent_id' => (int)$_POST['parent_id'],
            ['id', '!=', $page_id],
        ];
        $valid->check('slug', 'Slug::unique', 'pages', $slug_conditions);

        $valid->check('meta_keywords', 'Validity::length', 0, 200);
        $valid->check('meta_description', 'Validity::length', 0, 200);
        $valid->check('alt_browser_title', 'Validity::length', 0, 200);
        $valid->check('alt_nav_title', 'Validity::length', 0, 200);

        if ($page_type == 'standard') {
            $valid->check('redirect', 'Validity::length', 0, 200);

            if ($revision_changed) {
                $valid->check('changes_made', 'Validity::length', 0, 250);
            }

            if ($_POST['status'] == 'need_approval') {
                $valid->required(['approval_operator_id']);

                try {
                    $q = "SELECT * FROM ~operators WHERE ID = ?";
                    $approval_operator = Pdb::query($q, [(int) $_POST['approval_operator_id']], 'row');
                } catch (RowMissingException $ex) {
                    $valid->addFieldError('approval_operator_id', 'Invalid value');
                }
            }

            if ($_POST['status'] == 'auto_launch') {
                $valid->required(['date_launch']);
            }

        } else if ($page_type == 'tool') {
            $valid->required(['controller_entrance', 'controller_argument']);
            $valid->check('controller_entrance', 'Validity::length', 1, 200);
            if (!self::checkControllerEntrance($_POST['controller_entrance'], $page_id)) {
                $valid->addFieldError('controller_entrance', 'Invalid value');
            }
        } else if ($page_type == 'redirect') {
            $valid->required(['redirect']);
            $valid->check('redirect', function($value) {
                if (!Lnk::valid($_POST['redirect'])) {
                    throw new ValidationException('Invalid redirect target');
                }
            });
        }

        if ($orig_page['id'] == $_POST['parent_id']) {
            $valid->addFieldError('parent_id', 'A page can\'t be its own parent.');
        }

        if ($parent_changed) {
            $root_node = Navigation::loadPageTree($_SESSION['admin']['active_subsite'], true);
            $new_parent = $root_node->findNodeValue('id', $_POST['parent_id']);
            $ancestors = $new_parent->findAncestors();

            foreach ($ancestors as $anc) {
                if ($anc['id'] == $page_id) {
                    $valid->addFieldError('parent_id', 'You can\'t set a descendent of this page as its parent');

                    break;
                }
            }
        }

        // Check validation
        if ($valid->hasErrors()) {
            $_SESSION['admin']['field_values'] = $_POST;
            $_SESSION['admin']['field_errors'] = $valid->getFieldErrors();
            $valid->createNotifications();
            return false;
        }

        $operator = AdminAuth::getDetails();
        if (! $operator) return false;

        // Start transaction
        Pdb::transact();

        // Update page
        $update_fields = [];
        $update_fields['date_modified'] = Pdb::now();
        $update_fields['name'] = $_POST['name'];
        $update_fields['slug'] = Enc::urlname($_POST['slug'], '-');
        $update_fields['active'] = (int) (bool) @$_POST['active'];
        $update_fields['show_in_nav'] = (int) (bool) @$_POST['show_in_nav'];
        $update_fields['meta_keywords'] = $_POST['meta_keywords'];
        $update_fields['meta_description'] = $_POST['meta_description'];

        if ($page_type == 'standard') {
            if ($_POST['stale_age'] === '') {
                $update_fields['stale_age'] = null;
            } else {
                $update_fields['stale_age'] = (int) $_POST['stale_age'];
            }
        }

        $update_fields['alt_browser_title'] = $_POST['alt_browser_title'];
        $update_fields['alt_nav_title'] = $_POST['alt_nav_title'];
        $update_fields['date_expire'] = !empty($_POST['date_expire']) ? date('Y-m-d', strtotime($_POST['date_expire'])) : '0000-00-00';
        $update_fields['modified_editor'] = $operator['name'];
        $update_fields['menu_group'] = (int) @$_POST['menu_group'];
        if (Kohana::config('page.enable_banners')) {
            $update_fields['banner'] = !empty($_POST['banner']) ? $_POST['banner'] : null;
        }

        $update_fields['gallery_thumb'] = !empty($_POST['gallery_thumb']) ? $_POST['gallery_thumb'] : null;

        if ($_POST['type'] == 'standard') {
            $update_fields['alt_template'] = trim(preg_replace('![^-_a-z0-9/]!i', '', $_POST['alt_template']));


            if (Kohana::config('sprout.tweak_skin')) {
                $update_fields['additional_css'] = trim($_POST['additional_css']);
            }

        }

        // TODO should these be a boolean cast/check?

        if ($_POST['admin_perm_specific'] ?? '' == 1) {
            $update_fields['admin_perm_type'] = Constants::PERM_SPECIFIC;
        } else {
            $update_fields['admin_perm_type'] = Constants::PERM_INHERIT;
        }

        if (Register::hasFeature('users')) {
            if ($_POST['user_perm_specific'] ?? '' == 1) {
                $update_fields['user_perm_type'] = Constants::PERM_SPECIFIC;
            } else {
                $update_fields['user_perm_type'] = Constants::PERM_INHERIT;
            }
        }

        if ($parent_changed) {
            $update_fields['parent_id'] = (int) $_POST['parent_id'];
            $update_fields['record_order'] = 0;
        }

        Pdb::update('pages', $update_fields, ['id' => $page_id]);

        if ($parent_changed) $this->fixRecordOrder($page_id);

        // Update revision - if the text actually changed
        if ($revision_changed) {

            $update_fields = [];
            $update_fields['type'] = $_POST['type'];
            $update_fields['status'] = $_POST['status'];
            $update_fields['operator_id'] = $operator['id'];
            $update_fields['modified_editor'] = $operator['name'];
            $update_fields['date_launch'] = !empty($_POST['date_launch']) ? date('Y-m-d', strtotime($_POST['date_launch'])) : '0000-00-00';
            $update_fields['date_modified'] = Pdb::now();
            $update_fields['changes_made'] = $_POST['changes_made'];

            if ($_POST['type'] == 'redirect') {
                $update_fields['redirect'] = $_POST['redirect'];
            } else if ($_POST['type'] == 'tool') {
                $update_fields['controller_entrance'] = $_POST['controller_entrance'];
                $update_fields['controller_argument'] = $_POST['controller_argument'];
            }

            if ($orig_rev['status'] == 'wip' or $orig_rev['status'] == 'auto_launch') {
                // Update the selected revision
                Pdb::update('page_revisions', $update_fields, ['page_id' => $page_id, 'id' => $rev_id]);

                $res = $this->addHistoryItem($page_id, "Updated revision {$rev_id}");
                if (! $res) return false;

            } else {
                // Create a new revision
                $update_fields['page_id'] = $page_id;
                $update_fields['approval_operator_id'] = (int) @$_POST['approval_operator_id'];
                $update_fields['date_added'] = Pdb::now();

                $rev_id = Pdb::insert('page_revisions', $update_fields);

                $res = $this->addHistoryItem($page_id, "Created new revision {$rev_id}");
                if (! $res) return false;
            }

            // Mark all other live revisions as being old
            if ($_POST['status'] == 'live') {
                Page::activateRevision($rev_id);
            }

            // Widgets
            Pdb::delete('page_widgets', ['page_revision_id' => $rev_id]);

            foreach ($new_widgets as $widget) {
                $update_fields = $widget;
                $update_fields['page_revision_id'] = $rev_id;
                Pdb::insert('page_widgets', $update_fields);
            }
        }

        // Save the custom HEAD tags
        try {
            CustomHeadTags::saveTags('pages', $page_id, $_POST['custom_tags'] ?? []);
        } catch (Exception $ex) {
            Notification::error($ex->getMessage());
            return false;
        }

        // If the save is also requesting approval, generate an approval code
        if ($_POST['status'] == 'need_approval') {
            $approval_code = Security::randStr(12);
            $update_fields = [];
            $update_fields['approval_code'] = $approval_code;
            Pdb::update('page_revisions', $update_fields, ['page_id' => $page_id, 'id' => $rev_id]);
        }

        // Notification
        if (empty($this->in_preview)) {
            switch ($_POST['status']) {
            case 'need_approval':
                Notification::confirm('Your new revision has been saved, and is pending approval');
                Notification::confirm('Now showing current live revision');
                break;
            case 'auto_launch':
                $msg = 'Your new revision has been saved and will be published on ';
                $msg .= date('l, j/n/Y', strtotime($_POST['date_launch']));
                Notification::confirm($msg);
                Notification::confirm('Now showing current live revision');
                break;
            case 'live':
                Notification::confirm('Your changes have been saved and are now live');
                break;
            default:
                Notification::confirm('Your changes have been saved');
            }
        }


        // Admin permissions
        Pdb::delete('page_admin_permissions', ['item_id' => $page_id]);

        if ($_POST['admin_perm_specific'] ?? '' == 1 and !empty($_POST['admin_permissions'])) {
            foreach ($_POST['admin_permissions'] as $id) {
                $id = (int) $id;
                if ($id == 0) continue;

                // Create a new permission record
                $update_fields = array();
                $update_fields['item_id'] = $page_id;
                $update_fields['category_id'] = $id;

                Pdb::insert('page_admin_permissions', $update_fields);
            }
        }


        // User permissions
        if (Register::hasFeature('users')) {
            Pdb::delete('page_user_permissions', ['item_id' => $page_id]);

            if ($_POST['user_perm_specific'] ?? '' == 1 and !empty($_POST['user_permissions'])) {
                foreach ($_POST['user_permissions'] as $id) {
                    $id = (int) $id;
                    if ($id == 0) continue;

                    // Create a new permission record
                    $update_fields = array();
                    $update_fields['item_id'] = $page_id;
                    $update_fields['category_id'] = $id;

                    Pdb::insert('page_user_permissions', $update_fields);
                }
            }
        }


        // Custom attributes
        Pdb::delete('page_attributes', ['page_id' => $page_id]);

        if (!empty($_POST['multiedit_attrs'])) {
            foreach ($_POST['multiedit_attrs'] as $idx => $data) {
                if (MultiEdit::recordEmpty($data)) continue;

                $update_fields = array();
                $update_fields['page_id'] = (int) $page_id;
                $update_fields['name'] = $data['name'];
                $update_fields['value'] = $data['value'];

                Pdb::insert('page_attributes', $update_fields);
            }
        }


        // Do indexing on the page text, which is found in the embedded widgets
        $text = Page::getText($page_id, $rev_id, $_SESSION['admin']['active_subsite']);

        if ($revision_changed) {
            if ($_POST['status'] == 'need_approval') {
                // An email to the operator who is checking the revision
                $view = new PhpView('sprout/email/page_need_check');
                $view->page = $_POST;
                $view->approval_operator = $approval_operator;
                $view->request_operator = $operator;
                $view->url = Sprout::absRoot() . "page/view_specific_rev/{$page_id}/{$rev_id}/{$approval_code}";
                $view->changes_made = $_POST['changes_made'];

                $mail = new Email();
                $mail->AddAddress($approval_operator['email']);
                $mail->Subject = 'Page change approval required for ' . Kohana::config('sprout.site_title');
                $mail->SkinnedHTML($view->render());
                $mail->Send();

                Notification::confirm("An email has been sent to {$approval_operator['name']}");

            } else if ($_POST['status'] == 'live' and Kohana::config('sprout.update_notify')) {
                // A notification to all operators
                $view = new PhpView('sprout/email/page_notify');
                $view->page = $_POST;
                $view->request_operator = $operator;
                $view->url = Sprout::absRoot() . "page/view_specific_rev/{$page_id}/{$rev_id}";
                $view->changes_made = $_POST['changes_made'];

                $email_sql = $operator['email'];
                $q = "SELECT email FROM ~operators WHERE email != '' AND email != ?";
                $res = Pdb::q($q, [$email_sql], 'pdo');
                foreach ($res as $row) {
                    $mail = new Email();
                    $mail->AddAddress($row['email']);
                    $mail->Subject = 'Page updated on site ' . Kohana::config('sprout.site_title') . ': ' . $_POST['name'];
                    $mail->SkinnedHTML($view->render());
                    $mail->Send();
                }
                $res->closeCursor();
            }
        }

        $res = $this->reindexItem($page_id, $_POST['name'], $text);
        if (!$res) Notification::error('Failed to index page text');


        if ($page_type != $orig_page['type']) {
            $this->addHistoryItem($page_id, "Changed the page type");
            if (empty($this->in_preview)) Notification::confirm('Page type has been changed.');
        }


        // Commit
        Pdb::commit();

        Navigation::clearCache();

        // Make sure operator is sent to revision they just modified,
        // instead of going to the current live revision
        if (empty($this->in_preview)) {
            return "admin/edit/page/{$page_id}?revision={$rev_id}";
        }

        return true;
    }


    /**
    * Page organisation tool
    * Bulk renaming, reordering and reparenting
    **/
    public function _extraOrganise()
    {
        $view = new PhpView('sprout/admin/tree_organise');
        $view->root = Navigation::getRootNode();
        $view->controller_name = $this->controller_name;

        return array(
            'title' => 'Organise pages',
            'content' => $view->render()
        );
    }


    /**
    * Page organisation tool
    * Bulk renaming, reordering and reparenting
    **/
    public function _extraMenuGroups()
    {
        $view = new PhpView('sprout/admin/page_menu_groups');
        $view->all_groups = NavigationGroups::getAllGroupsAdmin();
        $view->all_extras = NavigationGroups::getAllExtrasAdmin();

        $enabled_extras = Subsites::getConfigAdmin('nav_extras');
        if ($enabled_extras === null) {
            $enabled_extras = [];
        }
        $view->enabled_extras = $enabled_extras;

        return array(
            'title' => 'Manage menu groups',
            'content' => $view->render()
        );
    }


    /**
    * Save the menu groups
    **/
    public function menuGroupsAction()
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        $enabled_extras = Subsites::getConfigAdmin('nav_extras');
        if ($enabled_extras === null) {
            $enabled_extras = array();
        }

        $all_groups = NavigationGroups::getAllGroupsAdmin();
        foreach ($all_groups as $page_id => $groups) {
            foreach ($groups as $id => $name) {
                $update_data = array();
                $update_data['name'] = @$_POST['groups'][$id]['name'];
                $update_data['date_modified'] = Pdb::now();

                $conditions = array();
                $conditions['subsite_id'] = (int)$_SESSION['admin']['active_subsite'];
                $conditions['page_id'] = (int)$page_id;
                $conditions['id'] = (int)$id;

                Pdb::update('menu_groups', $update_data, $conditions);
            }

            if (array_sum($enabled_extras)) {
                $update_data = array();
                $update_data['subsite_id'] = (int)$_SESSION['admin']['active_subsite'];
                $update_data['page_id'] = (int)$page_id;

                if (!empty($enabled_extras['text'])) {
                    $update_data['text'] = $_POST['extras'][$page_id]['text'];
                }
                if (!empty($enabled_extras['image'])) {
                    if (empty($_POST['extras'][$page_id]['image'])) {
                        $_POST['extras'][$page_id]['image'] = null;
                    }
                    $update_data['image'] = $_POST['extras'][$page_id]['image'];
                }

                try {
                    Pdb::insert('menu_extras', $update_data);
                } catch (Exception $ex) {
                    if (strpos($ex, 'Duplicate entry') === false) throw $ex;
                    unset($update_data['page_id']);
                    Pdb::update('menu_extras', $update_data, array('page_id' => $page_id));
                }
            }
        }

        Notification::confirm('Your changes have been saved');
        Url::redirect('admin/extra/page/menu_groups');
    }


    /**
    * Adds a history item for a page
    **/
    private function addHistoryItem($page_id, $changes_made, $editor = null)
    {
        if ($editor == null) {
            $operator = AdminAuth::getDetails();
            if (! $operator) return false;
            $editor = $operator['name'];
        }

        // Create a new revision
        $update_fields = array();
        $update_fields['page_id'] = $page_id;
        $update_fields['modified_editor'] = $editor;
        $update_fields['changes_made'] = $changes_made;
        $update_fields['date_added'] = Pdb::now();

        $res = Pdb::insert('page_history_items', $update_fields);

        return true;
    }

    /**
    * Returns the edit form for editing the specified page
    *
    * @param int $id The record to show the edit form for
    * @return string The HTML code which represents the edit form
    **/
    public function _getDeleteForm($id)
    {
        $id = (int) $id;

        $view = new PhpView('sprout/admin/page_delete');
        $view->id = $id;

        // Load page details
        $q = "SELECT * FROM ~pages WHERE id = ?";
        try {
            $view->page = Pdb::q($q, [$id], 'row');
        } catch (QueryException $ex) {
            return new AdminError("Invalid id specified - page does not exist");
        }

        // Check permissions
        $res = AdminPerms::checkPermissionsTree('pages', $id);
        if (! $res) return new AdminError("Access denied to delete this page");

        // Children pages
        $root = Navigation::getRootNode();
        $node = $root->findNodeValue('id', $id);
        $child_pages = ($node ? $node->children : []);
        foreach ($child_pages as $child) {
            if (!$child->children) continue;
            foreach ($child->children as $descendent) {
                $child_pages[] = $descendent;
            }
        }
        $view->child_pages = $child_pages;

        return array(
            'title' => 'Deleting page <strong>' . Enc::html($view->page['name']) . '</strong>',
            'content' => $view->render()
        );
    }


    /**
     * Does custom actions before _deleteSave method is called, e.g. extra security checks
     * @param int $item_id The record to delete
     * @return void
     * @throws Exception if the deletion shouldn't proceed for some reason
     */
    public function _deletePreSave($item_id)
    {
        if (!AdminPerms::checkPermissionsTree('pages', $item_id)) {
            throw new Exception('Permission denied');
        }
    }


    /**
     * Does custom actions after the _deleteSave method is called, e.g. clearing cache data
     * @param int $item_id The record to delete
     * @return void
     */
    public function _deletePostSave($item_id)
    {
        Navigation::clearCache();
    }


    /**
    * Shows the links (incoming and outgoing) for a page
    **/
    public function _extraLinks($id)
    {
        $id = (int) $id;

        $res = AdminPerms::checkPermissionsTree('pages', $id);
        if (! $res) return "Access denied to view this page";

        $view = new PhpView('sprout/admin/page_linklist');
        $view->id = $id;

        // Load page details
        $q = "SELECT pages.name, revs.text
            FROM ~pages AS pages
            INNER JOIN ~page_revisions AS revs
                ON revs.page_id = pages.id AND revs.status = ?
            WHERE pages.id = ?";
        $view->page = Pdb::q($q, ['live', $id], 'row');

        $root = Navigation::getRootNode();
        $node = $root->findNodeValue('id', $id);
        $url = $node->getFriendlyUrl();

        // Incoming links
        $q = "SELECT pages.id, pages.name, revs.text
            FROM ~pages AS pages
            INNER JOIN ~page_revisions AS revs
                    ON revs.page_id = pages.id
                   AND revs.status = ?
            WHERE revs.text LIKE CONCAT('%', ?, '%')";
        $res = Pdb::q($q, ['live', Pdb::likeEscape($url)], 'pdo');

        $items = array();
        foreach ($res as $row) {
            $matches = array();
            $match = preg_match('/<a.*?href="' . preg_quote($url, '/') . '".*?>(.+?)<\/a>/', $row->text, $matches);

            if ($match) {
                $items[] = array('id' => $row['id'], 'name' => $row['name'], 'text' => $matches[1]);
            }
        }
        $res->closeCursor();

        $list = new Itemlist();
        $list->items = $items;
        $list->main_columns = array(
            'Page' => 'name',
            'Link text' => 'text',
        );
        $list->addAction('edit', "SITE/admin/edit/{$this->controller_name}/%%");
        $view->incoming = $list->render();


        // Outgoing links
        $matches = array();
        $res = preg_match_all('/<a.*?href="(.+?)".*?>(.+?)<\/a>/i', $view->page->text, $matches, PREG_SET_ORDER);

        $items = array();
        foreach ($matches as $match) {
            $items[] = array('id' => $match[1], 'url' => $match[1], 'text' => $match[2]);
        }

        $list = new Itemlist();
        $list->items = $items;
        $list->main_columns = array(
            'URL' => 'url',
            'Link text' => 'text',
        );
        $list->addAction('edit', '%ne%');
        $view->outgoing = $list->render();


        return array(
            'title' => 'Links for page <strong>' . Enc::html($view->page->name) . '</strong>',
            'content' => $view->render()
        );
    }



    /**
    * Returns the intro HTML for this controller.
    * Looks for a view named "admin/<controller-name>_intro", and loads it if found.
    * Otherwise, loads the view, "admin/generic_intro".
    **/
    public function _intro()
    {
        $intro = new PhpView("sprout/admin/page_intro");
        $intro->controller_name = $this->controller_name;
        $intro->friendly_name = $this->friendly_name;


        // Recently updated pages
        $q = "SELECT pages.id, pages.name, DATE_FORMAT(pages.date_modified, '%d/%m/%Y') AS date_modified,
                pages.modified_editor
            FROM ~pages AS pages
            WHERE subsite_id = ?
            ORDER BY pages.date_modified DESC
            LIMIT 5";
        $res = Pdb::q($q, [$_SESSION['admin']['active_subsite']], 'arr');

        // Create the itemlist
        $itemlist = new Itemlist();
        $itemlist->main_columns = array('Name' => 'name', 'Date modified' => 'date_modified', 'Editor' => 'modified_editor');
        $itemlist->items = $res;
        $itemlist->addAction('edit', "SITE/admin/edit/{$this->controller_name}/%%");

        $intro->recently_updated = $itemlist->render();


        // Changes needing approval
        if (AdminPerms::canAccess('access_noapproval')) {
            $q = "SELECT pages.id, pages.name, DATE_FORMAT(page_revisions.date_modified, '%d/%m/%Y') AS date_modified,
                    page_revisions.modified_editor
                FROM ~page_revisions AS page_revisions
                INNER JOIN ~pages AS pages ON page_revisions.page_id = pages.id
                WHERE page_revisions.status = ?
                    AND subsite_id = ?
                ORDER BY page_revisions.date_modified DESC
                LIMIT 5";
            $res = Pdb::q($q, ['need_approval', $_SESSION['admin']['active_subsite']], 'arr');

            // Create the itemlist
            $itemlist = new Itemlist();
            $itemlist->main_columns = [
                'Name' => 'name',
                'Date modified' => 'date_modified',
                'Editor' => 'modified_editor'
            ];
            $itemlist->items = $res;
            $itemlist->addAction('edit', 'SITE/admin/edit/page/%%#main-tabs-revs');

            $intro->need_approval = $itemlist->render();
        }


        return $intro->render();
    }


    /**
    * Does a re-index for a page
    **/
    private function reindexItem($item_id, $name, $text)
    {
        Search::selectIndex('page_keywords', $item_id);

        $res = Search::clearIndex();
        if (! $res) return false;

        $res = Search::indexHtml($text, 1);
        if (! $res) return false;

        $res = Search::indexText($name, 4);
        if (! $res) return false;

        $res = Search::cleanup('pages');
        if (! $res) return false;

        return true;
    }

    /**
    * Does a complete re-index of all pages
    **/
    public function reindexAll()
    {
        AdminAuth::checkLogin();

        Pdb::transact();

        $q = "SELECT pages.id, pages.name, pages.active, widget.settings
            FROM ~pages AS pages
            INNER JOIN ~page_revisions AS rev
                ON rev.page_id = pages.id AND rev.status = ?
            LEFT JOIN ~page_widgets AS widget ON rev.id = widget.page_revision_id
                AND widget.area_id = 1 AND widget.active = 1 AND widget.type = 'RichText'
            ORDER BY widget.record_order";
        $res = Pdb::q($q, ['live'], 'pdo');

        $pages = [];
        foreach ($res as $row) {
            if ($row['settings'] == null or $row['active'] == 0) {
                $pages[$row['id']] = ['name' => $row['name'], 'text' => ''];
                continue;
            }
            $settings = json_decode($row['settings'], true);
            if (!isset($pages[$row['id']])) {
                $pages[$row['id']] = ['name' => $row['name'], 'text' => $settings['text']];
            } else {
                $pages[$row['id']]['text'] .= "\n" . $settings['text'];
            }
        }
        $res->closeCursor();

        if (count($pages) == 0) {
            echo '<p>Nothing to index</p>';
            Pdb::rollback();
            return;
        }

        foreach ($pages as $id => $page) {
            $this->reindexItem($id, $page['name'], $page['text']);
        }

        Pdb::commit();

        echo '<p>Success</p>';
    }


    /**
     * Validate given controller implements FrontEndEntrance
     *
     * @param string $controller Controller class
     * @param int $page_id Page record ID
     * @return bool True on success. False on failure
     */
    private static function checkControllerEntrance($controller, $page_id)
    {
        $page_id = (int) $page_id;

        $front_end_controllers = Register::getFrontEndControllers();
        if (empty($front_end_controllers[$controller])) return false;

        $inst = Sprout::instance($controller);
        if (!($inst instanceof FrontEndEntrance)) return false;

        return true;
    }


    /**
    * Returns the children pages for a specific page, in a format required by jqueryFileTree.
    * Uses the POST param 'dir', and is usually run through an AJAX call.
    **/
    public function filetreeOpen()
    {
        AdminAuth::checkLogin();

        $_POST['dir'] = trim($_POST['dir']);
        $_GET['record_id'] = (int) $_GET['record_id'];

        Navigation::loadPageTree($_SESSION['admin']['active_subsite'], true);

        $root = Navigation::getRootNode();
        $top_node = $root->findNodeValue('id', basename($_POST['dir']));

        $nav_limit = Subsites::getConfigAdmin('nav_limit');
        if (! $nav_limit) $nav_limit = 99999;
        if (Subsites::getConfigAdmin('nav_home')) $nav_limit--;

        echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";

        // This item
        $dir_item_path = preg_replace('!^/(.+)/$!', '$1', $_POST['dir']);
        if ($dir_item_path != '/') {
            $dir_item_name = basename($_POST['dir']);

            $name = $top_node['name'];
            if (strlen($name) > 25) $name = substr($name, 0, 25) . '...';
            $name = Enc::html($name);

            $rel = Enc::html('/' . $dir_item_path);

            $admin_perms = AdminPerms::checkPermissionsTree('pages', $top_node['id']);

            $class = ($admin_perms ? ' allow-access' : ' no-access');
            if ($top_node['id'] == $_GET['record_id']) $class .= ' current-edit';
            echo "<li class=\"file ext_txt{$class} directory-item\"><a href=\"#\" rel=\"{$rel}\">{$name}</a></li>";
        }

        // Children of this item
        foreach ($top_node->children as $child) {
            $name = $child['name'];
            if (strlen($name) > 25) $name = substr($name, 0, 25) . '...';
            $name = Enc::html($name);

            $rel = Enc::html($_POST['dir'] . $child['id']);

            $admin_perms = AdminPerms::checkPermissionsTree('pages', $child['id']);

            $class = ($admin_perms ? ' allow-access' : ' no-access');
            if (count($child->children) > 0 and $admin_perms) {
                echo "<li class=\"directory collapsed{$class}\"><a href=\"#\" rel=\"{$rel}/\">{$name}</a></li>";
            } else {
                if ($child['id'] == $_GET['record_id']) $class .= ' current-edit';
                echo "<li class=\"file ext_txt{$class}\"><a href=\"#\" rel=\"{$rel}\">{$name}</a></li>";
            }

            if ($dir_item_path == '/') {
                $nav_limit--;
                if ($nav_limit == 0) {
                    echo "<li class=\"nav-limit\">&nbsp;</li>";
                }
            }
        }

        echo "</ul>";

        if ($dir_item_path != '/') {
            echo "<p class=\"tree-extras\">";
            echo "&#43; <a href=\"SITE/admin/add/page?parent_id={$top_node['id']}\">Add Child</a>";
            echo " &nbsp; ";
            echo "&#8597; <a href=\"SITE/page/reorder/{$top_node['id']}\" onclick=\"$.facebox({'ajax':this.href}); return false;\">Re-order</a>";
            echo "</p>";
        }

        if ($_SESSION['admin'][$this->controller_name . '_nav'] == null) $_SESSION['admin'][$this->controller_name . '_nav'] = array();
        if ($_POST['dir'] != '/' and !in_array ($_POST['dir'], $_SESSION['admin'][$this->controller_name . '_nav'])) {
            $_SESSION['admin'][$this->controller_name . '_nav'][] = $_POST['dir'];
        }
    }

    /**
    * Saves in the session data the currently open pages in the pages tree (navigation pane)
    * Uses the POST param 'pages', and is usually run through an AJAX call.
    **/
    public function filetreeClose()
    {
        AdminAuth::checkLogin();

        if (empty($_SESSION['admin'][$this->controller_name . '_nav'])) return;

        $index = array_search ($_POST['dir'], $_SESSION['admin'][$this->controller_name . '_nav']);
        unset ($_SESSION['admin'][$this->controller_name . '_nav'][$index]);
    }


    /**
    * Shows the reorder screen (which is shown in a popup box) for re-ordering the top-level stuff
    * This custom version adds subsite support
    **/
    public function reorderTop()
    {
        AdminAuth::checkLogin();

        // Get children
        $q = "SELECT id, name
            FROM ~{$this->table_name}
            WHERE subsite_id = ? AND parent_id = 0
            ORDER BY record_order";
        $children = Pdb::q($q, [$_SESSION['admin']['active_subsite']], 'arr');

        // If there is only one child, complain that it's impossible to re-order
        if (count($children) == 1) {
            echo "<p>This site does not have enough top-level items for ordering.</p>";
            return;
        }

        // View
        $view = new PhpView('sprout/admin/categories_reorder');
        $view->id = 0;
        $view->items = $children;
        $view->controller_name = $this->controller_name;
        $view->friendly_name = $this->friendly_name;

        echo $view->render();
    }

    /**
     * If the specified item needs a record number to be set,
     * Puts this item at the end of the list.
     *
     * This custom version adds subsite support
     *
     * @param int $item_id Record-id to update
     */
    protected function fixRecordOrder($item_id)
    {
        $q = "SELECT record_order, subsite_id, parent_id FROM ~{$this->table_name} WHERE id = ?";
        $item = Pdb::q($q, [$item_id], 'row');

        if ($item['record_order'] != 0) return;

        $q = "SELECT MAX(record_order) AS m
            FROM ~{$this->table_name}
            WHERE subsite_id = ?
            AND parent_id = ?";
        try {
            $max = Pdb::q($q, [$item['subsite_id'], $item['parent_id']], 'val');
        } catch (QueryException $ex) {
            return;
        }

        $q = "UPDATE ~{$this->table_name} SET record_order = ? WHERE id = ?";
        Pdb::q($q, [$max + 1, $item_id], 'count');
    }


    /**
    * Activates AutoLaunch revisions
    **/
    public function cronPageActivate()
    {
        Cron::start('Activate pages');

        try {
            Pdb::transact();

            // Find revisions needing launch
            $q = "SELECT id, page_id
                FROM ~page_revisions
                WHERE status = ? AND date_launch <= NOW()";
            $res = Pdb::q($q, ['auto_launch'], 'arr');

            foreach ($res as $row) {
                Cron::message("Launching revision {$row['id']} on page {$row['page_id']}");

                // Launch revision
                Pdb::update('page_revisions', ['status' => 'live'], ['id' => $row['id']]);

                $this->addHistoryItem($row['page_id'], "AutoLaunched revision {$row['id']}", 'n/a');

                $where = [
                    'page_id' => $row['page_id'],
                    ['id', '!=', $row['id']],
                    'status' => 'live',
                ];
                Pdb::update('page_revisions', ['status' => 'old'], $where);
            }

            Pdb::commit();
        } catch (QueryException $ex) {
            return Cron::failure('Database error');
        }

        Navigation::clearCache();

        Cron::success();
    }


    /**
    * Deactivates pages after set date
    **/
    public function cronPageDeactivate()
    {
        Cron::start('De-activate pages');

        try {
            Pdb::transact();

            // Find revisions needing launch
            $q = "SELECT id
                FROM ~pages
                WHERE active = 1
                    AND date_expire != '0000-00-00'
                    AND date_expire IS NOT NULL
                    AND date_expire <= NOW()";
            $res = Pdb::q($q, [], 'arr');

            foreach ($res as $row) {
                Cron::message("De-activating page {$row['id']}");

                // Deactivate page
                Pdb::update('pages', ['active' => 0], ['id' => $row['id']]);

                $this->addHistoryItem($row['id'], "Deactivated page {$row['id']}", 'n/a');
            }

            Pdb::commit();
        } catch (QueryException $ex) {
            return Cron::failure('Database error');
        }

        Navigation::clearCache();

        Cron::success();
    }


    /**
    * Returns the tools to show in the left navigation
    **/
    public function _getTools()
    {
        $items = array();

        if (count(Register::getDocImports()) > 0) {
            $items[] = "<li class=\"import\"><a href=\"SITE/admin/import_upload/page\">Document import</a></li>";
        }

        $items[] = "<li class=\"action-log\"><a href=\"SITE/admin/search/{$this->controller_name}\">Search pages</a></li>";

        if (AdminPerms::canAccess('access_noapproval')) {
            $items[] = "<li class=\"action-log\"><a href=\"admin/extra/page/need_approval\">Pages needing approval</a></li>";
        }

        if (AdminAuth::isSuper() or Subsites::getConfigAdmin('nav_reorder')) {

            $items[] = "<li class=\"reorder\"><a href=\"admin/call/{$this->controller_name}/reorderTop\" onclick=\"$.facebox({'ajax':this.href}); return false;\">Reorder top-level</a></li>";
        }

        if (AdminAuth::isSuper()) {
            $items[] = "<li class=\"config\"><a href=\"admin/extra/page/organise\">Sitemap manager</a></li>";
        }

        if (Subsites::getConfigAdmin('nav_groups') !== null) {
            $items[] = "<li class=\"config\"><a href=\"admin/extra/page/menu_groups\">Manage menu groups</a></li>";
        }

        $items[] = "<li class=\"config\"><a href=\"admin/extra/page/link_checker\">Link checker</a></li>";

        if (Kohana::config('cache.enabled')) {
            $items[] = "<li class=\"config\"><a href=\"page/clear_navigation_cache\">Clear navigation cache</a></li>";
        }

        if ($this->_actionLog()) {
            $tools[] = '<li class="action-log"><a href="SITE/admin/contents/action_log?record_table=' . $this->getTableName() . '">View action log</a></li>';
        }

        return $items;
    }


    /**
    * Starts the link checker
    **/
    public function _extraLinkChecker()
    {
        $view = new PhpView('sprout/admin/link_checker');
        $view->ops = AdminPerms::getOperatorsWithAccess('access_reportemail');

        $details = AdminAuth::getDetails();
        $view->email = $details['email'];

        return array(
            'title' => 'Link Checker',
            'content' => $view->render(),
        );
    }


    /**
    * Starts the link checker
    **/
    public function linkCheckerAction()
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        $_POST['send_to'] = trim($_POST['send_to']);
        $_POST['email'] = trim($_POST['email']);

        if ($_POST['send_to'] != 'admins' and $_POST['send_to'] != 'specific') {
            Notification::error("Invalid 'send_to' argument");
            Url::redirect('admin/extra/page/link_checker');
        }

        if ($_POST['send_to'] == 'specific' and $_POST['email'] == '') {
            Notification::error("You didn't enter an email address");
            Url::redirect('admin/extra/page/link_checker');
        }

        try {
            if ($_POST['send_to'] == 'admins') {
                $info = WorkerCtrl::start('Sprout\\Helpers\\WorkerLinkChecker');
            } else if ($_POST['send_to'] == 'specific') {
                $info = WorkerCtrl::start('Sprout\\Helpers\\WorkerLinkChecker', $_POST['email']);
            }
        } catch (WorkerJobException $ex) {
            Notification::error("Unable to start background process: {$ex->getMessage()}");
            Url::redirect('admin/extra/page/link_checker');
        }

        Notification::confirm("Background process started");
        Url::redirect('admin/extra/page/link_checker_info/' . $info['job_id']);
    }


    /**
    * Tells the user the link checker is running
    **/
    public function _extraLinkCheckerInfo($id)
    {
        $id = (int) $id;

        $out = '';
        $out .= '<h3>Job started</h3>';
        $out .= '<p>The link checker background process has been started.</p>';
        $out .= '<p>An email will be sent once it is complete.</p>';

        return array(
            'title' => 'Link Checker',
            'content' => $out,
        );
    }


    /**
     * List of pages which need approval
     */
    public function _extraNeedApproval()
    {
        if (!AdminPerms::canAccess('access_noapproval')) {
            return 'Access denied';
        }

        $q = "SELECT pages.id, pages.name, revs.date_modified, revs.modified_editor
            FROM ~page_revisions AS revs
            INNER JOIN ~pages AS pages ON revs.page_id = pages.id
            WHERE revs.status = 'need_approval'
                AND pages.subsite_id = ?
            GROUP BY pages.id
            ORDER BY revs.date_modified DESC
            LIMIT 25";
        $res = Pdb::query($q, [$_SESSION['admin']['active_subsite']], 'arr');

        $itemlist = new Itemlist();
        $itemlist->main_columns = [
            'Name' => 'name',
            'Date modified' => [new ColModifierDate(), 'date_modified'],
            'Editor' => 'modified_editor'
        ];
        $itemlist->items = $res;
        $itemlist->addAction('edit', 'admin/edit/page/%%');

        return array(
            'title' => 'Pages needing approval',
            'content' => $itemlist->render(),
        );
    }


    /**
    * Cron version of the link checker
    **/
    public function cronLinkChecker()
    {
        Cron::start('Link Checker');
        WorkerCtrl::start('Sprout\\Helpers\\WorkerLinkChecker');
        Cron::success();
    }


    /**
     * Check for stale page content and send emails regarding stale pages; to be run via cron
     *
     * @return void
     */
    public function cronCheckStale()
    {
        Cron::start('Stale page checker');

        $email = Kohana::config('sprout.stale_page_email');
        $default_max_age = Kohana::config('sprout.stale_page_age');
        $resend_interval = (int) Kohana::config('sprout.stale_page_resend_after');
        if ($resend_interval <= 0) $resend_interval = 7;

        $q = "SELECT page.id, page.subsite_id, page.name,
                rev.modified_editor, DATEDIFF(NOW(), rev.date_modified) AS age,
                op.id AS op_id, op.name AS operator, op.email
            FROM ~pages AS page
            INNER JOIN ~page_revisions AS rev
                ON page.id = rev.page_id AND rev.status = 'live' AND rev.type = 'standard'
            LEFT JOIN ~operators AS op
                ON rev.operator_id = op.id
            WHERE page.active = 1
                AND DATE_SUB(CURDATE(), INTERVAL ? DAY) >= page.stale_reminder_sent
                AND IFNULL(page.stale_age, ?) > 0
                AND DATEDIFF(NOW(), rev.date_modified) >= IFNULL(page.stale_age, ?)
            ORDER BY age DESC, page.id";
        $res = Pdb::q($q, [$resend_interval, $default_max_age, $default_max_age], 'map-arr');

        if (count($res) == 0) {
            Cron::message('No stale pages found');
            Cron::success();
            return;
        }

        $op_emails = [];

        if ($email and !preg_match('/example\.com$/', $email)) {
            $op_emails[0] = ['email' => $email, 'pages' => []];
        }

        foreach ($res as $id => $row) {
            $url = Page::url($id);
            if (!$url) continue;

            $url = Subsites::getAbsRoot($row['subsite_id']) . $url;

            $op_emails[0]['pages'][] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'age' => $row['age'],
                'editor' => $row['modified_editor'],
                'url' => $url,
            ];

            if (!$row['email']) continue;

            $op = $row['op_id'];
            if (!isset($op_emails[$op])) {
                $op_emails[$op] = [
                    'email' => $row['email'],
                    'operator' => $row['operator'],
                    'pages' => [],
                ];
            }

            $op_emails[$op]['pages'][] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'age' => $row['age'],
                'url' => $url,
            ];
        }

        foreach ($op_emails as $id => $details) {
            if (empty($details['email'])) continue;
            if (count($details['pages']) == 0) continue;

            $msg = "Sending email to {$details['email']} about ";
            $msg .= Inflector::numPlural(count($details['pages']), 'page');
            Cron::message($msg);

            $view = new PhpView('sprout/email/pages_stale');
            $view->show_op = ($id == 0);
            $view->pages = $details['pages'];
            $view->base = Sprout::absRoot();

            $mail = new Email();
            $mail->Subject = 'Stale content warning';
            if (!empty($details['operator'])) {
                $mail->addAddress($details['email'], $details['operator']);
            } else {
                $mail->addAddress($details['email']);
            }
            $mail->skinnedHTML($view);
            $mail->send();
        }

        Cron::message("Marking pages as sent");

        $params = [date('Y-m-d')];
        $conds = [['id', 'IN', array_keys($res)]];
        $where = Pdb::buildClause($conds, $params);

        $count = Pdb::q("UPDATE ~pages SET stale_reminder_sent = ? WHERE {$where}", $params, 'count');
        Cron::message(Inflector::numPlural($count, 'page') . " marked as sent");

        Cron::success();
    }


    /**
    * Forces a clear of the pagecache
    **/
    public function clearNavigationCache()
    {
        AdminAuth::checkLogin();

        Navigation::clearCache();

        Notification::confirm('Page cache has been cleared');
        Url::redirect('admin/intro/page');
    }


    public function preview($item_id = 0) {
        $item_id = (int) $item_id;

        $tables = [
            'pages' => 1,
            'page_revisions' => ['id' => $_POST['rev_id']],
            'page_widgets' => 0,
            'page_history_items' => 0,
            'page_admin_permissions' => 1,
            'page_user_permissions' => 1,
            'page_attributes' => 0,
            'page_keywords' => 0,
            'search_keywords' => 0,
        ];

        // Make sure the resulting revision is live so it's the revision displayed in the preview, even though it might
        // be saved under a different status once the current administrator is happy with the preview
        $_POST['status'] = 'live';

        $this->in_preview = true;
        $item_id = Preview::load($this, $tables, $item_id);

        $ctlr = new PageController();
        Preview::run($ctlr, 'viewById', [$item_id]);
    }


    /**
     * Return JSON list of custom widget templates as defined by skin config
     * AJAX called
     *
     * @param string $_GET['template'] Template filename
     * @return void Echos HTML directly
     */
    public function ajaxListWidgetTemplates()
    {
        $templates = Kohana::config('sprout.widget_templates');
        Form::setData(['template' => @$_GET['template']]);
        $out = '';

        Form::nextFieldDetails('Template', false);
        $out .= Form::dropdown('template', [], $templates);

        // Render Save button
        $out .= '<div class="-clearfix"><button class="save-changes-save-button button button-green icon-after icon-save" type="submit">Save changes</button></div>';

        echo $out;
    }


    /**
     * Render form to set the columns on the page widget
     *
     * @return void Echo HTML directly
     */
    public function columnSettingsForm()
    {
        Form::setData(['columns' => !empty($_GET['columns']) ? $_GET['columns'] : '1st']);
        $out = '';

        Form::nextFieldDetails('Column', false);
        $out .= Form::dropdown('columns', [], Pdb::extractEnumArr('page_widgets', 'columns'));

        // Render Save button
        $out .= '<div class="-clearfix"><button class="save-changes-save-button button button-green icon-after icon-save" type="submit">Save changes</button></div>';

        echo $out;
    }
}
