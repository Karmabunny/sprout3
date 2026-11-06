<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class PageModel extends Model
{
    /** @var int */
    public int $parent_id;

    /** @var int */
    public int $subsite_id;

    /** @var string */
    public string $name;

    /** @var string */
    public string $slug;

    /** @var bool */
    public bool $active = true;

    /** @var bool */
    public bool $show_in_nav = true;

    /** @var int */
    public int $menu_group;

    /** @var string|null */
    public ?string $meta_keywords;

    /** @var string|null */
    public ?string $meta_description;

    /** @var string|null */
    public ?string $alt_browser_title;

    /** @var string|null */
    public ?string $alt_nav_title;

    /** @var int|null */
    public ?int $banner;

    /** @var int|null */
    public ?int $gallery_thumb;

    /** @var string|null */
    public ?string $alt_template;

    /** @var int */
    public int $admin_perm_type;

    /** @var int */
    public int $user_perm_type;

    /** @var int */
    public int $hit_count;

    /** @var int */
    public int $record_order = 0;

    /** @var string|null */
    public ?string $modified_editor;

    /** @var string|null */
    public ?string $additional_css;

    /** @var string|null */
    public ?string $date_expire;

    /** @var int|null */
    public ?int $stale_age = null;

    /** @var string */
    public string $stale_reminder_sent;

    /** @var string|null */
    public ?string $date_added = null;

    /** @var string|null */
    public ?string $date_modified = null;


    public static function getTableName(): string
    {
        return 'pages';
    }
}
