<?php
include ('CW.php');

$paramsGl = array_merge(
    include( __DIR__ . '/config/params.php'),
    include( __DIR__ . '/config/local-params.php')
);

if (!defined('CW_ENV')) {
    define('CW_ENV', 'dev');
}

\App::run([
    'params' => $paramsGl
], true);

class Console {
        const COLOR_RED = 31;
        const COLOR_GREEN = 32;
        const FG_DEFAULT = 39, FG_BLACK = 30,
                FG_RED = 31, FG_GREEN = 32, FG_YELLOW = 33, FG_BLUE = 34, FG_MAGENTA = 35, FG_CYAN = 36,
                FG_LIGHT_GRAY = 37, FG_DARK_GRAY = 90, FG_LIGHT_RED = 91, FG_LIGHT_GREEN = 92,
                FG_LIGHT_YELLOW = 93, FG_LIGHT_BLUE = 94, FG_LIGHT_MAGENTA = 95, FG_LIGHT_CYAN = 96, FG_WHITE = 97, BG_RED = 41, BG_GREEN = 42, BG_BLUE = 44, BG_DEFAULT = 49;

        public static $con;
        
        public static function log($str, $params = [], $colors = []) {
            $paramsC = count($params);

            if (!is_array($params)) {
                $params = [$params];
            }

            if (!is_array($colors)) {
                $colors = [$colors];
            }

            for ($i = 0; $i < $paramsC; $i++) {
                $str = str_replace(
                    "{{$i}}",
                    isset($colors[$i]) ? sprintf("\033[;%dm%s\033[0m", $colors[$i], $params[$i]) : $params[$i],
                    $str
                );
            }

            echo $str;
        }

        public static function in() {
            return fgets(STDIN);
        }
    }

if ($argc > 1) {
    function toUpper($char) {
        return chr( ord($char) ^ 32 );
    }

    function firstLetterToUpper($str) {
        $str[0] = toUpper($str[0]);
        return $str;
    }

    $route = explode('/', $argv[1]);

    $controller = 'console\\controllers\\' . firstLetterToUpper( trim($route[0]) ) . 'Controller';
    $action = 'action' . firstLetterToUpper( isset($route[1]) ? trim($route[1]) : 'Index' );

    if ($argc > 2) {
        $params = array_slice($argv, 2);
    } else {
        $params = [];
    }

    $inst = new $controller();
    $inst->$action($params);
}
