<?php

/**
 * Router for PHP's built-in server so static files are served as-is.
 * Usage: php -S 127.0.0.1:8090 -t public public/router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$file = __DIR__ . $uri;

if ($uri !== '/' && is_file($file)) {
    return false;
}

require_once __DIR__ . '/index.php';
