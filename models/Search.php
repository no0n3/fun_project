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

        if (0 === count($tagIds)) {
            return [];
        }

        $query = "SELECT u.*,"
                . (CW::$app->user->isLogged() ? ("0 < (SELECT count(*) FROM `update_upvoters` WHERE `update_id` = `u`.`id` AND `user_id` = " . CW::$app->user->identity->id . ") ") : ' false ')
                . " `voted` FROM updates u JOIN (SELECT distinct update_id FROM update_tags WHERE tag_id IN (" . ArrayHelper::getArrayToString($tagIds, ',')
                . ") LIMIT 10 OFFSET " . (10 * $page) . ") ut ON ut.update_id = u.id ORDER BY u.rate DESC, u.created_at DESC";

        $stmt = CW::$app->db->executeQuery($query);
        $updates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($updates as &$update) {
            $update['imgUrl'] = '/images/updates/' . $update['id'] . '/' . Update::IMAGE_MEDIUM_WIDTH . 'xX.jpeg';
            $update['updateUrl'] = Update::getUpdateUrl($update['id']);
            $update['postedAgo'] = BaseModel::getPostedAgoTime($update['created_at']);
            $update['created_at'] = strtotime($update['created_at']);
            $update['voted'] = (bool) $update['voted'];
        }

        if (0 < count($updates)) {
            Update::setUpdateTags($updates);
            Update::setUpdatePostedFrom($updates);
            Image::setUpdateImages($updates);
        }

        return $updates;
    }

}
