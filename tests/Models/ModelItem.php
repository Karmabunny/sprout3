<?php

use Sprout\Helpers\Model;

/**
 * A sprout model for testing 'internalSave' overrides.
 */
class ModelItem extends Model
{

    /** @inheritdoc */
    public static function getTableName(): string
    {
        return 'test';
    }


    /** @var string */
    public $name;

    /** @var string */
    public $uid;

    /** @var string */
    public $date_added;

    /** @var string */
    public $date_modified;
}
