<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class MenuGroupModel extends Model
{
    /** @var int */
    public int $subsite_id;

    /** @var int */
    public int $page_id;

    /** @var int */
    public int $position;

    /** @var string|null */
    public ?string $date_added = null;

    /** @var string|null */
    public ?string $date_modified = null;


    public static function getTableName(): string
    {
        return 'menu_groups';
    }
}
