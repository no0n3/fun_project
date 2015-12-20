<?php
namespace models;

use CW;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class Category {

    /**
     * 
     * @param type $categoryName
     * @return type
     */
    public static function getIdByName($categoryName) {
        $stmt = CW::$app->db->prepare("SELECT `id` FROM `categories` WHERE `name` = :categoryName");

        $stmt->execute([
            ':categoryName' => $categoryName
        ]);

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return 1 === count($result) ? $result[0]['id'] : null;
    }

    /**
     * 
     * @return type
     */
    public static function getAllCategories() {
        $stmt = CW::$app->db->executeQuery("SELECT `id`, `name` FROM `categories`");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

}
