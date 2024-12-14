<?php
namespace Sprout\Helpers\Aws;

use Aws\S3\S3Client;
use Kohana;

/**
 * A simple wrapper for loading S3 ready for use with the AWS SDK
 */
class S3 extends Aws
{

    /**
     * Get an S3Client instance with local configuration
     *
     * @return S3Client
     */
    public static function getClient(array $config = []): S3Client
    {
        $sdk = self::getSdk();
        $config['version'] = $config['version'] ?? '2006-03-01';
        $config['region'] = $config['region'] ?? Kohana::config('aws.region');

        $client = $sdk->createS3($config);

        return $client;
    }

}
