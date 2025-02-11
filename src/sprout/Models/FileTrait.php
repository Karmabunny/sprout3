<?php
namespace Sprout\Models;

use Sprout\Helpers\File;
use Sprout\Helpers\FilesBackend;



/**
 * Common fields and tooling for file caching and backend info
 *
 * This includes ensuring support for file backend migration
 */
trait FileTrait
{

    /** @var int */
    public $filesize;

    /** @var string */
    public $imagesize;

    /** @var string|null */
    public $date_file_modified;

    /** @var string */
    public $backend_type;

    /** @var string|null */
    public $filename_migrated;

    /** @var string|null */
    public $backend_migrated;

    /** @var string|null */
    public $date_migrated;


    abstract static function getTableName(): string;


    /**
     * Get an abs url for this transform file
     *
     * @return string
     */
    abstract public function getUrl();


    /**
     * Delete a file (not its db record)
     *
     * @return bool
     */
    abstract public function deleteFile();


    /**
     * Delete a record, optionally delete the associated file
     *
     * @param bool $remove_file Flag to delete the file with the record
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
     *
     * @return FilesBackend
     */
    public function getBackend()
    {
        $backend = File::getBackendByType($this->backend_type, true);
        return $backend;
    }


    /**
     * Get the name of the backend on which the transform is stored
     *
     * @return string
     */
    public function getBackendName()
    {
        $backend = File::getBackendByType($this->backend_type, true);
        return $backend->getName();
    }
}
