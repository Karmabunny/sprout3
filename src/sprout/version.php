<?php

$hash = '';

if (file_exists(APPPATH . 'git_hash.txt')) {
	ob_start();
	require_once APPPATH . 'git_hash.txt';
	$hash = ob_get_contents();
	ob_end_clean();
}

$config['version_brand'] = 3.1;
$config['version'] = sprintf('%s - #%s', $config['version_brand'], $hash);
