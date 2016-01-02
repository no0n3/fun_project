<?php
namespace models;

use CW;
use components\helpers\ArrayHelper;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class Comment {

    const IMAGE_BIG_WIDTH = 800;
    const IMAGE_MEDIUM_WIDTH = 500;
    const IMAGE_SMALL_WIDTH = 250;

    const COMMENT_LOAD_COUNT = 7;

    public static function getOne($id) {
        if (!is_numeric($id)) {
            return null;
        }

        $stmt = CW::$app->db->prepare("SELECT * FROM `updates` WHERE `id` = $id");

        $updates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return 1 === count($updates) ? $updates[0] : null;
    }

    public static function getUpdateImageUrl($updateId, $size = self::IMAGE_MEDIUM_WIDTH) {
        return '/images/updates/' . $updateId . '/' . $size . 'xX.jpeg';
    }

    public static function getUpdateUrl($updateId) {
        return '/update/view?id=' . $updateId;
    }

    public static function create($content, $updateId, $userId, $replyTo) {
        if (!is_numeric($updateId) || !is_numeric($userId)) {
            return false;
        }

        if (!empty($replyTo)) {
            if (!is_numeric($replyTo)) {
                return false;
            }

            $stmt = CW::$app->db->executeQuery('SELECT `reply_to` FROM `comments` WHERE `update_id` = ' . (int) $updateId);

            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (0 < count($result)) {
                if (null !== $result['reply_to'] && null !== $replyTo) {
                    return false;
                }
            }
        }

        CW::$app->db->beginTransaction();

        if (null !== $replyTo) {
            $query = "INSERT INTO `comments` (`user_id`, `update_id`, `content`, `reply_to`, `posted_on`) VALUES (:user_id, :update_id, :content, :reply_to, :posted_on)";
            $params = [
                ':user_id'    => $userId,
                ':update_id'  => $updateId,
                ':content'    => $content,
                ':reply_to'   =>  $replyTo,
                ':posted_on' => time()
            ];
        } else {
            $query = "INSERT INTO `comments` (`user_id`, `update_id`, `content`, `posted_on`) VALUES (:user_id, :update_id, :content, :posted_on)";
            $params = [
                ':user_id'    => $userId,
                ':update_id'  => $updateId,
                ':content'    => $content,
                ':posted_on' => time()
            ];
        }

        $stmt = CW::$app->db->prepare($query);
        $stmt->execute($params);
        $newCommentId = CW::$app->db->getLastInsertedId();

        Update::addActivity($updateId, $userId, Update::ACTIVITY_TYPE_COMMENT);

        CW::$app->db->executeUpdate("UPDATE `updates` SET `comments` = `comments` + 1 WHERE `id` = $updateId");

        CW::$app->db->commit();

        $comment = new self();
        $comment->id = $newCommentId;
        $comment->content = $content;
        $comment->replyTo = $replyTo;
        $comment->update_id = $updateId;

        return $comment;
    }

    public static function getComments($updateId, $page = null) {
        if (!is_numeric($updateId)) {
            return [];
        }

        $query = "SELECT *, "
                . (CW::$app->user->isLogged() ? ("0 < (SELECT count(*) FROM `comment_upvoters` WHERE `comment_id` = `comments`.`id` AND `user_id` = " . CW::$app->user->identity->id . ") ") : ' false ')
                . " `voted`, null ownerId, null ownerUsername from `comments` WHERE `update_id` = $updateId AND `reply_to` IS NULL ORDER BY `rate` DESC, `posted_on` ASC LIMIT ".self::COMMENT_LOAD_COUNT." OFFSET " . ($page ? ($page * self::COMMENT_LOAD_COUNT) : 0);

        $stmt = \CW::$app->db->executeQuery($query);
        $result = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $commentsCount = count($result);

        $_comments = [];
        $_topLevelComments = [];
        $_replies = [];
 
        for ($i = 0; $i < $commentsCount; $i++) {
            if (!isset($_comments[$result[$i]->user_id])) {
                $_comments[$result[$i]->user_id] = [];
            }

            $_comments[$result[$i]->user_id][] = $result[$i];

            $query = "SELECT *, "
                . (CW::$app->user->isLogged() ? ("0 < (SELECT count(*) FROM `comment_upvoters` WHERE `comment_id` = `comments`.`id` AND `user_id` = " . CW::$app->user->identity->id . ") ") : ' false ')
                . " `voted` from `comments` WHERE `reply_to` = {$result[$i]->id} ORDER BY `posted_on` ASC LIMIT 1";

            $stmt1 = \CW::$app->db->executeQuery($query);

            $_topLevelComments[$result[$i]->id] = $result[$i];

            $replies = $stmt1->fetchAll(\PDO::FETCH_OBJ);
            $result[$i]->repliesCount = count($replies);
            $result[$i]->owner = [
                'id' => $result[$i]->ownerId,
                'username' => $result[$i]->ownerUsername,
                'profileUrl' => \models\User::getProfileUrl($result[$i]->ownerId)
            ];
            $result[$i]->username = htmlspecialchars($result[$i]->username);
            $result[$i]->content = htmlspecialchars($result[$i]->content);
            $result[$i]->postedAgo = BaseModel::getPostedAgoTime($result[$i]->posted_on);
            $result[$i]->voted = (bool) $result[$i]->voted;

            $repliesCount = count($replies);

            for ($j = 0; $j < $repliesCount; $j++) {
                if (!isset($_comments[$replies[$j]->user_id])) {
                    $_comments[$replies[$j]->user_id] = [];
                }

                $_replies[$replies[$j]->reply_to] = $replies[$j]->id;

                $_comments[$replies[$j]->user_id][] = $replies[$j];
                $replies[$j]->username = htmlspecialchars($replies[$j]->username);
                $replies[$j]->content = htmlspecialchars($replies[$j]->content);
                $replies[$j]->postedAgo = BaseModel::getPostedAgoTime($replies[$j]->posted_on);
                $replies[$j]->voted = (bool) $replies[$j]->voted;
            }

            $result[$i]->replies = $replies;
        }

        if (0 < count($_comments)) {
            $userIds = ArrayHelper::keyArray($_comments);
            $query = 'SELECT `id`, `username`, `profile_img_id` FROM `users` WHERE `id` IN (' . ArrayHelper::getArrayToString($userIds, ',') . ')';

            $stmt = \CW::$app->db->executeQuery($query);
            $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $usersCount = count($users);

            for ($i = 0; $i < $usersCount; $i++) {
                $userComments = $_comments[$users[$i]['id']];
                $c = count($userComments);

                for ($j = 0; $j < $c; $j++) {
                    $commentJ = $userComments[$j];
                    $commentJ->owner = [
                        'id' => $users[$i]['id'],
                        'username' => htmlspecialchars($users[$i]['username']),
                        'profileUrl' => \models\User::getProfileUrl($users[$i]['id']),
                        'pictureUrl' => User::getProfilePictureUrl(
                                $users[$i]['profile_img_id'],
                                $users[$i]['id']
                        )
                    ];
                }
            }
        }

        if (0 < count($_replies)) {
            $a = [];

            foreach ($_replies as $replyTo => $replyId) {
                $__replies = $_topLevelComments[$replyTo]->replies;
                $last = $__replies[count($__replies) - 1]->posted_on;

                $a[] = "SELECT `reply_to` FROM `comments` WHERE `reply_to` = $replyTo AND `posted_on` > '$last' LIMIT 1";
            }

            $q = ArrayHelper::getArrayToString($a, ' UNION ', function($v) {
                return "($v)";
            });

            $stmt = CW::$app->db->executeQuery($q);

            $_result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($_result as $replyTo) {
                $_topLevelComments[$replyTo['reply_to']]->hasMore = true;
            }
        }

        if ($commentsCount === self::COMMENT_LOAD_COUNT) {
            $qq = "SELECT `id`, update_id, content FROM `comments` WHERE `update_id` = $updateId AND `reply_to` IS NULL ORDER BY `rate` DESC, `posted_on` DESC LIMIT 10 OFFSET "
                . ($page ? ($page * self::COMMENT_LOAD_COUNT + self::COMMENT_LOAD_COUNT) : self::COMMENT_LOAD_COUNT);
            $stmt = CW::$app->db->executeQuery($qq);

            $_result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $hasMore = 0 < count($_result);
        } else {
            $hasMore = false;
        }

        return [
            'items' => $result,
            'hasMore' => $hasMore
        ];
    }

    public static function getReplies($replyTo, $last = null) {
        if (!is_numeric($replyTo) || !is_numeric($replyTo)) {
            return [];
        }

        $result = [];
        $result['hasMore'] = false;

        $last = $last ? (" AND c.`posted_on` > '" . date("Y-m-d H:i:s", $last) . "'") : '';
        $query = "SELECT c.*, u.id ownerId, u.username ownerUsername, u.profile_img_id FROM `comments` c JOIN `users` u ON c.`user_id` = u.`id` WHERE c.`reply_to` = $replyTo $last ORDER BY c.`posted_on` ASC LIMIT 7";

        $stmt = \CW::$app->db->executeQuery($query);

        $replies = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $repliesCount = count($replies);
        $last = null;

        for ($i = 0; $i < $repliesCount; $i++) {
            $last = $replies[$i]->posted_on;
            $replies[$i]->postedAgo = BaseModel::getPostedAgoTime($replies[$i]->posted_on);
            $replies[$i]->posted_on = strtotime($replies[$i]->posted_on);
            $replies[$i]->owner = [
                'id' => $replies[$i]->ownerId,
                'username' => $replies[$i]->ownerUsername,
                'profileUrl' => \models\User::getProfileUrl($replies[$i]->ownerId),
                'pictureUrl' => User::getProfilePictureUrl(
                        $replies[$i]->profile_img_id,
                        $replies[$i]->ownerId
                )
            ];
        }

        if (0 < $repliesCount) {
            $stmt = CW::$app->db->executeQuery("SELECT `reply_to` FROM `comments` WHERE `reply_to` = $replyTo AND `posted_on` > '"
                . $last . "' LIMIT 1");

            $_result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (0 < count($_result)) {
                $result['hasMore'] = true;
            }
        }

        $result['items'] = $replies;

        return $result;
    }

    public static function upvote($commentId, $userId) {
        if (!is_numeric($commentId) || !is_numeric($userId)) {
            return false;
        }

        $stmt = CW::$app->db->executeQuery("SELECT `reply_to`, `update_id` FROM `comments` WHERE `id` = $commentId");
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (0 < count($result)) {
            $replyTo = $result[0]['reply_to'];
            $updateId = $result[0]['update_id'];

            CW::$app->db->executeUpdate("UPDATE `comments` SET upvotes = upvotes + 1, rate = rate + 1 WHERE `id` = $commentId");
            CW::$app->db->executeUpdate("INSERT INTO `comment_upvoters` (`user_id`, `comment_id`, `voted_at`) VALUES ($userId, $commentId, " . time() . ")");

            if ($replyTo) {
                CW::$app->db->executeUpdate("UPDATE `comments` SET rate = rate + 1 WHERE `id` = $replyTo");
            }

            return true;
        }

        return false;
    }

    public static function unvote($commentId, $userId) {
        if (!is_numeric($commentId) || !is_numeric($userId)) {
            return false;
        }

        $stmt = CW::$app->db->executeQuery("SELECT `reply_to`, `update_id` FROM `comments` WHERE `id` = $commentId");
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (0 < count($result)) {
            $replyTo = $result[0]['reply_to'];
            $updateId = $result[0]['update_id'];

            CW::$app->db->executeUpdate("UPDATE `comments` SET upvotes = upvotes - 1, rate = rate - 1 WHERE `id` = $commentId");
            CW::$app->db->executeUpdate("DELETE FROM `comment_upvoters` WHERE `user_id` = $userId AND `comment_id` = $commentId");

            if ($replyTo) {
                CW::$app->db->executeUpdate("UPDATE `comments` SET rate = rate - 1 WHERE `id` = $replyTo");
            }

            return true;
        }

        return false;
    }
}
