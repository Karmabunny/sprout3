<?php
/*
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 *
 * Helpful examples here:
 * https://github.com/awsdocs/aws-doc-sdk-examples/blob/main/php/example_code/s3/
 */

namespace Sprout\Helpers;

use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Exception;

use Kohana;
use Kohana_Exception;
use Psr\Http\Message\StreamInterface;
use Sprout\Helpers\Aws\S3;
use Throwable;

/**
 * Backend for the files module which stores files in a local directory.
 *
 * S3 can host public files in four ways:
 *
 * 1. using a default ACL like 'public-read'
 * 2. using a bucket policy
 * 3. using signed requests
 * 4. using a cloudfront CDN (or other) proxy
 *
 * Consider your project requirement and configure the backend as appropriate.
 *
 * Note, all methods but proxies requires the 'public access override' in
 * the bucket config to be disabled.
 */
class FilesBackendS3 extends FilesBackend
{

    /**
     * This is merged with the backend 'settings' defined in the 'file' config.
     */
    const DEFAULT_SETTINGS = [
        // Folder prefix for transformed images
        'transform_folder_prefix' => 'transformed/',

        // Time to cache file helpers responses, such as ::exists()
        'default_cache_ttl' => 86400,

        // Chunk size for processing streams
        'stream_chunk_size' => 1024 * 1024,
    ];


    /**
     * This should match the key in Kohana::config("file.file_backends")
     */
    protected $backend_type = 's3';


    /**
     * @var S3Client|null
     */
    protected $client = null;


    /**
     * Generate server files base directory path
     *
     * @return string
     */
    public function baseDir(): string
    {
        return '';
    }


    /** @inheritdoc */
    public function getSettings(): array
    {
        $config = parent::getSettings();
        return $config + self::DEFAULT_SETTINGS;
    }


    /**
     * Config for the AWS client.
     *
     * @return array
     * @throws Kohana_Exception
     */
    public function getS3Config(): array
    {
        $config = $this->getConfig();
        return $config['client'] ?? [];
    }


    /**
     * The S3 client connection for this backend.
     *
     * @return S3Client
     * @throws Kohana_Exception
     */
    public function getS3Client(): S3Client
    {
        $config = $this->getS3Config();
        $this->client ??= S3::getClient($config);
        return $this->client;
    }


    /** @inheritdoc */
    public function relUrl($filename): string
    {
        return $this->absUrl($filename);
    }


    /** @inheritdoc */
    public function absUrl($filename, bool $lazy = true): string
    {
        Profiling::begin(__METHOD__, self::class, compact('filename', 'lazy'));

        try {
            $settings = $this->getSettings();
            $s3 = $this->getS3Client();

            // Using a proxy.
            if ($base_url = $settings['public_url_domain'] ?? false) {
                return rtrim($base_url, '/') . '/' . $filename;
            }

            // Build our own URL.
            if (!empty($settings['static_object_urls'])) {
                $region = $s3->getRegion();
                $bucket = $settings['bucket'];
                return "https://{$bucket}.s3.{$region}.amazonaws.com/{$filename}";
            }

            // Defer any backend requests for later. This makes for a fast template
            // while each file is resolved on separate requests.
            if (!empty($settings['lazy_object_urls']) and $lazy) {
                return Sprout::absRoot() . "file/resolve/{$filename}";
            }

            // Using signed urls.
            if ($validity = $settings['signed_urls'] ?? false) {
                $cmd = $s3->getCommand('GetObject', [
                    'Bucket' => $settings['bucket'],
                    'Key' => $filename
                ]);

                $request = $s3->createPresignedRequest($cmd, $validity);
                return (string) $request->getUri();
            }

            // Get a URL without presigning.
            return (string) $s3->getObjectUrl($settings['bucket'], $filename);

        } finally {
            Profiling::end(__METHOD__, self::class);
        }
    }


    /** @inheritdoc */
    public function exists(string $filename): bool
    {
        Profiling::begin(__METHOD__, self::class, compact('filename'));

        try {
            $result = $this->getHeadObject($filename);
            return $result !== null;

        } finally {
            Profiling::begin(__METHOD__, self::class);
        }
    }


