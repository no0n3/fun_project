<?php
namespace components;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class Component extends \classes\Objectv {
    private $scenario;

    public function __construct($data = []) {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }
}
