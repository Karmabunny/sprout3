<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class FileModel extends Model
{
    /** @var int */
    public $subsite_id;

    /** @var string */
    public $filename;

    /** @var int */
    public $type;

    /** @var string */
    public $author;

    /** @var int */
    public $embed_author;

    /** @var string */
    public $description;

    /** @var string */
    public $focal_points;

    /** @var string|null */
    public $plaintext;

    /** @var int|null */
    public $document_type;

    /** @var string|null */
    public $date_published;

    /** @var string */
    public $enable_indexing;

    /** @var string|null */
    public $date_file_modified;

    /** @var string */
    public $sha1;


    public static function getTableName(): string
    {
        return 'files';
    }
}