    /**
     * Determine if a file exists from the public side (quickly)
     *
     * @param string $filename The file to check for
     *
     * @return bool Returns TRUE if the file exists, and FALSE if it does not
     */
    public function existsPublic(string $filename): bool
    {
        Profiling::begin(__METHOD__, self::class, compact('filename'));

        try {
            $cache_response = $this->getCacheResponse(__FUNCTION__, $filename);
            if ($cache_response !== null) {
                return $cache_response;
            }

            $url = $this->absUrl($filename);

            try {
                $headers = get_headers($url, true);
            } catch (Exception $e) {
                return false;
            }

            $status = substr($headers[0], 9, 3);

            $response = ($status >= 200 && $status < 300 ) ? true : false;
            $this->setCacheResponse(__FUNCTION__, $filename, $response);

            return $response;

        } finally {
            Profiling::end(__METHOD__, self::class);
        }
    }


    /** @inheritdoc */
    public function size(string $filename): int|false
    {
        Profiling::begin(__METHOD__, self::class, compact('filename'));

        try {
            $result = $this->getHeadObject($filename);

            if ($result === null) {
                return false;
            }

            return (int) $result['ContentLength'];

        } finally {
            Profiling::end(__METHOD__, self::class);
        }

        return false;
    }


    /** @inheritdoc */
    public function mtime(string $filename): int|false
    {
        Profiling::begin(__METHOD__, self::class, compact('filename'));

        try {
            $result = $this->getHeadObject($filename);

            if ($result === null) {
                return false;
            }

            return strtotime($result['LastModified']);

        } finally {
            Profiling::end(__METHOD__, self::class);
        }
    }


    /**
     * Sets access and modification time of file
     *
     * We achieve this in S3 by copying the file on top of itself
     *
     * @param string $filename
     *
     * @return bool
     */
    public function touch(string $filename): bool
    {
        return $this->copyExisting($filename, $filename);
    }


    /**
     * Copy an existing file (within S3) to a new file in S3
     *
     * @param string $src_filename
     * @param string $target_filename
     * @return bool
     */
    public function copyExisting(string $src_filename, string $target_filename): bool
    {
        Profiling::begin(__METHOD__, self::class, compact('src_filename', 'target_filename'));

        $config = $this->getSettings();
        $s3 = $this->getS3Client();

        try {
            $request = [
                'Bucket' => $config['bucket'],
                'Key' => $target_filename,
                'CopySource' => $config['bucket'] . '/' . $src_filename,
                // Overwrite if found
                'MetadataDirective' => 'REPLACE',
            ];

            if ($acl = $config['default_acl'] ?? false) {
                $request['ACL'] = $acl;
            }

            $result = $s3->copyObject($request);

            // TODO: Is this too granular?
            if ($result['@metadata']['statusCode'] == 200) {
                return true;
            }

        } catch (Exception $e) {
            $this->handleException($e);

        } finally {
            $this->clearCaches($target_filename);
            Profiling::end(__METHOD__, self::class);
        }

        return false;
    }


    /**
     * WARNING: SLOW ON S3 | Returns the size of an image, or false on failure.
     *
     * In S3 we can either store image size info as meta data
     *
     * Output format is the same as getimagesize, but will be at a minimum:
     *   [0] => width, [1] => height, [2] => type
     *
     * @param string $filename
     *
     * @return array|false depending if found
     */
    public function imageSize(string $filename): array|false
    {
        Profiling::begin(__METHOD__, self::class, compact('filename'));

        try {
            $stream = $this->getStream($filename);

            if (!$stream) {
                return false;
            }

            // Read the data incrementally until we get some decent metadata.
            // For PNG + WEBP this is in the first 30 bytes. JPEG metadata
            // comes in a variety of formats, but typically we find it in the
            // first 1000 bytes. Worst case we read the whole file (shrug).

            $data = '';
            $chunk = 1024;

            while (!$stream->eof()) {
                $data .= $stream->read($chunk);
                $size = @getimagesizefromstring($data);

                if ($size !== false) {
                    return $size;
                }

                // Incrementally grab more, but not more than 1mb at a time.
                $chunk = min(1024 * 1024, $chunk * 2);
            }

            return false;

        } catch (Exception $e) {
            $this->handleException($e);

        } finally {
            if (isset($stream)) {
                $stream->close();
            }

            Profiling::end(__METHOD__, self::class);
        }

        return false;
    }


