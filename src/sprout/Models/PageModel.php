<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class PageModel extends Model
{
    /** @var int */
    public $parent_id;

    /** @var int */
    public $subsite_id;

    /** @var string */
    public $slug;

    /** @var int */
    public $show_in_nav;

    /** @var int */
    public $menu_group;

    /** @var string|null */
    public $meta_keywords;

    /** @var string|null */
    public $meta_description;

    /** @var string|null */
    public $alt_browser_title;

    /** @var string|null */
    public $alt_nav_title;

    /** @var int|null */
    public $banner;

    /** @var int|null */
    public $gallery_thumb;

    /** @var string|null */
    public $alt_template;

    /** @var int */
    public $admin_perm_type;

    /** @var int */
    public $user_perm_type;

    /** @var int */
    public $hit_count;

    /** @var int */
    public $record_order;

    /** @var string|null */
    public $modified_editor;

    /** @var string|null */
    public $additional_css;

    /** @var string|null */
    public $date_expire;

    /** @var int|null */
    public $stale_age;

    /** @var string */
    public $stale_reminder_sent;


    public static function getTableName(): string
    {
        return 'pages';
    }
}
