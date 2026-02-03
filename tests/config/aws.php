<?php

use Aws\Credentials\CredentialProvider;

if (getenv('GITHUB_ACTIONS')) {
    $config['credentials'] = CredentialProvider::env();
} else {
    $config['credentials'] = CredentialProvider::ini('web-sdk-dev', __DIR__ . '/aws-credentials.ini');
}
