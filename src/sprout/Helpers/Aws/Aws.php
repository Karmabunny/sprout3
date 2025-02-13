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

        $credentials = Kohana::config('aws.credentials', false, false);

        if (empty($credentials)) {
            $credentials = CredentialProvider::defaultProvider();
        }

        self::$aws_sdk = new Sdk([
            'region' => Kohana::config('aws.region'),
            'version' => 'latest',
            'credentials' => $credentials,
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
