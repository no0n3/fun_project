<?php
namespace models\forms;

use CW;
use components\helpers\ArrayHelper;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class EditProfileForm extends \models\BaseModel {

    public $username;
    public $description;

    public function rules() {
        return [
            'username' => [
                'validator' => function($name, $value) {
                    $this->$name = $value = trim($value);
                    $valueC = strlen($value);

                    if ($valueC < 2 || $valueC > 255) {
                        return false;
                    }

                    $stmt = CW::$app->db->prepare('SELECT count(*) c FROM users WHERE username = :username AND id != :id');
                    $stmt->execute([
                        ':username' => $value,
                        ':id' => \CW::$app->user->identity->id
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
            'description' => [],
            'categories' => []
        ];
    }

    public function getAttributeLabels() {
        return [
            'username' => 'Username',
            'description' => 'Description'
        ];
    }

    public function save() {
        if (!$this->validate()) {
            return false;
        }

        $toAdd = [];
        $toDelete = [];

        CW::$app->db->beginTransaction();

        foreach (!is_array($this->categories) ? [] : $this->categories as $category) {
            if (!in_array($category, $this->userCategories)) {
                $toAdd[] = $category;
            }
        }

        foreach ($this->userCategories as $category) {
            if (!in_array($category, $this->categories)) {
                $toDelete[] = $category;
            }
        }

        if (0 < count($toAdd)) {
            $query = sprintf(
                "INSERT INTO `user_categories` (`user_id`, `category_id`) VALUES %s",
                ArrayHelper::getArrayToString($toAdd, ',', function ($v) {
                    return "($this->userId, $v)";
                })
            );
            CW::$app->db->executeUpdate($query);
        }

        if (0 < count($toDelete)) {
            $query = sprintf(
                "DELETE FROM `user_categories` WHERE `user_id` = %d AND `category_id` IN (%s)",
                $this->userId,
                ArrayHelper::getArrayToString($toDelete, ',')
            );
            CW::$app->db->executeUpdate($query);
        }

        $stmt = CW::$app->db->prepare('UPDATE `users` SET `username` = :username, `description` = :description WHERE `id` = :userId');
        $success = $stmt->execute([
            ':username' => $this->username,
            ':description' => $this->description,
            ':userId' => $this->userId
        ]);

        CW::$app->db->commit();

        return $success;
    }

}
