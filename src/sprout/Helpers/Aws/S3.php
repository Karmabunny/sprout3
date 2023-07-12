<?php
namespace Sprout\Helpers\Aws;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Kohana;

/**
 * A simple wrapper for loading S3 ready for use with the AWS SDK
 */
class S3
{


    public static function loadConfig(string $config_scope = 'default')
    {
        $config = Kohana::config('aws');

        if ($config_scope != 'default') {
            $config_merge = Kohana::config("aws.{$config_scope}");
            $config = array_merge($config, $config_merge);
        }

        return $config;
    }


    /**
     * Get an S3Client instance with local configuration
     *
     * @return S3Client
     */
    public static function getClient(string $config_scope = 'default')
    {
        $config = S3::loadConfig($config_scope);

        $credentials = new Credentials($config['credentials']['key'], $config['credentials']['secret']);

        $s3 = new S3Client(
            [
                'region' => $config['region'],
                'version' => 'latest',
                'credentials' => $credentials,
            ]
        );

        return $s3;
    }


    /**
     * Get the original files backend bucket URL for the current config set
     */
    public static function filesBackendBucketUrl()
    {
        $config = S3::loadConfig('files_backend');
        return sprintf('https://%s.s3.%s.amazonaws.com/', $config['bucket'], $config['region']);
    }


    /**
     * Get the path for a file in the original file bucket
     *
     * @param string $filename
     *
     * @return string
     */
    public static function filesBackendUrl(string $filename)
    {
        $base = S3::filesBackendBucketUrl();
        return $base . $filename;
    }


    /**
     * Get the file resize bucket URL for the current config set
     */
    public static function filesResizeBucketUrl()
    {
        $config = S3::loadConfig('files_resized');
        return sprintf('https://%s.s3.%s.amazonaws.com/', $config['bucket'], $config['region']);
    }


    /**
     * Get the path for a file in the resized file bucket
     *
     * @param string $filename
     *
     * @return string
     */
    public static function fileResizeUrl(string $filename)
    {
        $base = S3::filesResizeBucketUrl();
        return $base . $filename;
    }


    /**
     * Get a custom folder path based on the region and bucket in config
     */
    public static function filesBackendFolderUrl(string $folder_path)
    {
        $config = S3::loadConfig('files_backend');

        return sprintf('https://%s.s3.%s.amazonaws.com/%s/', $config['bucket'], $config['region'], $folder_path);
    }

}