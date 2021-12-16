<?php
namespace Sprout\Models;

use karmabunny\kb\ValidationException;
use Sprout\Exceptions\FileUploadException;
use Sprout\Helpers\File;
use Sprout\Helpers\FileUpload;
use Sprout\Helpers\Model;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\SubsiteSelector;

class FileModel extends Model
{
    /** @var int */
    public $subsite_id;

    /** @var string */
    public $name;

    /** @var string */
    public $filename;

    /** @var int */
    public $type;

    /** @var string */
    public $author = '';

    /** @var int */
    public $embed_author = 0;

    /** @var string */
    public $description;

    /** @var string */
    public $focal_points = '';

    /** @var string|null */
    public $plaintext;

    /** @var int|null */
    public $document_type;

    /** @var string|null */
    public $date_added;

    /** @var string|null */
    public $date_modified;

    /** @var string|null */
    public $date_published;

    /** @var string */
    public $enable_indexing = 1;

    /** @var string|null */
    public $date_file_modified;

    /** @var string */
    public $sha1;


    public static function getTableName(): string
    {
        return 'files';
    }


    /**
     * Create a file from an upload.
     *
     * This will perform validations and move the file to the correct location.
     *
     * @param string $key a $_FILES key
     * @param array $config any optional fields
     * @return static|null the model, or null if the file key doesn't exist
     * @throws FileUploadException
     * @throws ValidationException
     */
    public static function fromUpload(string $key, array $config = [])
    {
        $file = $_FILES[$key] ?? null;
        if (!$file) return null;

        // Some validations.

        if ($file['error'] != UPLOAD_ERR_OK) {
            throw new FileUploadException(FileUpload::getErrorMessage($file['error']));
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new FileUploadException('Invalid file type provided');
        }

        if (!FileUpload::checkFilename($file['name'])) {
            throw new FileUploadException('Invalid file type provided');
        }

        $ext = strtolower(File::getExt($file['name']));
        if (!empty($allowed_exts) and !in_array($ext, $allowed_exts)) {
            throw new FileUploadException('Invalid file extension');
        }

        if (File::checkFileContentsExtension($file['tmp_name'], $ext) === false) {
            throw new FileUploadException("File content doesn't match extension");
        }

        // Prefer a configured name, but fallback to the filename.
        $config['name'] = $config['name'] ?? $file['name'];

        // Create the initial model.
        $model = new FileModel($config);

        $model->subsite_id = SubsiteSelector::$subsite_id;
        $model->date_file_modified = Pdb::now();

        // Core bits.
        $model->filename = $file['name'];
        $model->type = File::getType($file['name']);
        $model->sha1 = hash_file('sha1', $file['tmp_name'], false);

        // A transaction because we double-save the record.
        $pdb = $model->getConnection();
        $pdb->transact();

        try {
            // Initial save to generate an ID for the filename.
            $model->save(false);
            $model->filename = $model->id . '_' . File::filenameMakeSane($file['name']);

            // Move the temp file to the final location.
            $ok = File::moveUpload($file['tmp_name'], $model->filename);
            if (!$ok) throw new FileUploadException('Failed to move uploaded file');

            // This updates the filename, does other funny bits.
            File::postUploadProcessing($model->filename, $model->id, $model->type);

            $model->validate();
            $pdb->commit();
            return $model;
        }
        // Clean up any spilt milk.
        finally {
            if ($pdb->inTransaction()) {
                $pdb->rollback();
            }
        }
    }
}
