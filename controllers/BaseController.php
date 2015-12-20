<?php
namespace controllers;

use CW;
use components\web\Controller;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class BaseController extends Controller {

    private function actionsToExclude() {
        return [
            
        ];
    }

    public function beforeAction($actionId) {
        if (CW::$app->request->isPost() && CW::$app->user->isLogged()) {
            $this->hasCsrfValidation = true;
        }

        if (!in_array("$this->id/$actionId", $this->actionsToExclude())) {
            $stmt = CW::$app->db->executeQuery('SELECT `name` FROM `categories` ORDER BY `position`');

            $this->view->categories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->view->categories = [];
        }

        return true;
    }

}
