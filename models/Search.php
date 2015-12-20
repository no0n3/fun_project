<?php
namespace models;

use CW;
use components\helpers\ArrayHelper;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class Search {

    public static function searchFor($term, $page = 0) {
        if (empty($term)) {
            return [];
        }

        $tags = preg_split('/\s+/', $term);

        $stmt = CW::$app->db->executeQuery("SELECT id FROM tags WHERE name IN (".ArrayHelper::getArrayToString($tags, ',', function ($v) {return "'$v'";}).")");
        $tagIds = ArrayHelper::getKeyArray( $stmt->fetchAll(\PDO::FETCH_ASSOC), 'id' );

        if (empty($tagIds)) {
            return [];
        }

        $query = "SELECT u.* FROM updates u JOIN (SELECT distinct update_id FROM update_tags WHERE tag_id IN (" . ArrayHelper::getArrayToString($tagIds, ',')
                . ") LIMIT 10 OFFSET " . (10 * $page) . ") ut ON ut.update_id = u.id ORDER BY u.rate DESC, u.created_at DESC";

        $stmt = CW::$app->db->executeQuery($query);
        $updates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $updatesCount = count($updates);

        for ($i = 0; $i < $updatesCount; $i++) {
            $updates[$i]['imgUrl'] = '/images/updates/' . $updates[$i]['id'] . '/' . Update::IMAGE_MEDIUM_WIDTH . 'xX.jpeg';
            $updates[$i]['updateUrl'] = Update::getUpdateUrl($updates[$i]['id']);
            $updates[$i]['postedAgo'] = BaseModel::getPostedAgoTime($updates[$i]['created_at']);
            $updates[$i]['created_at'] = strtotime($updates[$i]['created_at']);
            $updates[$i]['voted'] = (bool) $updates[$i]['voted'];
        }

        $updateTags = UpdateTag::getUpdateTags(
            \components\helpers\ArrayHelper::getKeyArray($updates, 'id')
        );

        foreach ($updateTags as $updateId => $tags) {
            for ($i = 0; $i < $updatesCount; $i++) {
                if ($updates[$i]['id'] != $updateId) {
                    continue;
                }

                $updates[$i]['tags'] = $tags;
            }
        }
        
        Image::setUpdateImages($updates);

        return $updates;
    }

}
