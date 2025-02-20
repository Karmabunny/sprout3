<?php
namespace Sprout\Helpers\Aws;

use Aws\CloudFront\CloudFrontClient;

/**
 * A simple wrapper for loading S3 ready for use with the AWS SDK
 */
class CloudFront extends Aws
{

    /**
     * Get a CloudFrontClient instance with local configuration
     *
     * @return CloudFrontClient
     */
    public static function getClient(array $config = []): CloudFrontClient
    {
        $sdk = self::getSdk();
        $config['version'] = $config['version'] ?? 'latest';
        $client = $sdk->createCloudFront($config);

        return $client;
    }

}
