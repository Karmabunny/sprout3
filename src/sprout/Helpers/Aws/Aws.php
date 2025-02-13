<?php
namespace Sprout\Helpers\Aws;

use Aws\Credentials\CredentialProvider;
use Aws\Credentials\InstanceProfileProvider;
use Aws\Sdk;
use Kohana;

/**
 * Core tooling for using the AWS SDK
 */
class Aws
{

    private static $aws_sdk = null;


    public static function getSdk(): Sdk
    {
        if (!empty(self::$aws_sdk)) {
            return self::$aws_sdk;
        }

        // Init the AWS SDK with common configuration & auth
        if (SITES_ENVIRONMENT == 'dev') {
            $file = DOCROOT . 'config/aws-credentials.ini';
            $credential = CredentialProvider::ini('web-sdk-dev', $file);
        } else {
            $credential = self::getProdCredential();
        }

        self::$aws_sdk = new Sdk([
            'region' => Kohana::config('aws.region'),
            'version' => 'latest',
            'credentials' => $credential,
        ]);

        return self::$aws_sdk;
    }


    /**
     * Get production credentials from an instanceProfile
     *
     * This uses instance profile credentials so needs to be configured
     * properly on AWS
     *
     * @return InstanceProfileProvider
     */
    public static function getProdCredential(): InstanceProfileProvider
    {
        $timeout = 1.5;
        $retries = 5;

        if (PHP_SAPI === 'cli') {
            $timeout = 3;
            $retries = 10;
        }

        return CredentialProvider::instanceProfile([
            'timeout' => $timeout,
            'retries' => $retries,
        ]);
    }

}
