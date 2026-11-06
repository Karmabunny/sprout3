<?php
namespace Sprout\Models;

use Sprout\Helpers\File;
use Sprout\Helpers\Model;

class FileTransformModel extends Model
{

    use FileTrait;

    /** @var int|null */
    public ?int $file_id;

    /** @var string */
    public string $filename;

    /** @var string */
    public string $transform_name;

    /** @var string */
    public string $transform_filename;

    /** @var string|null */
    public ?string $date_added = null;

    /** @var string|null */
    public ?string $date_modified = null;


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
