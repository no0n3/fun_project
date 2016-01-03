<?php
namespace components\web;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class User {

    const REMBER_COOKIE_IDENTIFIER_NAME = '_rmi';
    const REMBER_COOKIE_TOKEN_NAME = '_rmt';

    const CSRF_NAME = '_csrf';

    const LOGGED_USER = 'user';

    const REMEMBER_SECONDS = 25920; // 30 days in seconds

    public $identity;
    private $_tested;

    public function __construct() {
        $this->identity = isset($_SESSION[self::LOGGED_USER]) ?
            $_SESSION[self::LOGGED_USER]:
            null;
    }

    public function isLogged() {
        if (empty($_SESSION[self::LOGGED_USER])) {
            if (null === $this->_tested) {
                $this->_tested = true;
                return $this->f2();
            }
        } else {
            return true;
        }

        return false;
    }

    private function f2() {
        $userId = $this->f1();

        if ($userId) {
            $stmt = \CW::$app->db->executeQuery("SELECT id, username, profile_img_id FROM `users` WHERE `id` = $userId");
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (0 < count($result)) {
                $result = $result[0];

                $this->_login(
                    $result['id'],
                    $result['username'],
                    $result['profile_img_id']
                );

                return true;
            }
        }

        return false;
    }

    private function f1() {
        $rememberMe = isset($_COOKIE[static::REMBER_COOKIE_TOKEN_NAME]) ? $_COOKIE[static::REMBER_COOKIE_TOKEN_NAME] : null;

        if ($rememberMe) {
            $stmt = \CW::$app->db->prepare("SELECT user_id FROM remember_user WHERE uuid = :uuid");
            $stmt->execute([
                ':uuid' => $rememberMe
            ]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (0 < count($result)) {
                return $result[0]['user_id'];
            }
        }

        return false;
    }

    private function _login($userId, $username, $profile_img_id, $remember = false) {
        $logedUser = new \models\User();
        $logedUser->id = $userId;
        $logedUser->username = $username;
        $logedUser->profile_img_id = $profile_img_id;

        $this->identity = $_SESSION['user'] = $logedUser;
        $_SESSION['_csrf'] = sprintf("%d-%s", $logedUser->id, uniqid());

        if ($remember) {
            $uuid = sprintf("%d:%s", $logedUser->id, uniqid());
            $uuidHash = \components\Security::hash($uuid);
            $token = sprintf("%d:%s", $logedUser->id, time());

            $query = sprintf(
                "INSERT INTO remember_user (user_id, uuid, token) VALUES (%d, '%s', '%s') ON DUPLICATE KEY UPDATE user_id = %d, uuid = '%s', token = '%s'",
                $logedUser->id,
                $uuidHash,
                $token,
                $logedUser->id,
                $uuidHash,
                $token
            );

            \CW::$app->db->executeUpdate($query);

            setcookie(
                static::REMBER_COOKIE_TOKEN_NAME,
                $uuid,
                static::REMEMBER_SECONDS,
                '/'
            );

            setcookie(
                static::REMBER_COOKIE_IDENTIFIER_NAME,
                $uuid,
                static::REMEMBER_SECONDS,
                '/'
            );
        }
    }

    public function inRole($roles) {
        if (in_array(Controller::REQUIRED_LOGIN, $roles)) {
            return $this->isLogged();
        } else if (in_array('not_logged', $roles)) {
            return !$this->isLogged();
        }

        return true;
    }

    public function login($email, $password, $remember = false) {
        if ($this->isLogged()) {
            return true;
        }

        $loginSuccess = false;

        $stmt = \CW::$app->db->prepare('SELECT id, username, email, password, profile_img_id FROM `users` WHERE `email` = :email');
        $stmt->execute([
            ':email' => $email
        ]);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (0 < count($result)) {
            $result = $result[0];

            $loginSuccess = \components\Security::verifyHash($password, $result['password']);

            if ($loginSuccess) {
                $this->_login(
                    $result['id'],
                    $result['username'],
                    $result['profile_img_id'],
                    $remember
                );
            }
        }

        return $loginSuccess;
    }

    public function signUp($username, $email, $password) {
        $stmt = \CW::$app->db->prepare("INSERT INTO `users` (`username`, `password`, `email`) VALUES (:username, :password, :email)");

        return $stmt->execute([
            ':username' => $username,
            ':password' => \components\Security::hash($password),
            ':email' => $email
        ]);
    }

    public function logout() {
        if (!empty($_SESSION[static::LOGGED_USER])) {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            if (isset($_SESSION[self::LOGGED_USER])) {
                $loggedUserId = $_SESSION[self::LOGGED_USER]->id;
                $loggedOut = true;
            } else {
                $loggedOut = false;
            }

            setcookie(
                static::REMBER_COOKIE_TOKEN_NAME,
                null,
                -1,
                '/'
            );

            setcookie(
                static::REMBER_COOKIE_IDENTIFIER_NAME,
                null,
                -1,
                '/'
            );

            \CW::$app->db->executeUpdate("DELETE FROM remember_user WHERE user_id = $loggedUserId");

            session_destroy();
            unset($_SESSION);

            return $loggedOut;
        }
        
        return true;
    }
}
