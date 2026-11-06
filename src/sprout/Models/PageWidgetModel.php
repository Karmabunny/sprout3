<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class PageWidgetModel extends Model
{
    /** @var string */
    public string $embed_key;

    /** @var int */
    public int $page_revision_id;

    /** @var int */
    public int $area_id;

    /** @var bool */
    public bool $active = true;

    /** @var string */
    public string $type;

    /** @var string */
    public string $settings;

    /** @var string */
    public string $conditions;

    /** @var string */
    public string $heading;

    /** @var string */
    public string $template;

    /** @var int */
    public int $record_order = 0;


    public static function getTableName(): string
    {
        return 'page_widgets';
    }
}
