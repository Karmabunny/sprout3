<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class PageAtrributesModel extends Model
{
    /** @var int */
    public $page_id;

    /** @var string|null */
    public $name;

    /** @var string */
    public $value;


    public static function getTableName(): string
    {
        return 'page_attributes';
    }
}
