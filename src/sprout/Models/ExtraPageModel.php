<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class ExtraPageModel extends Model
{
    /** @var int */
    public $subsite_id;

    /** @var string */
    public $type;

    /** @var string */
    public $text;


    public static function getTableName(): string
    {
        return 'extra_pages';
    }
}
