<?php
namespace classes;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class Object {

    public function getClassName($withNamespace = true) {
        if (false === $withNamespace) {
            $name = explode('\\', get_called_class());

            return is_array($name) ? $name[count($name) - 1] : $name;
        }

        return get_called_class();
    }

    public function hasProperty($prop) {
        return property_exists($this, $prop);
    }

    public function hasMethod($name) {
        return method_exists($this, $name);
    }

    public function getSimpleClassName() {
        return \components\helpers\StringHelper::slugify(
            $this->getClassName(false)
        );
    }

    public function __get($name) {
        $_n = $name;
        $_n[0] = chr(ord($_n) ^ 32);
        $_n = "get$_n";

        return $this->hasMethod($_n) ? $this->$_n() : null;
    }

}
