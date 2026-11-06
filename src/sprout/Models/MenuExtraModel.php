<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class MenuExtraModel extends Model
{
    /** @var int */
    public int $subsite_id;

    /** @var int */
    public int $page_id;

    /** @var string */
    public string $text;

    /** @var int|null */
    public ?int $image;



    public static function getTableName(): string
    {
        return 'menu_extras';
    }
}
