<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class ExtraPageModel extends Model
{
    /** @var int */
    public int $subsite_id;

    /** @var string */
    public string $type;

    /** @var string */
    public string $text;

    /** @var string|null */
    public ?string $date_added = null;

    /** @var string|null */
    public ?string $date_modified = null;


    public static function getTableName(): string
    {
        return 'extra_pages';
    }
}
