<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class SubsiteModel extends Model
{
    /** @var bool */
    public bool $active = true;

    /** @var string */
    public string $name;

    /** @var string */
    public string $code;

    /** @var string|null */
    public ?string $cond_domain = null;

    /** @var string|null */
    public ?string $cond_directory = null;

    /** @var int */
    public int $mobile;

    /** @var int */
    public int $content_id;

    /** @var int */
    public int $admin_perm_category;

    /** @var int */
    public int $require_admin;

    /** @var int */
    public int $require_user;

    /** @var int */
    public int $record_order = 0;

    /** @var string|null */
    public ?string $date_added = null;

    /** @var string|null */
    public ?string $date_modified = null;


    public static function getTableName(): string
    {
        return 'subsites';
    }
}
