<?php

class CW {
    public static $app;

    public static function autoload() {
        spl_autoload_register(function($className) {
            global $params;

            $className = str_replace('\\', '/', $className);
            $file = __DIR__ . "/$className.php";

            if (is_file($file)) {
                include __DIR__ . "/$className.php";
            }
        });
    }
}

CW::autoload();
