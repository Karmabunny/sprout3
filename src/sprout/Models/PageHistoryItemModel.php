<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class PageHistoryItemModel extends Model
{
    /** @var string */
    public $changes_made;


    public static function getTableName(): string
    {
        return 'page_history_items';
    }
}
