<?php
namespace models;

use CW;
use components\UrlManager;
use components\helpers\ArrayHelper;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class Update {

    const TYPE_FRESH = 'fresh';
    const TYPE_TRENDING = 'trending';

    const IMAGE_BIG_WIDTH = 800;
    const IMAGE_MEDIUM_WIDTH = 500;
    const IMAGE_SMALL_WIDTH = 250;

    const ACTIVITY_TYPE_UPVOTE = 1;
    const ACTIVITY_TYPE_COMMENT = 2;
    const ACTIVITY_TYPE_POST = 4;

    const ACTIVITY_TYPE_ALL_STR = 'all';
    const ACTIVITY_TYPE_UPVOTE_STR = 'upvoted';
    const ACTIVITY_TYPE_COMMENT_STR = 'commented';
    const ACTIVITY_TYPE_POST_STR = 'posted';

    const POPULAR_UPDATES_LIMIT = 15;
    const UPDATES_LOAD_COUNT = 10;

    public static function getAllActivityTypesAsString() {
        return [
            static::ACTIVITY_TYPE_COMMENT_STR,
            static::ACTIVITY_TYPE_POST_STR,
            static::ACTIVITY_TYPE_UPVOTE_STR
        ];
    }

    public static function isValidType($type) {
        return in_array($type, [self::TYPE_FRESH, self::TYPE_TRENDING]);
    }

    public static function getUpdates(
        $page,
        $categoryId = null,
        $type = self::TYPE_FRESH,
        $categoryName = null
    ) {
        $page = (is_numeric($page) ? ($page * self::UPDATES_LOAD_COUNT) : 0);

        if (!self::isValidType($type)) {
            $type = self::TYPE_FRESH;
        }

        if (empty($categoryId)) {
            if ($type === self::TYPE_FRESH) {
                $query = "SELECT *, "
                    . (CW::$app->user->isLogged() ? ("0 < (SELECT count(*) FROM `update_upvoters` WHERE `update_id` = `updates`.`id` AND `user_id` = " . CW::$app->user->identity->id . ") ") : ' false ')
                    . " `voted` FROM `updates` ORDER BY `created_at` DESC LIMIT " . self::UPDATES_LOAD_COUNT . " OFFSET $page";
            } else {
                $query = "SELECT *, "
                    . (CW::$app->user->isLogged() ? ("0 < (SELECT count(*) FROM `update_upvoters` WHERE `update_id` = `updates`.`id` AND `user_id` = " . CW::$app->user->identity->id . ") ") : ' false ')
                    . " `voted` FROM `updates` WHERE `rate` > 0"
                    . " ORDER BY `rate`, `created_at` LIMIT " . self::UPDATES_LOAD_COUNT . " OFFSET $page";
            }
        } else {
            if (!is_numeric($categoryId)) {
                return [];
            }

            if ($type === self::TYPE_FRESH) {
                $query = "SELECT u.*, "
                    . (CW::$app->user->isLogged() ? ("0 < (SELECT count(*) FROM `update_upvoters` WHERE `update_id` = `u`.`id` AND `user_id` = " . CW::$app->user->identity->id . ") ") : ' false ')
                    . " `voted` FROM `updates` u JOIN `update_categories` uc ON uc.`update_id` = u.id WHERE uc.category_id = $categoryId "
                    . " ORDER BY `created_at` DESC LIMIT " . self::UPDATES_LOAD_COUNT . " OFFSET $page";
            } else {
                $query = "SELECT u.*, "
                    . (CW::$app->user->isLogged() ? ("0 < (SELECT count(*) FROM `update_upvoters` WHERE `update_id` = `u`.`id` AND `user_id` = " . CW::$app->user->identity->id . ") ") : ' false ')
                    . " `voted` FROM `updates` u JOIN `update_categories` uc ON uc.`update_id` = u.id WHERE uc.category_id = $categoryId  AND `rate` > 0"
                    . " ORDER BY `rate`, `created_at` LIMIT " . self::UPDATES_LOAD_COUNT . " OFFSET $page";
            }
        }

        $stmt = CW::$app->db->executeQuery($query);
        $updates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $updatesCount = count($updates);

        for ($i = 0; $i < $updatesCount; $i++) {
            $updates[$i]['description'] = htmlspecialchars($updates[$i]['description']);
            $updates[$i]['imgUrl'] = '/images/updates/' . $updates[$i]['id'] . '/' . self::IMAGE_MEDIUM_WIDTH . 'xX.jpeg';
            $updates[$i]['updateUrl'] = self::getUpdateUrl($updates[$i]['id'], $categoryName);
            $updates[$i]['postedAgo'] = BaseModel::getPostedAgoTime($updates[$i]['created_at']);
            $updates[$i]['created_at'] = strtotime($updates[$i]['created_at']);
            $updates[$i]['voted'] = (bool) $updates[$i]['voted'];
        }

        if (0 < $updatesCount) {
            self::setUpdateTags($updates);
            self::setUpdatePostedFrom($updates);
            Image::setUpdateImages($updates);
        }

        return $updates;
    }

    /**
     * 
     * @param array $updates
     * @return type
     */
    public static function setUpdateTags(&$updates) {
        if (!is_array($updates) || 0 === count($updates)) {
            return;
        }

        $updateTags = UpdateTag::getUpdateTags(
            ArrayHelper::getKeyArray($updates, 'id')
        );
        
        $updatesCount = count($updates);

        foreach ($updateTags as $updateId => $tags) {
            for ($i = 0; $i < $updatesCount; $i++) {
                if ($updates[$i]['id'] != $updateId) {
                    continue;
                }

                $updates[$i]['tags'] = $tags;
            }
        }

        return $updates;
    }

    /**
     * 
     * @param array $updates
     * @return type
     */
    public static function setUpdatePostedFrom(&$updates) {
        if (!is_array($updates) || 0 === count($updates)) {
            return;
        }

        $users = [];

        foreach ($updates as &$update) {
            if (!isset($users[ $update['user_id'] ])) {
                $users[ $update['user_id'] ] = [];
            }

            $users[ $update['user_id'] ][] = &$update;
        }

        $userIds = ArrayHelper::keyArray($users);

        if (0 === count($userIds)) {
            return;
        }

        $stmt = \CW::$app->db->executeQuery('SELECT id, username, profile_img_id FROM users WHERE id IN (' . ArrayHelper::getArrayToString($userIds, ',') . ')');
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as $user) {
            $user['imgUrl']     = \models\User::getProfilePictureUrl($user['profile_img_id'], $user['id']);
            $user['username']   = htmlspecialchars($user['username']);
            $user['profileUrl'] = \models\User::getProfileUrl($user['id']);

            foreach ($users[$user['id']] as &$update) {
                $update['from'] = $user;
            }
        }
    }

    /**
     * 
     * @param type $time
     * @param type $userId
     * @param type $type
     * @return boolean
     */
    public static function getUserUpdates($userId, $type, $page = 0) {
        if (!is_numeric($userId)) {
            return false;
        }

        static::validateActivityType($type);

        if ('all' === $type) {
            $where = '';
        } else if ('posted' === $type) {
            $where = 'AND ua.type_posted= 1';
        } else if ('upvoted' === $type) {
            $where = 'AND ua.type_upvoted = 1';
        } else if ('commented' === $type) {
            $where = 'AND ua.type_commented = 1';
        } else {
            $where = '';
        }

        $query = "SELECT u.*, ua.type_posted, ua.type_upvoted, ua.type_commented, "
            . (CW::$app->user->isLogged() ? ("0 < (SELECT count(*) FROM `update_upvoters` WHERE `update_id` = `u`.`id` AND `user_id` = " . CW::$app->user->identity->id . ") ") : ' false ')
            . " `voted` FROM `updates` u JOIN `user_update_activity` ua ON ua.update_id = u.id WHERE ua.`user_id` = $userId $where ORDER BY ua.`time` DESC LIMIT " . self::UPDATES_LOAD_COUNT
            . " OFFSET " . (self::UPDATES_LOAD_COUNT * $page);

        $stmt = CW::$app->db->executeQuery($query);

        $updates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $updatesCount = count($updates);

        for ($i = 0; $i < $updatesCount; $i++) {
            $updates[$i]['description'] = htmlspecialchars($updates[$i]['description']);
            $updates[$i]['imgUrl'] = '/images/updates/' . $updates[$i]['id'] . '/' . self::IMAGE_MEDIUM_WIDTH . 'xX.jpeg';
            $updates[$i]['updateUrl'] = self::getUpdateUrl($updates[$i]['id']);
            $updates[$i]['postedAgo'] = BaseModel::getPostedAgoTime($updates[$i]['created_at']);
            $updates[$i]['created_at'] = strtotime($updates[$i]['created_at']);
            $updates[$i]['voted'] = (bool) $updates[$i]['voted'];
            $updates[$i]['activity_type'] =
                ($updates[$i]['type_posted'] ? Update::ACTIVITY_TYPE_POST : 0) |
                ($updates[$i]['type_upvoted'] ? Update::ACTIVITY_TYPE_UPVOTE : 0) |
                ($updates[$i]['type_commented'] ? Update::ACTIVITY_TYPE_COMMENT : 0);
        }

        if (0 < $updatesCount) {
            self::setUpdateTags($updates);
            self::setUpdatePostedFrom($updates);
            Image::setUpdateImages($updates);
        }

        return $updates;
    }

    /**
     * 
     * @param string $type
     */
    private static function validateActivityType(&$type) {
        if (!in_array($type, ['posted', 'upvoted', 'commented'])) {
            $type = 'all';
        }
    }

    public static function getPopularUpdates() {
        $query = "SELECT * FROM `updates` WHERE `created_at` > '" . (date("Y-m-d H:i:s", strtotime('-3 days'))) . "' "
                . "AND `rate` > 0"
                . " ORDER BY `rate` DESC LIMIT " . self::POPULAR_UPDATES_LIMIT;

        $stmt = CW::$app->db->executeQuery($query);

        $updates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $updatesCount = count($updates);

        for ($i = 0; $i < $updatesCount; $i++) {
            $updates[$i]['description'] = htmlspecialchars($updates[$i]['description']);
            $updates[$i]['imgUrl'] = '/images/updates/' . $updates[$i]['id'] . '/' . self::IMAGE_MEDIUM_WIDTH . 'xX.jpeg';
            $updates[$i]['updateUrl'] = self::getUpdateUrl($updates[$i]['id']);
            $updates[$i]['postedAgo'] = BaseModel::getPostedAgoTime($updates[$i]['created_at']);
            $updates[$i]['created_at'] = strtotime($updates[$i]['created_at']);
        }

        return $updates;
    }

    public static function getOne($id) {
        if (!is_numeric($id)) {
            return null;
        }

        $stmt = CW::$app->db->executeQuery("SELECT *, "
            . (CW::$app->user->isLogged() ? ("0 < (SELECT count(*) FROM `update_upvoters` WHERE `update_id` = `updates`.`id` AND `user_id` = " . CW::$app->user->identity->id . ") ") : ' false ')
            . " `voted` FROM `updates` WHERE `id` = $id");
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (1 === count($result)) {
            $update = $result[0];
            $update['description'] = htmlspecialchars($update['description']);
            $update['postedAgo'] = BaseModel::getPostedAgoTime($update['created_at']);
            $update['voted'] = (bool) $update['voted'];

            $update['tags'] = UpdateTag::getUpdateTags($update['id']);

            return $update;
        }

        return null;
    }

    public static function getNext($id, $category) {
        if (!is_numeric($id)) {
            return null;
        }

        if (!empty($category)) {
            $stmt = CW::$app->db->prepare("SELECT u.`created_at`, u.`upvotes`, u.`rate`, uc.category_id FROM `updates` u JOIN `update_categories` uc ON uc.update_id = u.id JOIN categories c ON c.id = uc.category_id WHERE u.`id` = :id AND c.name = :categoryName");
            $stmt->execute([
                ':id' => $id,
                ':categoryName' => $category
            ]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (1 === count($result)) {
                $createdAt = $result[0]['created_at'];
                $upvotes = $result[0]['upvotes'];
                $rate = $result[0]['rate'];
                $categoryId = $result[0]['category_id'];
            } else {
                return null;
            }
        } else {
            $stmt = CW::$app->db->executeQuery("SELECT `created_at`, `rate`, `upvotes` FROM `updates` WHERE `id` = $id");
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (1 === count($result)) {
                $createdAt = $result[0]['created_at'];
                $rate = $result[0]['rate'];
                $upvotes = $result[0]['upvotes'];
            } else {
                return null;
            }
        }

        if (isset($categoryId)) {
            $query = "SELECT u.`id` FROM `updates` u JOIN update_categories uc ON uc.update_id = u.id WHERE uc.category_id = $categoryId AND u.`created_at` < '$createdAt'"
                . " ORDER BY u.`created_at`"
                . " DESC LIMIT 1";

            $stmt = CW::$app->db->executeQuery($query);

            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (1 === count($result)) {
                return $result[0]['id'];
            }

            return null;
        } else {
            $query = "SELECT `id` FROM `updates` WHERE `created_at` < '$createdAt'"
                . " ORDER BY `created_at`"
                . " DESC LIMIT 1";

            $stmt = CW::$app->db->executeQuery($query);

            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (1 === count($result)) {
                return $result[0]['id'];
            }

            return null;
        }
    }

    public static function getPrev($id, $category = null) {
        if (!is_numeric($id)) {
            return null;
        }

        if (!empty($category)) {
            $stmt = CW::$app->db->prepare("SELECT u.`created_at`, u.`upvotes`, u.`rate`, uc.category_id FROM `updates` u JOIN `update_categories` uc ON uc.update_id = u.id JOIN categories c ON c.id = uc.category_id WHERE u.`id` = :id AND c.name = :categoryName");
            $stmt->execute([
                ':id' => $id,
                ':categoryName' => $category
            ]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (1 === count($result)) {
                $createdAt = $result[0]['created_at'];
                $upvotes = $result[0]['upvotes'];
                $rate = $result[0]['rate'];
                $categoryId = $result[0]['category_id'];
            } else {
                return null;
            }
        } else {
            $stmt = CW::$app->db->executeQuery("SELECT `created_at`, `rate`, `upvotes` FROM `updates` WHERE `id` = $id");
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (1 === count($result)) {
                $createdAt = $result[0]['created_at'];
                $rate = $result[0]['rate'];
                $upvotes = $result[0]['upvotes'];
            } else {
                return null;
            }
        }

        if (isset($categoryId)) {
            $query = "SELECT u.`id` FROM `updates` u JOIN update_categories uc ON uc.update_id = u.id WHERE uc.category_id = $categoryId AND u.`created_at` > '$createdAt'"
                . " ORDER BY u.`created_at`"
                . " ASC LIMIT 1";

            $stmt = CW::$app->db->executeQuery($query);

            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (1 === count($result)) {
                return $result[0]['id'];
            }

            return null;
        } else {
            $query = "SELECT `id` FROM `updates` WHERE `created_at` > '$createdAt'"
                . " ORDER BY `created_at`"
                . " ASC LIMIT 1";

            $stmt = CW::$app->db->executeQuery($query);

            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (1 === count($result)) {
                return $result[0]['id'];
            }

            return null;
        }
    }

    public static function getUpdateImageUrl($updateId, $size = self::IMAGE_MEDIUM_WIDTH) {
        return '/images/updates/' . $updateId . '/' . $size . 'xX.jpeg';
    }

    public static function getUpdateUrl($updateId, $categoryName = null) { 
        return '' !== trim($categoryName) ?
            UrlManager::to(['update/view', 'id' => $updateId, 'category' => $categoryName], true)
            : UrlManager::to(['update/view', 'id' => $updateId], true);
    }

    public static function upvote($updateId, $userId) {
        if (!is_numeric($updateId) || !is_numeric($userId)) {
            return false;
        }

        if (!self::addActivity($updateId, $userId, self::ACTIVITY_TYPE_UPVOTE)) {
            return false;
        }
        $stmt = CW::$app->db->executeQuery("SELECT `upvotes`, `created_at` FROM `updates` WHERE `id` = $updateId");
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (0 < count($result)) {
            $rate = self::calculateRankSum($result[0]['upvotes'] + 1, $result[0]['created_at']);
        }
        CW::$app->db->executeUpdate("INSERT INTO `update_upvoters` (`user_id`, `update_id`, `voted_at`) VALUES ($userId, $updateId, ".time().")");

        if (0 >= CW::$app->db->executeUpdate("UPDATE `updates` SET upvotes = upvotes + 1, rate = $rate WHERE `id` = $updateId")) {
            return false;
        }

        return true;
    }

    public static function unvote($updateId, $userId) {
        if (!is_numeric($updateId) || !is_numeric($userId)) {
            return false;
        }

        if (!static::removeActivity($updateId, $userId, static::ACTIVITY_TYPE_UPVOTE)) {
            return false;
        }

        if (0 >= CW::$app->db->executeUpdate("DELETE FROM `update_upvoters` WHERE `user_id` = $userId AND `update_id` = $updateId")) {
            return false;
        }

        $stmt = CW::$app->db->executeQuery("SELECT `upvotes`, `created_at` FROM `updates` WHERE `id` = $updateId");
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
 
        if (0 < count($result)) {
            $rate = static::calculateRankSum($result[0]['upvotes'] - 1, $result[0]['created_at']);
        }

        if (0 >= CW::$app->db->executeUpdate("UPDATE `updates` SET upvotes = upvotes - 1, `rate` = $rate WHERE `id` = $updateId")) {
            return false;
        }

        return true;
    }

    /**
     * 
     * @param type $updateId
     * @param type $userId
     * @param type $activityType
     * @return boolean
     */
    public static function addActivity($updateId, $userId, $activityType) {
        if (!is_numeric($updateId) || !is_numeric($userId) || !is_numeric($activityType)) {
            return false;
        }

        $stmt = CW::$app->db->executeQuery("SELECT type_posted, type_upvoted, type_commented FROM user_update_activity WHERE user_id = $userId AND update_id = $updateId");
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $result = 0 === count($result) ? null : $result[0];

        if (null === $result) {
            // add new
            if (static::ACTIVITY_TYPE_UPVOTE == $activityType) {
                $set = "0,1,0";
            } else if (static::ACTIVITY_TYPE_COMMENT == $activityType) {
                $set = "0,0,1";
            } else if (static::ACTIVITY_TYPE_POST == $activityType) {
                $set = "1,0,0";
            } else {
                return false;
            }

            return CW::$app->db->executeUpdate(
                "INSERT INTO `user_update_activity` (`user_id`, `update_id`, type_posted, type_upvoted, type_commented, time) VALUES ($userId, $updateId, $set, ".time().")"
            );
        } else {
            // update
            if (static::ACTIVITY_TYPE_UPVOTE == $activityType) {
                if (1 == $result['type_upvoted']) {
                    return true;
                }
                $set = "type_upvoted = 1";
            } else if (static::ACTIVITY_TYPE_COMMENT == $activityType) {
                if (1 == $result['type_commented']) {
                    return true;
                }
                $set = "type_commented = 1";
            } else if (static::ACTIVITY_TYPE_POST == $activityType) {
                if (1 == $result['type_posted']) {
                    return true;
                }
                $set = "type_posted = 1";
            } else {
                return false;
            }

            return CW::$app->db->executeUpdate(
                "UPDATE `user_update_activity` SET $set WHERE user_id = $userId AND update_id = $updateId"
            );
        }

        return true;
    }

    /**
     * 
     * @param type $updateId
     * @param type $userId
     * @param type $activityType
     * @return boolean
     */
    public static function removeActivity($updateId, $userId, $activityType) {
        if (static::ACTIVITY_TYPE_UPVOTE == $activityType) {
            $set = "type_upvoted = 0";
        } else if (static::ACTIVITY_TYPE_COMMENT == $activityType) {
            $set = "type_commented = 0";
        } else if (static::ACTIVITY_TYPE_POST == $activityType) {
            $set = "type_posted = 0";
        } else {
            return false;
        }

        if (0 <= CW::$app->db->executeUpdate(
            "UPDATE `user_update_activity` SET $set WHERE `user_id` = $userId AND `update_id` = $updateId"
        )) {
            CW::$app->db->executeUpdate(
                "DELETE FROM `user_update_activity` WHERE `user_id` = $userId AND `update_id` = $updateId AND type_upvoted = 0 AND type_commented = 0 AND type_posted = 0"
            );
            return true;
        }

        return false;
    }

    public static function getUpdateCategories($updateId) {
        if (!is_numeric($updateId) || 0 >= $updateId) {
            return [];
        }

        $stmt = CW::$app->db->executeQuery("SELECT c.`name` FROM `categories` c JOIN `update_categories` uc ON c.`id` = uc.`category_id` WHERE uc.`update_id` = $updateId");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    static function calculateRankSum($score, $created_at) {
//        $s = ($score) / pow(($created_at+2), 1.8);
//        return 0 >= $s ? $created_at : $s;
        $order = log10(max(abs($score), 1));

        if ( $score > 0 ) {
           $sign = 1;
        } elseif ( $score < 0 ) {
           $sign = -1; 
        } else {
           $sign = 0;
        }

        $seconds = intval(($created_at - mktime(0, 0, 0, 1, 1, 1970)) / 8640);

        $long_number = (($order + $sign) == 0 ? 1 :($order + $sign)) * ($seconds);
        
        return round($long_number, 7);
    }

}