    /** @inheritdoc */
    public function delete(string $filename): bool
    {
        Profiling::begin(__METHOD__, self::class, compact('filename'));

        $config = $this->getSettings();
        $s3 = $this->getS3Client();

        try {
            $result = $s3->deleteObject([
                'Bucket' => $config['bucket'],
                'Key' => $filename,
            ]);

            if (@$result['@metadata']['statusCode'] == 204) {
                return true;
            }

            // No file, so all good
            if (@$result['@metadata']['statusCode'] == 404) {
                return true;
            }

        } catch (Exception $e) {
            $this->handleException($e);

        } finally {
            $this->clearCaches($filename);
            Profiling::end(__METHOD__, self::class);
        }

        return false;
    }


    /**
    * Delete a directory
    **/
    function deleteDir($directory)
    {
        Profiling::begin(__METHOD__, self::class, compact('directory'));

        try {
            $config = $this->getSettings();
            $s3 = $this->getS3Client();

            $items = $s3->listObjects([
                'Bucket' => $config['bucket'],
                'Prefix' => $directory,
            ]);

            foreach ($items['Contents'] ?? [] as $item) {
                $this->delete($item['Key']);
            }
        }
        finally {
            Profiling::end(__METHOD__, self::class);
        }

        return true;
    }


    /**
    * Create an empty directory
    **/
    function mkDir($directory)
    {
        // s3 doesn't have directories, but we'll create an empty key
        return $this->putString($directory . '/', '');
    }


    /** @inheritdoc */
    public function glob(string $mask, $depth = 0): array
    {
        Profiling::begin(__METHOD__, self::class, compact('mask', 'depth'));

        try {
            $config = $this->getSettings();
            $s3 = $this->getS3Client();

            $items = $s3->listObjects([
                'Bucket' => $config['bucket'],
            ]);

            $results = [];

            // Function not available on some systems..
            if (!function_exists('fnmatch')) return $results;

            foreach ($items['Contents'] ?? [] as $item) {
                if (fnmatch($mask, $item['Key'])) {
                    $results[] = $item['Key'];
                }
            }

            return $results;

        } finally {
            Profiling::end(__METHOD__, self::class);
        }
    }


    /** @inheritdoc */
    public function readfile(string $filename)
    {
        Profiling::begin(__METHOD__, self::class, compact('filename'));

        $config = $this->getSettings();
        $s3 = $this->getS3Client();
        $s3->registerStreamWrapperV2();

        try {
            $stream = fopen("s3://{$config['bucket']}/{$filename}", 'r');

            if ($stream === false) {
                return false;
            }

            $output = fopen('php://output', 'w');
            $size = 0;

            do {
                $length = stream_copy_to_stream($stream, $output, $config['stream_chunk_size']);

                if ($length === false) {
                    return false;
                }

                $size += $length;

            } while ($length > 0);

            return $size;

        } catch (Exception $e) {
            $this->handleException($e);

        } finally {
            if (is_resource($stream ?? false)) {
                @fclose($stream);
            }

            stream_wrapper_unregister('s3');
            Profiling::end(__METHOD__, self::class);
        }

        return false;
    }


    /** @inheritdoc */
    public function getString(string $filename)
    {
        Profiling::begin(__METHOD__, self::class, compact('filename'));

        $config = $this->getSettings();
        $s3 = $this->getS3Client();

        try {
            $result = $s3->getObject([
                'Bucket' => $config['bucket'],
                'Key' => $filename,
            ]);

            if (@$result['@metadata']['statusCode'] == 200) {
                return $result['Body'];
            }

        } catch (Exception $e) {
            $this->handleException($e);

        } finally {
            Profiling::end(__METHOD__, self::class);
        }

        return false;
    }


    /** @inheritdoc */
    public function putString(string $filename, string $content): bool
    {
        Profiling::begin(__METHOD__, self::class, ['filename' => $filename, 'size' => strlen($content)]);

        $config = $this->getSettings();
        $s3 = $this->getS3Client();

        // This may well throw an Aws\S3\Exception\S3Exception, in this scenario we want to be elegant about it
        try {
            $request = [
                'Bucket' => $config['bucket'],
                'Key' => $filename,
                'Body' => $content,
                'ContentType' => File::mimetype($filename),
                // Overwrite if found
                'MetadataDirective' => 'REPLACE',
            ];

            if ($acl = $config['default_acl'] ?? false) {
                $request['ACL'] = $acl;
            }

            $result = $s3->putObject($request);

            if (@$result['@metadata']['statusCode'] == 200) {
                $res = Replication::postFileUpdate($filename);
                if (! $res) {
                    // TODO: Something with the S3 file?
                    return false;
                }

                return true;
            }

        } catch (Exception $e) {
            $this->handleException($e);

        } finally {
            $this->clearCaches($filename);
            Profiling::end(__METHOD__, self::class);
        }

        return false;
    }


