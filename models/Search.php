<?php
namespace models;

use CW;
use components\helpers\ArrayHelper;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class Search {

    const LOAD_FACTOR = 15;

    /**
     * Finds updates by their tags that match the given search query.
     * @param string $term the search term.
     * @param integer $page the current page.
     * @return array found tags.
     */
    public static function searchFor($term, $page = 0) {
        if (empty($term)) {
            return [];
        }

        $tagIds = static::getTagIds($term);

        if (0 === count($tagIds)) {
            return [];
        }

        $query = "SELECT u.*,"
            . (CW::$app->user->isLogged() ? ("0 < (SELECT count(*) FROM `update_upvoters` WHERE `update_id` = `u`.`id` AND `user_id` = " . CW::$app->user->identity->id . ") ") : ' false ')
            . " `voted` FROM updates u JOIN (SELECT distinct update_id FROM update_tags WHERE tag_id IN (" . ArrayHelper::getArrayToString($tagIds, ',')
            . ") LIMIT ".static::LOAD_FACTOR." OFFSET " . (static::LOAD_FACTOR * $page) . ") ut ON ut.update_id = u.id ORDER BY u.rate DESC, u.created_at DESC";

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

    /**
     * Gets the ids of all tags that match the given term.
     * @param string $term the search term.
     * @return array the ids of the found tags.
     */
    private static function getTagIds($term) {
        $tags  = preg_split('/\s+/', $term);
        $tagsC = count($tags);

        if (0 === $tagsC) {
            return [];
        }

        $execParams = [];

        for ($i = 0; $i < $tagsC; ++$i) {
            $execParams[":tag$i"] = "{$tags[$i]}%";
        }

        $query = "SELECT id FROM tags WHERE ".ArrayHelper::getArrayToString($execParams, ' OR ', function ($v, $k) {return "name LIKE $k";});

        $stmt = CW::$app->db->prepare($query);
        $stmt->execute($execParams);

        return ArrayHelper::getKeyArray( $stmt->fetchAll(\PDO::FETCH_ASSOC), 'id' );
    }

}
