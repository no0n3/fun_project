<?php
namespace models\forms;

use CW;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class SignUpForm extends \models\BaseModel {

    public $username;
    public $password;

    public function rules() {
        return [
            'email' => [
                'type' => 'email',
                'validator' => function($name, $value) {

                    $this->$name = $value = trim($value);

                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->addError($name, "Please enter a valid email.");

                        return false;
                    }

                    $valueC = strlen($value);

                    if ($valueC < 2 || $valueC > 255) {
                        return false;
                    }

                    $stmt = CW::$app->db->prepare('SELECT count(*) c FROM users WHERE email = :email');
                    $stmt->execute([
                        ':email' => $value
                    ]);

                    $result = $stmt->fetchAll(\PDO::FETCH_ASSOC)[0];

                    if (0 < $result['c']) {
                        $this->addError($name, 'Email \'' . htmlspecialchars($value) . '\' is already registered.');

                        return false;
                    }

                    return true;
                }
            ],
            'username' => [
                'validator' => function($name, $value) {
                    $this->$name = $value = trim($value);
                    $valueC = strlen($value);

                    if ($valueC < 2 || $valueC > 255) {
                        return false;
                    }

                    $stmt = CW::$app->db->prepare('SELECT count(*) c FROM users WHERE username = :username');
                    $stmt->execute([
                        ':username' => $value
                    ]);

                    $result = $stmt->fetchAll(\PDO::FETCH_ASSOC)[0];

                    if (0 < $result['c']) {
                        $this->addError($name, 'Username \'' . htmlspecialchars($value) . '\' already taken.');

                        return false;
                    }

                    return true;
                },
                'clientValidator' => function() {
                    return <<<JS
                        function (value) {
                            return value.length < 2 || value.length > 255;
                        }
JS;
                },
                'message' => 'Username must be at least 2 characters long.'
            ],
            'password' => [
                'validator' => function($name, $value) {
                    return $value < 6 || $value > 255;
                },
                'clientValidator' => function() {
                    return <<<JS
                        function (value) {
                            return value.length < 6 || value.length > 255;
                        }
JS;
                },
                'message' => 'Password must be at least 6 characters long.'
            ],
        ];
    }

    public function getAttributeLabels() {
        return [
            'email' => 'Email',
            'username' => 'Username',
            'password' => 'Password'
        ];
    }

}
