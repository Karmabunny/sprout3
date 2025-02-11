<?php
namespace Sprout\Helpers\Aws;

use Aws\Iam\IamClient;
use Kohana;

/**
 * A simple wrapper for loading Iam ready for use with the AWS SDK
 */
class Iam extends Aws
{

    /**
     * Get an S3Client instance with local configuration
     *
     * @return IamClient
     */
    public static function getClient(array $config = []): IamClient
    {
        $sdk = self::getSdk();
        $config['version'] = $config['version'] ?? '2010-05-08';
        $config['region'] = $config['region'] ?? Kohana::config('aws.region');

        $client = $sdk->createIam($config);

        return $client;
    }

}
