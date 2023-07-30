<?php
namespace Sprout\Models;

use Kohana_Exception;
use Sprout\Helpers\File;
use Sprout\Helpers\Model;

class FileTransformModel extends Model
{

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

    /** @var int */
    public $filesize;

    /** @var string */
    public $imagesize;

    /** @var string|null */
    public $date_added;

    /** @var string|null */
    public $date_modified;

    /** @var string|null */
    public $date_file_modified;

    /** @var string */
    public $backend_type;

    /** @var string|null */
    public $backend_migrated;

    /** @var string|null */
    public $date_migrated;


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
        $backend = File::getBackendByType($this->backend_type, true);
        return $backend->absUrl($this->transform_filename);
    }


    /**
     * Delete a record, optionally delete the associated file
     *
     * @return bool
     */
    public function deleteFile()
    {
        $backend = File::getBackendByType($this->backend_type, true);
        return $backend->delete($this->transform_filename);
    }


    /**
     * Delete a record, optionally delete the associated file
     *
     * $param bool $remove_file
     *
     * @return bool
     */
    public function delete(bool $remove_file = true): bool
    {
        if ($remove_file) {
            $res = $this->deleteFile();
            if (!$res) return false;
        }

        return parent::delete();
    }


    /**
     * Get the name of the backend on which the transform is stored
     */
    public function getBackendName()
    {
        $backend = File::getBackendByType($this->backend_type, true);
        return $backend->getName();
    }
}
