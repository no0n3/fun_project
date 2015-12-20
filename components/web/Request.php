<?php
namespace components\web;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class Request {

    private function __construct() {
        // prevent public instantiation.
    }

    public static function getInstance() {
        static $inst = null;

        if (null === $inst) {
            $inst = new self();
        }

        return $inst;
    }

    public function get($name = null) {
        return null === $name ? ($_GET) : (isset($_GET[$name]) ? $_GET[$name] : null);
    }

    public function post($name = null) {
        return null === $name ? ($_POST) : (isset($_POST[$name]) ? $_POST[$name] : null);
    }

    public function param($name = null) {
        return null === $name ? ($_REQUEST) : (isset($_REQUEST[$name]) ? $_REQUEST[$name] : null);
    }

    public function isAjax() {
       return 'xmlhttprequest' === strtolower(filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH'));
    }

    public function isPost() {
        return strtolower($_SERVER['REQUEST_METHOD']) === 'post';
    }

}
