<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class PageAtrributesModel extends Model
{
    /** @var int */
    public int $page_id;

    /** @var string */
    public string $name;

    /** @var string */
    public string $value = '';


    public static function getTableName(): string
    {
        return 'page_attributes';
    }
}
