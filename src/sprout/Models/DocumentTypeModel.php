<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class DocumentTypeModel extends Model
{
    /** @var int */
    public $record_order;

    /** @var string */
    public $name;

    /** @var string|null */
    public $date_added;

    /** @var string|null */
    public $date_modified;


    public static function getTableName(): string
    {
        return 'document_types';
    }
}
