<?php
namespace models;

use CW;
use components\helpers\ArrayHelper;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class Image {
    const REL_TYPE_USER   = 'user';
    const REL_TYPE_UPDATE = 'update';

    const TYPE_PROFILE_PIC = 'profile_pic';
    const TYPE_HIGH_IMAGE  = 'high_image';
    const TYPE_IMAGE       = 'image';

    const IMAGE_TYPE_NORMAL = 1;
    const IMAGE_TYPE_VIDEO  = 2;

    /**
     * 
     * @param type $data
     */
    public static function create($data) {
        $query = sprintf(
                "INSERT INTO images (`rel_id`, `rel_type`, `type`, `image_type`, `created_at`) VALUES (%d, '%s', '%s', %d, %d)",
                $data['rel_id'],
                $data['rel_type'],
                $data['type'],
                $data['image_type'],
                time()
            );
        
        if (0 < CW::$app->db->executeUpdate($query)) {
            return CW::$app->db->getLastInsertedId();
        }

        return null;
    }

    /**
     * 
     * @param type $updates
     * @return type
     */
    public static function setUpdateImages(&$updates) {
        if (!is_array($updates) || 0 >= count($updates)) {
            return [];
        }

        $images = static::getImages(ArrayHelper::getKeyArray($updates, 'id'), static::REL_TYPE_UPDATE);

        $updatesC = count($updates);

        for ($i = 0; $i < $updatesC; $i++) {
            if (isset($images[$updates[$i]['id']])) {
                $updates[$i]['isHighImage'] = static::TYPE_HIGH_IMAGE == $images[$updates[$i]['id']]['type'];
            }
        }
    }

    public static function setUserProfileImages(&$users) {
        if (!is_array($users) || 0 >= count($users)) {
            return [];
        }

        $images = static::getImages(
            ArrayHelper::getKeyArray($users, 'id'),
            static::REL_TYPE_USER,
            static::TYPE_PROFILE_PIC
        );

        $usersC = count($users);

        for ($i = 0; $i < $usersC; $i++) {
            if (isset($images[$users[$i]['id']])) {
                $users[$i]['imgUrl'] = "/images/users/{$users[$i]['id']}/200x200.jpeg";
            } else {
                $users[$i]['imgUrl'] = "/images/default_avatar.jpg";
            }
        }
    }

    /**
     * 
     * @param type $relIds
     * @param type $relTypes
     * @return type
     */
    public static function getImages($relIds, $relTypes = null, $type = null) {
        $relTypes = empty($relTypes) ? "" :
            (is_array($relTypes) ?
            sprintf("IN (%s)", ArrayHelper::getArrayToString($relTypes, ',')) :
            " = '$relTypes'");

        $relIds = is_array($relIds) ?
            sprintf("IN (%s)", ArrayHelper::getArrayToString($relIds, ',')) :
            " = $relIds";

        $query = "SELECT * FROM images WHERE rel_type ".('' === $relTypes ? '' : "$relTypes AND")." rel_id $relIds" . (in_array($type, [
            static::TYPE_HIGH_IMAGE,
            static::TYPE_IMAGE,
            static::TYPE_PROFILE_PIC
        ]) ? " AND `type` = '$type'" : '');

        $stmt = CW::$app->db->executeQuery($query);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $r = [];

        foreach ($result as $item) {
            $r[ $item['rel_id'] ] = $item;
        }

        return $r;
    }
}
