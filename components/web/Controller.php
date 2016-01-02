<?php
namespace components\web;

use components\exceptions\ForbiddenException;
use components\exceptions\WrongMethodException;
use components\exceptions\BadRequestException;
use components\exceptions\NotFoundException;
use CW;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
abstract class Controller extends \classes\Object {

    const DEFAULT_ACTION = 'index';
    const DEFAULT_LAYOUT = 'main';

    const REQUIRED_LOGIN = '!';
    const ALL = '*';
    const NOT_LOGGED = '@';

    public $layout = self::DEFAULT_LAYOUT;
    public $hasCsrfValidation = false;

    public $view;
    public $actionId;
    public $id;

    public function doNotFoundError() {
        return $this->doError(new NotFoundException());
    }

    public function doError($exception = null) {
        function setError($code, $msg) {
            http_response_code($code);

            return sprintf(
                "%s%s",
                is_numeric($code) ? "<span style=\"color: red;\">$code</span> " : '',
                $msg
            );
        }

        function f($e) {
            if ($e instanceof ForbiddenException) {
                return setError(403, 'FORBIDDEN');
            } else if ($e instanceof WrongMethodException) {
                return setError(405, 'WRONG METHOD');
            } else if ($e instanceof BadRequestException) {
                return setError(400, 'BAD REQUEST');
            } else if ($e instanceof NotFoundException) {
                return setError(404, 'NOT FOUND');
            } else {
                return setError(500, 'INTERNAL SERVER ERROR');
            }
        }

        return '<div style="text-align: center; padding-top: 20px;">' . f($exception) . '</div>';
    }

    /**
     * The action response type.
     * @var string
     */
    public $responseType = 'text/html';

    function __construct($id, $actionId) {
        $this->view = new \components\web\View($this);
        $this->id = $id;
        $this->actionId = $actionId;
    }

    public function rules() {
        return [
            Controller::ALL => [
                'response_type' => 'text/html',
            ],
            'error' => [
                'response_type' => 'text/html'
            ],
            'notFoundError' => [
                'response_type' => 'text/html'
            ],
        ];
    }

    public function beforeAction($actionId) {
        return true;
    }

    protected function render($viewName, $vars = []) {
        return $this->view->render(
            $viewName,
            $vars
        );
    }

    public final function forward($path) {
        ob_clean();

        \CW::$app->dispatch($path);
    }

    protected function redirect($path) {
        header(
            sprintf(
                "Location: /%s",
                1 === count( explode('/', $path) ) ?
                   "{$this->id}/$path" :
                   $path
            )
        );
    }

}
