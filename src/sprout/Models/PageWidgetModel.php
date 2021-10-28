<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class PageWidgetModel extends Model
{
    /** @var string */
    public $embed_key;

    /** @var int */
    public $page_revision_id;

    /** @var int */
    public $area_id;

    /** @var int */
    public $active;

    /** @var int */
    public $type;

    /** @var string */
    public $settings;

    /** @var string */
    public $conditions;

    /** @var string */
    public $heading;

    /** @var string */
    public $template;

    /** @var int */
    public $record_order;


    public static function getTableName(): string
    {
        return 'page_widgets';
    }
}
