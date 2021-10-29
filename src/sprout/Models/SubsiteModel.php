<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class SubsiteModel extends Model
{
    /** @var int */
    public $active;

    /** @var string */
    public $name;

    /** @var string */
    public $code;

    /** @var string|null */
    public $cond_domain;

    /** @var string|null */
    public $cond_directory;

    /** @var int */
    public $mobile;

    /** @var int */
    public $content_id;

    /** @var int */
    public $admin_perm_category;

    /** @var int */
    public $require_admin;

    /** @var int */
    public $require_user;

    /** @var int */
    public $record_order;

    /** @var string|null */
    public $date_added;

    /** @var string|null */
    public $date_modified;


    public static function getTableName(): string
    {
        return 'subsites';
    }
}