    /** @inheritdoc */
    public function putStream(string $filename, $stream): bool
    {
        Profiling::begin(__METHOD__, self::class, compact('filename'));

        if ($stream instanceof StreamInterface) {
            $stream = $stream->detach();
        }

        if ($stream === null) {
            return false;
        }

        $this->clearCaches($filename);

        $config = $this->getSettings();
        $s3 = $this->getS3Client();
        $s3->registerStreamWrapperV2();

        try {
            $file = fopen("s3://{$config['bucket']}/{$filename}", 'w');

            if ($file === false) {
                return false;
            }

            do {
                $length = stream_copy_to_stream($stream, $file, $config['stream_chunk_size']);

                if ($length === false) {
                    return false;
                }
            } while ($length > 0);

            return true;

        } catch (Exception $e) {
            $this->handleException($e);

        } finally {
            if (is_resource($file ?? false)) {
                @fclose($file);
            }

            stream_wrapper_unregister('s3');
            Profiling::end(__METHOD__, self::class);
        }

        return false;
    }


    /** @inheritdoc */
    public function getStream(string $filename): ?StreamInterface
    {
        $settings = $this->getSettings();
        $s3 = $this->getS3Client();

        try {
            $command = $s3->getCommand('GetObject', [
                'Bucket' => $settings['bucket'],
                'Key' => $filename,
            ]);

            $command['@http']['stream'] = true;
            $result = $s3->execute($command);

            if ($result['Body'] instanceof StreamInterface) {
                return $result['Body'];
            }

            throw new Exception('Expected StreamInterface, got: ' . get_debug_type($result['Body']));

        } catch (Exception $e) {
            $this->handleException($e);
        }

        return null;
    }


    /** @inheritdoc */
    public function putExisting(string $filename, string $existing): bool
    {
        $stream = fopen($existing, 'r');

        if (!$stream) {
            return false;
        }

        try {
            $ok = $this->putStream($filename, $stream);
            return $ok;

        } finally {
            @fclose($stream);
        }
    }


    /** @inheritdoc */
    public function createLocalCopy(string $filename)
    {
        $temp_filename = STORAGE_PATH . 'temp/' . time() . '_' . str_replace('/', '~', $filename);

        Profiling::begin(__METHOD__, self::class, compact('filename'));

        try {
            $stream = $this->getStream($filename);
            $stream = $stream ? $stream->detach() : null;

            if (!$stream) {
                return null;
            }

            $file = fopen($temp_filename, 'w');

            if (!$file) {
                return null;
            }
        } catch (Throwable $e) {
            Profiling::end(__METHOD__, self::class);
            throw $e;
        }

        $settings = $this->getSettings();

        try {
            do {
                $length = stream_copy_to_stream($stream, $file, $settings['stream_chunk_size']);

                if ($length === false) {
                    return false;
                }
            } while ($length > 0);

            chmod($temp_filename, 0777);
            return $temp_filename;

        } catch (Exception $e) {
            $this->handleException($e);

        } finally {
            @fclose($stream);
            @fclose($file);
            Profiling::end(__METHOD__, self::class);
        }

        return null;
    }


    /** @inheritdoc */
    public function moveFile(string $src, string $dest): bool
    {
        $config = $this->getSettings();
        $s3 = $this->getS3Client();

        Profiling::begin(__METHOD__, self::class, compact('src', 'dest'));

        try {
            $request = [
                'Bucket' => $config['bucket'],
                'Key' => $dest,
                'CopySource' => $config['bucket'] . '/' . $src,
                // Overwrite if found
                'MetadataDirective' => 'REPLACE',
            ];

            if ($acl = $config['default_acl'] ?? false) {
                $request['ACL'] = $acl;
            }

            $result = $s3->copyObject($request);

            // TODO: Is this too granular?
            if ($result['@metadata']['statusCode'] != 200) {
                return false;
            }

            $result = $s3->deleteObject([
                'Bucket' => $config['bucket'],
                'Key' => $src,
            ]);

            if (@$result['@metadata']['statusCode'] != 204) {
                return false;
            }

            return true;

        } catch (Exception $e) {
            $this->handleException($e);

        } finally {
            $this->clearCaches($src);
            $this->clearCaches($dest);
            Profiling::end(__METHOD__, self::class);
        }

        return false;
    }


