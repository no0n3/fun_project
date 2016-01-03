<?php
namespace components\helpers;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class ArrayHelper {

    public static function indexBy($a, $key) {
        $_a = [];

        foreach ($a as $k => $v) {
            $_a[$v[$key]] = $v;
        }

        return $_a;
    }

    public static function getKeyArray($a, $key) {
        $_a = [];

        foreach ($a as $k => $v) {
            $_a[] = $v[$key];
        }

        return $_a;
    }

    public static function getArrayToString($a, $sep, $each = null) {
        $_a = [];

        if (null !== $each) {
            foreach ($a as $k => $v) {
                $_a[] = $each($v, $k);
            }
        } else {
            $_a = $a;
        }

        return implode($sep, $_a);
    }

    public static function keyArray($a) {
        $_a = [];

        foreach ($a as $k => $v) {
            $_a[] = $k;
        }

        return $_a;
    }

    public static function column($a) {
        $_a = [];

        foreach ($a as $k => $v) {
            $_a[] = $v[0];
        }

        return $_a;
    }
}
