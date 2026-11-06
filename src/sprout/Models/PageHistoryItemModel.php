<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class PageHistoryItemModel extends Model
{
    /** @var int */
    public int $page_id;

    /** @var string */
    public string $modified_editor;

    /** @var string */
    public string $changes_made;

    /** @var string|null */
    public ?string $date_added = null;


    public static function getTableName(): string
    {
        return 'page_history_items';
    }
}
