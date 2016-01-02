<?php
namespace models;

use CW;
use components\helpers\ArrayHelper;
use components\UrlManager;

/**
 * 
 */
class UpdateTag {

    /**
     * 
     * @param type $updateIds
     * @return type
     */
    public static function getUpdatesTags($updateIds) {
        if (is_numeric($updateIds)) {
            return self::getUpdateTags($updateIds);
        } else if (empty($updateIds) || !is_array($updateIds)) {
            return [];
        }

        $stmt = CW::$app->db->executeQuery("SELECT t.name, t.id, ut.update_id FROM tags t JOIN update_tags ut ON t.id = ut.tag_id WHERE ut.update_id IN (".ArrayHelper::getArrayToString($updateIds, ',').")");
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $r = [];

        foreach ($result as $item) {
            $item['url']  = UrlManager::to(['site/search', 'term' => $item['name']]);
            $item['name'] = htmlspecialchars($item['name']);

            if (!isset( $r[$item['update_id']] )) {
                $r[ $item['update_id'] ] = [$item];
            } else {
                $r[ $item['update_id'] ][] = $item;
            }
        }

        return $r;
    }

    /**
     * 
     * @param type $updateId
     * @return type
     */
    public static function getUpdateTags($updateId) {
        if (is_array($updateId)) {
            return self::getUpdatesTags($updateId);
        } else if (!is_numeric($updateId)) {
            return [];
        }

        $stmt = CW::$app->db->executeQuery("SELECT t.name, t.id, ut.update_id FROM tags t JOIN update_tags ut ON t.id = ut.tag_id WHERE ut.update_id = $updateId");

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

}
