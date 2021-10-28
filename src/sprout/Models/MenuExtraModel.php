<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class MenuExtraModel extends Model
{
    /** @var int */
    public $subsite_id;

    /** @var int */
    public $page_id;

    /** @var string */
    public $text;

    /** @var int|null */
    public $image;



    public static function getTableName(): string
    {
        return 'menu_extras';
    }
}
