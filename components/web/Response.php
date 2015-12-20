<?php
namespace components\web;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class Response {

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

    public function setContentType($contentType = 'text/html', $charset = 'utf-8') {
        header("Content-Type:{$contentType}; charset={$charset}");
    }

}
