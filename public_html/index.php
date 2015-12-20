<?php
$params = include('../config/params.php');

if (!defined('CW_ENV')) {
    define('CW_ENV', 'dev');
}

require_once __DIR__ . '/../vendor/autoload.php';

include '../CW.php';

\App::run(['params' => $params]);
