<?php
namespace Sprout\Models;

use InvalidArgumentException;
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
    public int $filesize;

    /** @var string */
    public string $imagesize;

    /** @var string|null */
    public ?string $date_file_modified;

    /** @var string */
    public string $backend_type;

    /** @var string|null */
    public ?string $filename_migrated;

    /** @var string|null */
    public ?string $backend_migrated;

    /** @var string|null */
    public ?string $date_migrated;


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
     * @throws InvalidArgumentException
     */
    public function getBackend()
    {
        $backend = File::getBackendByType($this->backend_type);
        return $backend;
    }


    /**
     * Get the name of the backend on which the transform is stored
     *
     * @return string
     */
    public function getBackendName()
    {
        try {
            $backend = $this->getBackend();
            return $backend->getName();
        } catch (InvalidArgumentException $exception) {
            return "Unknown ({$this->backend_type})";
        }
    }
}
