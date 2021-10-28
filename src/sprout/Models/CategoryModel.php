<?php
namespace Sprout\Models;

use Sprout\Helpers\Category;
use Sprout\Helpers\Model;


class CategoryModel extends Model
{
    protected $table;


    public static function getTableName(): string
    {
        return self::$table;
    }


    public static function setTableName($main_table): void
    {
        self::$table = Category::tableMain2cat($main_table);
    }
}
