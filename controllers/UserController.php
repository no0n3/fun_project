<?php
namespace controllers;

use CW;
use models\User;
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
        $result = $this->changeSettings(
            \CW::$app->request->get('t')
        );

        CW::$app->db->close();

        return $this->render('edit', $result);
    }

    private function changeSettings($type) {
        if (!User::isValidSettingType($type)) {
            $type = User::SETTINGS_PROFILE;
        }

        if (User::SETTINGS_PROFILE === $type) {
            $result = $this->updateSettings();
        } else if (User::SETTINGS_PASSWORD === $type) {
            $result = $this->updatePassword();
        } else if (User::SETTINGS_PICTURE === $type) {
            $result = $this->updatePicture();
        } else {
            $result = [];
        }

        $result['settingType'] = $type;

        return $result;
    }

    private function updateSettings() {
        $user = \models\User::findUser(CW::$app->user->identity->id);

        $form = new \models\forms\EditProfileForm();
        $form->userCategories = $user->categories;
        $form->userId = $user->id;

        if (empty(CW::$app->request->post())) {
            $form->username = $user->username;
            $form->description = $user->description;
            $success = false;
        } else if ($form->load(CW::$app->request->post()) && $form->save()) {
            $_SESSION['user']->username = $form->username;
            $success = true;
        }

        $categories = \models\Category::getAllCategories();

        return [
            'model'      => $form,
            'success'    => $success,
            'categories' => $categories,
        ];
    }

    private function updatePassword() {
        $model = new \models\forms\ChangePasswordForm();
        $success = false;

        $model->userId = \CW::$app->user->identity->id;

        if ($model->load(CW::$app->request->post()) &&
            $model->save()
        ) {
            $success = true;
        }

        return [
            'model'   => $model,
            'success' => $success
        ];
    }

    private function updatePicture() {
        $model = new \models\forms\ChangeProfilePicture();
        $success = false;

        $model->userId = \CW::$app->user->identity->id;

        if (CW::$app->request->isPost() && $model->load(CW::$app->request->post()) &&
            $model->save()
        ) {
            $success = true;
        }

        return [
            'model'   => $model,
            'success' => $success
        ];
    }

}
