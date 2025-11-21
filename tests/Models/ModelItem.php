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
    public $uid;

    /** @var string */
    public $date_added;

    /** @var string */
    public $date_modified;

    /** @var string */
    public $name;

    /** @var string */
    public $status;


    public function rules(?string $scenario = null): array
    {
        return [
            ['required' => ['name', 'status']],
            ['uniqueValue' => ['name', 'uid']],
            ['length' => ['name', 'min' => 5]],
            ['inEnum' => ['status']],
        ];
    }
}
