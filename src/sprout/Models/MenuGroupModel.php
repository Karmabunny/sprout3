<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class MenuGroupModel extends Model
{
    /** @var int */
    public $subsite_id;

    /** @var int */
    public $page_id;

    /** @var int */
    public $position;


    public static function getTableName(): string
    {
        return 'menu_groups';
    }
}
