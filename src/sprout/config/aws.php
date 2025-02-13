<?php

use Sprout\Helpers\Aws\Aws;

$config['account_id'] = getenv('AWS_ACCOUNT_ID') ?: '';
$config['region'] = getenv('AWS_REGION') ?: '';

if (SITES_ENVIRONMENT == 'prod') {
    // Init the AWS SDK with common configuration & auth
    $config['credentials'] = Aws::getProdCredential();

} else if (
    ($key = getenv('AWS_KEY'))
    and ($secret = getenv('AWS_SECRET'))
) {
    // SHOULD NOT BE A ROOT USER - PLEASE USE IAM
    $config['credentials']['key'] = $key;
    $config['credentials']['secret'] = $secret;
    $config['credentials']['accountId'] = $config['account_id'] ?: null;

} else {
    // Use the default provider (i.e. all of them).
    // @see Aws\Credentials\CredentialProvider
    $config['credentials'] = null;
}
