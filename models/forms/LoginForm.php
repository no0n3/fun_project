<?php
namespace models\forms;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class LoginForm extends \models\BaseModel {

    public $username;
    public $password;

    public function rules() {
        return [
            'email' => [
                'type' => 'email',
                'filter' => function($value) {
                    return $value;
                },
                'validator' => function($name, $value) {
                    return $value < 2 || $value > 255;
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
                'filter' => function($value) {
                    return $value;
                },
                'validator' => function($name, $value) {
                    return strlen($value) > 0;
                },
                'clientValidator' => function() {
                    return <<<JS
                        function (value) {
                            return value.length <= 0;
                        }
JS;
                },
                'message' => 'Password cannot be empty.'
            ],
        ];
    }

    public function getAttributeLabels() {
        return [
            'email' => 'Email',
            'password' => 'Password'
        ];
    }

}
