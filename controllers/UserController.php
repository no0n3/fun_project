<?php
namespace controllers;

use CW;
use models\Update;
use components\web\Controller;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class UserController extends BaseController {

    public function rules() {
        return [
            Controller::ALL => [
                'response_type' => 'application/json',
                'roles' => [Controller::REQUIRED_LOGIN],
                'methods' => ['post']
            ],
            'view' => [
                'response_type' => 'text/html',
                'roles' => [Controller::ALL],
                'methods' => ['get']
            ],
            'settings' => [
                'response_type' => 'text/html',
                'roles' => [Controller::REQUIRED_LOGIN],
            ],
            'changePassword' => [
                'response_type' => 'text/html',
                'roles' => [Controller::REQUIRED_LOGIN],
            ],
            'changePicture' => [
                'response_type' => 'text/html',
                'roles' => [Controller::REQUIRED_LOGIN],
            ]
        ];
    }

    public function doView() {
        $user = \models\User::getOne(
            CW::$app->request->get('id')
        );

        return $this->render('view', [
            'model' => $user
        ]);
    }

    public function doSettings() {
        $user = \models\User::findUser(CW::$app->user->identity->id);

        $form = new \models\forms\EditProfileForm();
        $form->userCategories = $user->categories;
        $form->userId = $user->id;

        if (empty(CW::$app->request->post())) {
            $form->username = $user->username;
            $form->description = $user->description;
        } else if ($form->load(CW::$app->request->post()) && $form->save()) {
            $_SESSION['user']->username = $form->username;
            $success = true;
        }

        $categories = \models\Category::getAllCategories();

        CW::$app->db->close();

        $settingType = \CW::$app->request->param('t');

        return $this->render('edit', [
            'model' => $form,
            'categories' => $categories,
            'settingType' => !in_array($settingType, ['profile', 'password', 'picture']) ? 'profile' : $settingType,
            'success' => isset($success) ? $success : false
        ]);
    }

    public function doChangePicture() {
        $model = new \models\forms\ChangeProfilePicture();

        $model->userId = \CW::$app->user->identity->id;

        if (CW::$app->request->isPost() && $model->load(CW::$app->request->post()) &&
            $model->save()
        ) {
            $success = true;
        }

        return $this->render('edit', [
            'id' => CW::$app->request->get('id'),
            'model' => $model,
            'settingType' => 'picture',
            'success' => !empty($success) ? $success : false
        ]);
    }

    public function doChangePassword() {
        $model = new \models\forms\ChangePasswordForm();

        $model->userId = \CW::$app->user->identity->id;

        if ($model->load(CW::$app->request->post()) &&
            $model->save()
        ) {
            $success = true;
        }

        return $this->render('edit', [
            'id' => CW::$app->request->get('id'),
            'model' => $model,
            'settingType' => 'password',
            'success' => isset($success) ? $success : false
        ]);
    }

}
