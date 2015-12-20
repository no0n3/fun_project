<?php
namespace components;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class Security {

    public static function hash($string) {
        $salt = mcrypt_create_iv(22, MCRYPT_DEV_URANDOM);
        $salt = base64_encode($salt);
        $salt = str_replace('+', '.', $salt);

        return crypt($string, '$2y$10$'.$salt.'$');
    }

    public static function verifyHash($string, $hash) {
        return password_verify($string, $hash);
    }

}
