<?php
namespace Sprout\Helpers\Aws;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Exception;
use Kohana;

/**
 * A simple wrapper for loading S3 ready for use with the AWS SDK
 */
class S3
{

    /**
     * Load the AWS config from Kohana 'aws' config, with optional merge overrides
     *
     * @param array $config_merge
     *
     * @return array
     */
    public static function loadConfig(array $config_merge = [])
    {
        $config = Kohana::config('aws');
        $config = array_merge($config, $config_merge);

        if (empty($config['region'])) {
            throw new Exception('AWS region not configured - is the .env file updated with AWS config?');
        }

        return $config;
    }


    /**
     * Get an S3Client instance with local configuration
     *
     * @return S3Client
     */
    public static function getClient(array $config_merge = [])
    {
        $config = S3::loadConfig($config_merge);
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

}
