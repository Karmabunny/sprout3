<?php

/**
 * Permit these origins.
 *
 * True means all origins are permitted.
 */
$config['origins'] = true;

/**
 * Permit these request methods.
 */
$config['methods'] = [
    'get',
];

/**
 * Permit these request headers.
 */
$config['headers'] = [
    'accept',
    'accept-language',
    'content-type',
    'x-requested-with',
    'from',
    'dnt',
    'via',
];

/**
 * Set the 'Access-Control-Allow-Credentials' response header.
 *
 * This also permits the request headers: authorization + cookie.
 */
$config['allow_credentials'] = false;

/**
 * Ignore all request header validations.
 */
$config['ignore_headers'] = false;

/**
 * Set the 'Access-Control-Expose-Headers' response header.
 *
 * - true: expose all headers
 * - false: no headers are exposed
 * - array: expose only the headers in the array
 */
$config['expose_headers'] = false;
