<?php
namespace controllers;

use CW;
use models\Update;
use components\web\Controller;
use components\helpers\ImageHelper;
use models\Category;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class UpdateController extends BaseController {

    public function rules() {
        return [
            Controller::ALL => [
                'response_type' => 'application/json',
                'roles' => [Controller::REQUIRED_LOGIN],
                'methods' => ['post']
            ],
            'create' => [
                'response_type' => 'text/html',
                'roles' => [Controller::REQUIRED_LOGIN],
            ],
            'upload' => [
                'response_type' => 'text/html',
            ],
            'view' => [
                'response_type' => 'text/html',
                'roles' => [Controller::ALL],
                'methods' => ['get']
            ],
            'ajaxLoad' => [
                'response_type' => 'application/json',
                'roles' => [Controller::ALL],
                'methods' => ['get']
            ],
            'ajaxUserUpdates' => [
                'response_type' => 'application/json',
                'roles' => [Controller::ALL],
                'methods' => ['get']
            ],
        ];
    }

    public function doCreate() {
        $form = new \models\forms\CreateUpdateForm();

        if ($form->load(\CW::$app->request->post()) &&
            $form->validate()
        ) {
            CW::$app->db->beginTransaction();

            if ($form->save()) {
                $success = true;
                CW::$app->db->commit();
            } else {
                CW::$app->db->rollback();
            }

            CW::$app->db->close();
        } else {
            $success = false;
        }

        return $this->render('create', [
            'success' => !empty($success),
            'model' => $form,
            'categories' => \models\Category::getAllCategories()
        ]);
    }

    public function doView() {
        $updateId = CW::$app->request->get('id');
        $type = CW::$app->request->get('type');
        $categoryName = CW::$app->request->get('category');

        $update = \models\Update::getOne(
            $updateId,
            $type,
            $categoryName
        );

        if (null !== $update) {
            $update['imageUrl'] = Update::getUpdateImageUrl($update['id'], Update::IMAGE_BIG_WIDTH);
            $categories = Update::getUpdateCategories($update['id']);
        } else {
            $categories = [];
        }

        CW::$app->db->close();

        return $this->render('view', [
            'update' => $update,
            'categories' => $categories,
            'categoryName' => $categoryName,
            'prevUpdateId' => null === $update ? null : Update::getPrev($updateId, $categoryName),
            'nextUpdateId' => null === $update ? null : Update::getNext($updateId, $categoryName)
        ]);
    }

    public function doUpvote() {
        CW::$app->db->beginTransaction();

        $result = \models\Update::upvote(
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

        $result = \models\Update::unvote(
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

    public function doAjaxUserUpdates() {
        $result = Update::getUserUpdates(
            CW::$app->request->get('userId'),
            CW::$app->request->get('type'),
            CW::$app->request->get('page')
        );

        CW::$app->db->close();

        return json_encode($result);
    }

    public function doAjaxLoad() {
        $result = Update::getUpdates(
            CW::$app->request->get('page'),
            Category::getIdByName(CW::$app->request->get('category')),
            CW::$app->request->get('type'),
            CW::$app->request->get('category')
        );

        CW::$app->db->close();

        return json_encode($result);
    }

}
