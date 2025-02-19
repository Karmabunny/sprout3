<?php
namespace Sprout\Models;

use Sprout\Helpers\File;
use Sprout\Helpers\Model;

class FileTransformModel extends Model
{

    use FileTrait;


    /** @var int */
    public $id;

    /** @var int|null */
    public $file_id;

    /** @var string */
    public $filename;

    /** @var string */
    public $transform_name;

    /** @var string */
    public $transform_filename;

    /** @var string|null */
    public $date_added;

    /** @var string|null */
    public $date_modified;


    public static function getTableName(): string
    {
        return 'file_transforms';
    }


    /**
     * Get an abs url for this transform file
     *
     * @return string
     */
    public function getUrl()
    {
        $backend = File::getBackendByType($this->backend_type);
        return $backend->absUrl($this->transform_filename);
    }


    /**
     * Delete a record, optionally delete the associated file
     *
     * @return bool
     */
    public function deleteFile()
    {
        $backend = File::getBackendByType($this->backend_type);
        return $backend->delete($this->transform_filename);
    }

}
