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

    use FileTrait;


    /** @var int */
    public $id;

    /** @var int */
    public $subsite_id = 0;

    /** @var string */
    public $name = '';

    /** @var string */
    public $filename = '';

    /** @var int */
    public $type = 0;

    /** @var string */
    public $author = '';

    /** @var int */
    public $embed_author = 0;

    /** @var string */
    public $description = '';

    /** @var string */
    public $focal_points = '';

    /** @var string|null */
    public $plaintext = '';

    /** @var int|null */
    public $document_type = 0;

    /** @var string|null */
    public $date_added = '';

    /** @var string|null */
    public $date_modified = '';

    /** @var string|null */
    public $date_published = '';

    /** @var int */
    public $enable_indexing = 1;

    /** @var string */
    public $sha1 = '';


    public static function getTableName(): string
    {
        return 'files';
    }


    /**
     * Get an abs url for this file
     *
     * @return string
     */
    public function getUrl()
    {
        $backend = File::getBackendByType($this->backend_type, true);
        return $backend->absUrl($this->filename);
    }


    /**
     * Delete a record, optionally delete the associated file
     *
     * @return bool
     */
    public function deleteFile()
    {
        $backend = File::getBackendByType($this->backend_type, true);
        return $backend->delete($this->filename);
    }


    /**
     * Create a file from an upload.
     *
     * This will perform validations and move the file to the correct location.
     *
     * @param string $key a $_FILES key
     * @param bool $required default false
     * @param array $config any optional fields
     * @return self|null the model, or null if the file doesn't exist (in non-required mode)
     * @throws FileUploadException
     * @throws ValidationException
     */
    public static function fromUpload(string $key, bool $required = false, array $config = [])
    {
        $file = FileUpload::getFile($key);

        // Some validations.

        if (!$file or $file['error'] == UPLOAD_ERR_NO_FILE) {
            if ($required) {
                throw new ValidationException('No file was uploaded.');
            }

            return null;
        }

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

        // TODO nested or shared transactions support in kbpdb.
        $transact = !$pdb->inTransaction();

        if ($transact) {
            $pdb->transact();
        }

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

            if ($transact) {
                $pdb->commit();
            }
            return $model;
        }
        // Clean up any spilt milk.
        finally {
            if ($transact and $pdb->inTransaction()) {
                $pdb->rollback();
            }
        }
    }


    /**
     * Create a file record from given path
     * This will perform validations and move the file to the correct location
     *
     * @param array $data
     * @param string $from_path Current path of the file
     * @return self the model
     * @throws FileUploadException
     * @throws ValidationException
     */
    public static function fromPath(array $data, string $from_path = '')
    {
        if (!FileUpload::checkFilename($from_path)) {
            throw new FileUploadException('Invalid file type provided');
        }

        // TODO is from_path required or not??
        // This method is fraught with bugs otherwise.

        // Prefer a configured name, but fallback to the filename.
        $data['name'] = $data['name'] ?? File::filenameMakeSane(basename($from_path));

        // Create the initial model.
        $model = new FileModel($data);

        $model->subsite_id = SubsiteSelector::$subsite_id;
        $model->date_file_modified = Pdb::now();

        // Core bits
        $model->filename = File::filenameMakeSane(basename($from_path));
        $model->type = File::getType(basename($from_path));
        $model->sha1 = hash_file('sha1', $from_path, false);

        // A transaction because we double-save the record
        $pdb = $model->getConnection();

        // TODO nested or shared transactions support in kbpdb
        $transact = !$pdb->inTransaction();

        if ($transact) $pdb->transact();

        try
        {
            // Initial save to generate an ID for the filename
            $model->save(false);
            $model->filename = $model->id . '_' . File::filenameMakeSane(File::filenameMakeSane(basename($from_path)));

            // Move the temp file to the final location.
            $ok = File::moveUpload($from_path, $model->filename);
            if (!$ok) throw new FileUploadException('Failed to move uploaded file');

            // This updates the filename, does other funny bits.
            File::postUploadProcessing($model->filename, $model->id, $model->type);

            $model->validate();

            if ($transact) $pdb->commit();
            return $model;
        }
        // Clean up any spilt milk
        finally
        {
            if ($transact and $pdb->inTransaction()) $pdb->rollback();
        }
    }


    /**
     * Renames physical file
     *
     * @param string $filename
     * @return bool True on success
     */
    public function moveFile(string $filename): bool
    {
        $src = File::baseDir() . $this->filename;
        $this->filename = $filename;

        return File::moveUpload($src, $this->filename);
    }
}
