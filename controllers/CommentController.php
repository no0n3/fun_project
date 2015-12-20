<?php
namespace controllers;

use CW;
use components\web\Controller;
use components\helpers\ImageHelper;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class CommentController extends Controller {


    public function rules() {
        $rules = parent::rules();

        $rules[Controller::ALL] = [
                'response_type' => 'application/json',
                'roles' => [Controller::REQUIRED_LOGIN],
                'methods' => ['post']
            ];
        $rules['load'] = [
                'response_type' => 'application/json',
                'roles' => [Controller::ALL],
                'methods' => ['get']
            ];
        $rules['loadReplies'] = [
            'response_type' => 'application/json',
            'roles' => [Controller::ALL],
            'methods' => ['get']
        ];
        $rules['create'] = [
            'response_type' => 'application/json',
            'roles' => [Controller::REQUIRED_LOGIN],
            'methods' => ['post']
        ];
        $rules['upvote'] = [
            'response_type' => 'application/json',
            'roles' => [Controller::REQUIRED_LOGIN],
            'methods' => ['post']
        ];
        $rules['unvote'] = [
            'response_type' => 'application/json',
            'roles' => [Controller::REQUIRED_LOGIN],
            'methods' => ['post']
        ];

        return $rules;
    }

    public function doCreate() {
        $content = CW::$app->request->post('content');
        $updateId = CW::$app->request->post('updateId');

        if (empty($content) || empty($updateId)) {
            return false;
        }

        $replyTo = CW::$app->request->post('replyTo');

        $result = \models\Comment::create(
            $content,
            $updateId,
            CW::$app->user->identity->id,
            0 === $replyTo ? null : $replyTo
        );

        if ($result) {
            $result->owner = [
                'id' => CW::$app->user->identity->id,
                'username' => CW::$app->user->identity->username,
                'profileUrl' => \models\User::getProfileUrl(CW::$app->user->identity->id),
                'pictureUrl' => CW::$app->user->identity->getProfilePicUrl()
            ];
            $result->postedAgo = \models\BaseModel::getPostedAgoTime(date("Y-m-d H:i:s", time()));
            $result->upvotes = 0;
            $result->replies = $replyTo ? false : [];
            $result->voted = false;
            $result->content = htmlspecialchars($result->content);
        }

        return json_encode($result);
    }

    public function doUpvote() {
        CW::$app->db->beginTransaction();

        $result = \models\Comment::upvote(
            CW::$app->request->post('id'),
            CW::$app->user->identity->id
        );

        if ($result) {
            CW::$app->db->commit();
        } else {
            CW::$app->db->rollback();
        }

        CW::$app->db->close();

        return json_encode($result);
    }

    public function doUnvote() {
        CW::$app->db->beginTransaction();

        $result = \models\Comment::unvote(
            CW::$app->request->post('id'),
            CW::$app->user->identity->id
        );

        if ($result) {
            CW::$app->db->commit();
        } else {
            CW::$app->db->rollback();
        }

        CW::$app->db->close();

        return json_encode($result);
    }

    public function doLoad() {
        $result = \models\Comment::getComments(
            CW::$app->request->get('updateId'),
            CW::$app->request->get('page')
        );

        CW::$app->db->close();

        if (null === $result) {
            throw new \components\exceptions\BadRequestException();
        }

        return json_encode($result);
    }

    public function doLoadReplies() {
        $result = \models\Comment::getReplies(
            CW::$app->request->get('replyTo'),
            CW::$app->request->get('last')
        );

        CW::$app->db->close();

        if (null === $result) {
            throw new \components\exceptions\BadRequestException();
        }

        return json_encode($result);
    }

}
