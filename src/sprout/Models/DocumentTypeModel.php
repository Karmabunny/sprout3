<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class DocumentTypeModel extends Model
{
    /** @var int */
    public $record_order;


    public static function getTableName(): string
    {
        return 'document_types';
    }
}
