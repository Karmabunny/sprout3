<?php
namespace Sprout\Helpers;

use Sprout\Exceptions\FileUploadException;
use Sprout\Exceptions\ValidationException;
use Sprout\Helpers\Constants;
use Sprout\Helpers\File;
use Sprout\Helpers\FileUpload;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\SubsiteSelector;


class Files
{
    /**
     * Save file to media repo from local source
     *
     * @param string $src Full filepath
     * @param string $name Record name
     * @param string $filename Destination filename. Will be prepended with file's ID
     * @return int File ID
     */
    public static function saveFromFile($src, $filename = null, $name = null)
    {
        if (empty($filename)) $filename = basename($src);
        $ext = strtolower(File::getExt($filename));

        if (!FileUpload::checkFilename($filename)) throw new FileUploadException('Invalid file type provided');
        if (!in_array($ext, array_keys(Constants::$mimetypes))) throw new FileUploadException('Invalid file extension');
        if (File::checkFileContentsExtension($src, $ext) === false) throw new FileUploadException("File content doesn't match extension");

        $data = [
            'subsite_id' => SubsiteSelector::$subsite_id,
            'type' => File::getType($filename),
            'name' => $name ? $name : $filename,
            'filename' => $filename,
            'sha1' => hash_file('sha1', $src, false),
            'author' => '',
            'embed_author' => 0,
            'description' => '',
            'date_added' => Pdb::now(),
            'date_modified' => Pdb::now(),
            'date_file_modified' => Pdb::now(),
        ];

        $file_id = Pdb::insert('files', $data);
        $filename = sprintf('%u_%s', $file_id, $filename);

        // Add file to repo folder
        $ok = File::moveUpload($src, $filename);
        if (!$ok) throw new FileUploadException('Failed to move uploaded file');

        // This updates the filename, create cached sizes if image
        File::postUploadProcessing($filename, $file_id, File::getType($filename));

        return $file_id;
    }


    /**
     * Save file into media repo from upload
     *
     * @param string$key A $_FILES key
     * @param bool $required default false
     * @param string $filename Optional uploaded filename. Will be prepended with file's ID
     * @param string $name Optional record name
     * @return int File ID
     */
    public static function saveFromUpload($key, $required = false, $filename = null, $name = null)
    {
        $file = FileUpload::getFile($key);

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
        if (!empty($allowed_exts) and !in_array($ext, $allowed_exts)) {
            throw new FileUploadException('Invalid file extension');
        }

        if (File::checkFileContentsExtension($file['tmp_name'], $ext) === false) {
            throw new FileUploadException("File content doesn't match extension");
        }

        $data = [
            'subsite_id' => SubsiteSelector::$subsite_id,
            'type' => File::getType($file['name']),
            'name' => $name ? $name : $file['name'],
            'filename' => $filename ? $filename : $file['name'],
            'sha1' => hash_file('sha1', $file['tmp_name'], false),
            'author' => '',
            'embed_author' => 0,
            'description' => '',
            'date_added' => Pdb::now(),
            'date_modified' => Pdb::now(),
            'date_file_modified' => Pdb::now(),
        ];

        $file_id = Pdb::insert('files', $data);
        $filename = sprintf('%u_%s', $file_id, $data['filename']);

        // Add file to repo folder
        $ok = File::moveUpload($file['tmp_name'], $filename);
        if (!$ok) throw new FileUploadException('Failed to move uploaded file');

        // This updates the filename, create cached sizes if image
        File::postUploadProcessing($filename, $file_id, File::getType($file['name']));

        return $file_id;
    }
}
