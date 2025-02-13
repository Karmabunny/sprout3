<?php

use Aws\Credentials\CredentialProvider;

$config['credentials'] = CredentialProvider::ini('web-sdk-dev', __DIR__ . '/aws-credentials.ini');