    /** @inheritdoc */
    public function moveUpload(string $src, string $filename): bool
    {
        if (is_link($src)) {
            // Move file symlink points to, rather than symlink itself
            $src = readlink($src);
        }

        // Upload the src file into S3
        $config = $this->getSettings();
        $s3 = $this->getS3Client();

        Profiling::begin(__METHOD__, self::class, compact('src', 'filename'));

        try {
            $request = [
                'Bucket' => $config['bucket'],
                'Key' => $filename,
                'SourceFile' => $src,
                'ContentType' => File::mimetype($filename),
                // Overwrite if found
                'MetadataDirective' => 'REPLACE',
            ];

            if ($acl = $config['default_acl'] ?? false) {
                $request['ACL'] = $acl;
            }

            $result = $s3->putObject($request);

            if (@$result['@metadata']['statusCode'] == 200) {
                $res = Replication::postFileUpdate($filename);
                if (! $res) {
                    // TODO: Something with the S3 file?
                    return false;
                }

                unlink($src);
                return true;
            }

        } catch (Exception $e) {
            $this->handleException($e);

        } finally {
            $this->clearCaches($filename);
            Profiling::end(__METHOD__, self::class);
        }

        return false;
    }


    /**
     * Get the head object for a file.
     *
     * This includes checks for delete markers.
     *
     * @param string $filename
     * @return array|null null if not found or deleted
     */
    public function getHeadObject(string $filename): ?array
    {
        $cache_response = $this->getCacheResponse('HeadObject', $filename);

        if ($cache_response !== null) {
            return $cache_response ?: null;
        }

        try {
            $config = $this->getSettings();
            $s3 = $this->getS3Client();

            $command = $s3->getCommand('HeadObject', [
                'Bucket' => $config['bucket'],
                'Key' => $filename,
            ]);

            $result = $s3->execute($command);
            $result = $result->toArray();

        } catch (Exception $e) {
            $result = $this->handleException($e, true);
        }

        $this->setCacheResponse(__FUNCTION__, $filename, $result, 60);
        return $result ?: null;
    }


    /**
     * Make a file public using ACL
     *
     * Will not work if 'block all public access' is enabled on the bucket
     *
     * @param string $filename
     * @return bool
     */
    public function makePublic(string $filename)
    {
        $config = $this->getSettings();
        $s3 = $this->getS3Client();

        Profiling::begin(__METHOD__, self::class, compact('filename'));

        try {
            $result = $s3->putObjectAcl([
                'Bucket' => $config['bucket'],
                'Key' => $filename,
                'ACL' => 'public-read',
            ]);

            if (@$result['@metadata']['statusCode'] == 200) {
                return true;
            }

        } catch (Exception $e) {
            $this->handleException($e);

        } finally {
            $this->clearCaches($filename);
            Profiling::end(__METHOD__, self::class);
        }

        return false;
    }


    /**
     * Make a file private using ACL
     *
     * @param string $filename
     * @return bool
     */
    public function makePrivate(string $filename)
    {
        $config = $this->getSettings();
        $s3 = $this->getS3Client();

        Profiling::begin(__METHOD__, self::class, compact('filename'));

        try {
            $result = $s3->putObjectAcl([
                'Bucket' => $config['bucket'],
                'Key' => $filename,
                'ACL' => 'private',
            ]);

            if (@$result['@metadata']['statusCode'] == 200) {
                return true;
            }

        } catch (Exception $e) {
            $this->handleException($e);

        } finally {
            $this->clearCaches($filename);
            Profiling::end(__METHOD__, self::class);
        }

        return false;
    }


    /**
     * Simple exception handling with logging
     *
     * @param Exception $e
     * @param bool $permit_not_found
     * @throws Exception
     * @return false|null false if not found, null otherwise
     */
    private function handleException(Exception $e, $permit_not_found = false)
    {
        if ($e instanceof S3Exception) {
            if ($permit_not_found) {
                if ($e->getStatusCode() == 404) {
                    return false;
                }

                // Support for versioned objects.
                if (
                    ($response = $e->getResponse())
                    and $response->getHeader('x-amz-delete-marker')
                ) {
                    return false;
                }
            }

            $this->log($e);
            // Fall through to failure

        } elseif ($e instanceof AwsException) {
            $this->log($e);
            // Fall through to failure

        } else {
            // Always raise for unknown exceptions.
            throw $e;
        }

        // If we're testing, blow up in any case
        if (defined('PHPUNIT') and PHPUNIT) {
            throw $e;
        }

        return null;
    }

}
