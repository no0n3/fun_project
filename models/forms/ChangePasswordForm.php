<?php
namespace models\forms;

use CW;
use components\Security;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class ChangePasswordForm extends \models\BaseModel {

    public $oldPassword;
    public $newPassword;
    public $confirmPassword;

    public function rules() {
        $jsValidator = function() {
                    return <<<JS
                        function (value) {
                            return value.length < 6 || value.length > 255;
                        }
JS;
        };

        $validator = function($name, $value) {
            $valueLen = strlen($value);

            return $valueLen < 6 || $valueLen > 255;
        };

        return [
            'oldPassword' => [
                'validator' => $validator,
                'clientValidator' => $jsValidator,
                'message' => 'Old password must be at least 2 characters long.'
            ],
            'newPassword' => [
                'validator' => $validator,
                'clientValidator' => $jsValidator,
                'message' => 'Old password must be at least 2 characters long.'
            ],
            'confirmPassword' => [
                'validator' => $validator,
                'clientValidator' => $jsValidator,
                'message' => 'Old password must be at least 2 characters long.'
            ],
        ];
    }

    public function getAttributeLabels() {
        return [
            'oldPassword' => 'Password',
            'newPassword' => 'New Password',
            'confirmPassword' => 'Confirm Password'
        ];
    }

    public function save() {
        $stmt = CW::$app->db->executeQuery("SELECT `password` FROM `users` WHERE `id` = $this->userId");
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $oldPass = 0 < count($result) ? $result[0]['password'] : null;

        if (null === $oldPass) {
            return false;
        }

        if ($this->newPassword === $this->confirmPassword && Security::verifyHash($this->oldPassword, $oldPass)) {
            $stmt = CW::$app->db->prepare("UPDATE `users` SET `password` = :newPassword WHERE `id` = :userId");

            return $stmt->execute([
                ':newPassword' => Security::hash($this->newPassword),
                ':userId' => $this->userId
            ]);
        }

        return false;
    }

}
