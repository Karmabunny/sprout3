<?php
namespace Sprout\Helpers;

use DOMDocument;
use SimpleXMLElement;
use Exception;

use Sprout\Helpers\Enc;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Slug;
use Sprout\Helpers\WidgetArea;


class ImportCMS
{
    private static $page_ids = [];
    private static $revision_ids = [];
    private static $old_widets = [];


    /**
     * Process given XML file into Sprout CMS 3 pages
     *
     * @param string $filename Path and filename of XML
     * @return array
     */
    public static function import($filename)
    {
        // Load XML document
        $xml = new SimpleXMLElement(file_get_contents($filename), LIBXML_NOCDATA);
        if (!$xml) throw new Exception('Unable to parse XML');

        Pdb::transact();

        // Slug conditions
        $conds = [
            'subsite_id' => $_POST['subsite_id'],
            'parent_id' => $_POST['page_id']
        ];

        // Fetch record order
        $q = "SELECT
                MAX(page.record_order) AS o
            FROM
                ~pages AS page
            WHERE
                page.parent_id = ?";

        $record_order = Pdb::query($q, [$_POST['page_id']], 'val');
        $record_order ++;

        // Create pages and content widgets
        foreach ($xml->page as $page) {
            self::processXmlPage($page, $record_order ++, $_POST['page_id'], $_POST['subsite_id']);
        }

        self::updateContent();

        Pdb::commit();

        $results = [];
        foreach (self::$page_ids as $old_id => $new_id) {
            $results[] = [
                'old_id' => $old_id,
                'new_id' => $new_id,
                'widgets' => @self::$old_widets[$old_id],
            ];
        }

        return $results;
    }


    /**
     * Process DOMElement into page record with content
     *
     * @param DomElement $page
     * @param int $record_order
     * @param int $parent_id
     * @param int $subsite_id
     * @param int $depth Number of recursions
     * @return void
     */
    private static function processXmlPage($page, $record_order, $parent_id, $subsite_id)
    {
        // Create page record
        $fields = [];
        $fields['parent_id'] = $parent_id;
        $fields['subsite_id'] = $subsite_id;
        $fields['record_order'] = $record_order ++;
        $fields['name'] = trim((string) $page['name']);
        $fields['active'] = ((string)$page['active'] ? 1 : 0);
        $fields['show_in_nav'] = ((string)$page['menu'] ? 1 : 0);
        $fields['menu_group'] = (int) !empty($page['menu-group-position'])? (string) $page['menu-group-position'] : 0;
        $fields['modified_editor'] = 'Import tool';
        $fields['date_added'] = Pdb::now();
        $fields['date_modified'] = Pdb::now();

        try {
            $fields['slug'] = Slug::unique(Enc::urlname($fields['name'], '-'), 'pages', $conds);
        } catch (Exception $ex) {
            $fields['slug'] = Slug::create('pages', $fields['name']);
        }

        $page_id = Pdb::insert('pages', $fields);

        // Map old page ID to new page ID
        self::$page_ids[(int) $page['id']] = $page_id;

        // Add first revision
        $fields = [];
        $fields['page_id'] = $page_id;
        $fields['type'] = 'standard';
        $fields['status'] = 'live';
        $fields['changes_made'] = 'Import of existing content';
        $fields['modified_editor'] = 'Import tool';
        $fields['date_added'] = Pdb::now();
        $fields['date_modified'] = Pdb::now();

        $revision_id = Pdb::insert('page_revisions', $fields);

        // Map old page ID to new page ID
        self::$revision_ids[(int) $page['id']] = $revision_id;

        // Content
        $html = (string) $page->content[0];

        // Widget area
        $area = WidgetArea::findAreaByName('embedded');

        // RichText widget
        $fields = [];
        $fields['page_revision_id'] = $revision_id;
        $fields['area_id'] = $area->getIndex();
        $fields['active'] = 1;
        $fields['record_order'] = 0;
        $fields['type'] = 'RichText';
        $fields['settings'] = json_encode(['text' => $html]);

        Pdb::insert('page_widgets', $fields);

        if (empty($page->children->page)) return;

        $record_order = 1;
        foreach ($page->children->page as $child_page) {
            self::processXmlPage($child_page, $record_order++, $page_id, $subsite_id);
        }
    }


    /**
     * Update content for Sprout CMS 3
     *
     * @return void
     */
    private static function updateContent()
    {
        $params = [];
        $conditions = [];

        $conditions[] = ['widget.page_revision_id', 'IN', self::$revision_ids];
        $where = Pdb::buildClause($conditions, $params);

        $q = "SELECT
                widget.id,
                widget.page_revision_id,
                widget.settings
            FROM
                ~page_widgets AS widget
            WHERE
                {$where}";

        $widgets = Pdb::query($q, $params, 'arr');

        foreach ($widgets as $widget) {
            $settings = json_decode($widget['settings']);

            $settings->text = self::replacePageIds($settings->text);
            $settings->text = self::replaceFileUrls($settings->text);

            self::findOldWidgets($widget['page_revision_id'], $settings->text);

            Pdb::update('page_widgets', ['settings' => json_encode(['text' => $settings->text])], [['id', '=', $widget['id']]]);
        }
    }


    /**
     * Replace page IDs within page URLS
     *
     * @param string $html HTML content
     * @return string HTML with replaced URLs
     */
    private static function replacePageIds($html)
    {
        return preg_replace_callback('!href="page/view_by_id/([0-9]+)"!', function ($matches)
        {
            if (isset(self::$page_ids[$matches[1]])) return 'href="page/view_by_id/' . self::$page_ids[$matches[1]] . '"';
            return 'href="unknown_page_' . $matches[1] . '"';
        }, $html);
    }


    /**
     * Report pages that contain old widgets
     *
     * @param string $html HTML content
     * @return void
     */
    private static function findOldWidgets($rev_id, $html)
    {
        $matches = [];
        preg_match_all(
            '/\(\(WIDGET [a-zA-Z]*? ?([0-9A-Za-z]+)\)\)/',
            $html,
            $matches
        );

        if (empty($matches[0]) or count($matches[0]) == 0) return;

        self::$old_widets[array_search($rev_id, self::$revision_ids)] = implode(' ~ ', $matches[0]);
    }


    /**
     * Replace file URLs
     * FROM: files/1702_icon_joint.small.png
     * TO: file/download/1702/small
     *
     *
     * @param string $html HTML content
     * @return string HTML with replaced file URLs
     */
    private static function replaceFileUrls($html)
    {
        return preg_replace_callback('!(src|href)="files/([0-9]+)_[_.0-9A-Za-z]+"!', function($matches)
        {
            $url = $matches[1] . '="file/download/' . $matches[2];

            if (strpos($matches[0], '.small.') !== false) {
                $url .= '/small';
            } else if (strpos($matches[0], '.medium.') !== false) {
                $url .= '/medium';
            } else if (strpos($matches[0], '.large.') !== false) {
                $url .= '/large';
            }

            return $url . '"';

        }, $html);
    }
}
