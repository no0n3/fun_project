<?php
$params = array_merge(
    include( __DIR__ . '/../config/params.php'),
    include( __DIR__ . '/../config/local-params.php')
);

if (!defined('CW_ENV')) {
    define('CW_ENV', 'dev');
}

require_once __DIR__ . '/../vendor/autoload.php';

include __DIR__ . '/../CW.php';

\App::run(
    ['params' => $params],
    false, // not console application
    isset($route) ? $route : $_SERVER['PATH_INFO']
);
