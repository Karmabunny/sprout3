<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class PageHistoryItemModel extends Model
{
    /** @var int */
    public $page_id;

    /** @var string */
    public $modified_editor;

    /** @var string */
    public $changes_made;

    /** @var string|null */
    public $date_added;


    public static function getTableName(): string
    {
        return 'page_history_items';
    }
}
