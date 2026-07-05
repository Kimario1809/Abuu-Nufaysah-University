<?php
// Load environment variables
require_once __DIR__ . '/app/core/Env.php';
App\Core\Env::load();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$publicPath = __DIR__ . '/public' . $uri;

if ($uri !== '/' && is_file($publicPath)) {
    return false;
}

$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';

require __DIR__ . '/public/index.php';
