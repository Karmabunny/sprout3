<?php

$config['account_id'] = getenv('AWS_ACCOUNT_ID') ?: '';
$config['region'] = getenv('AWS_REGION') ?: '';

// SHOULD NOT BE A ROOT USER - PLEASE USE IAM
$config['credentials']['key'] = getenv('AWS_KEY') ?: '';
$config['credentials']['secret'] = getenv('AWS_SECRET') ?: '';
