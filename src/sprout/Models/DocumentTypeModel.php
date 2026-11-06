<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class DocumentTypeModel extends Model
{
    /** @var int */
    public int $record_order;

    /** @var string */
    public string $name;

    /** @var string|null */
    public ?string $date_added = null;

    /** @var string|null */
    public ?string $date_modified = null;


    public static function getTableName(): string
    {
        return 'document_types';
    }
}
