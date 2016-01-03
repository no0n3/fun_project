<?php

use components\web\Request;
use components\web\Response;
use components\Security;
use components\exceptions\ForbiddenException;
use components\exceptions\WrongMethodException;
use components\exceptions\ErrorException;
use components\exceptions\NotFoundException;
use components\web\Controller;

class App extends \classes\Object {

    const DEFAULT_CONTROLLER = 'site';

    private static $inst;
    private $db;
    private $user;
    private $request;
    private $response;
    public static $authManager;
    private $config;
    public $params;
    private $isConsoleApp;

    private $controllerInst;

    private $components;

    public function __get($name) {
        static $comps = [];

        if ($this->hasProperty($name)) {
            if ('db' === $name) {
                if (null === $this->$name) {
                    $this->$name = new \components\db\DBConnection();
                }
            }

            return $this->$name;
        } else if (isset($this->components[$name])) {
            if (!isset($comps[$name])) {
                $comps[$name] = new $this->components[$name]['class']();
            }

            return $comps[$name];
        }

        return null;
    }

    public function __set($name, $value) {
        if ('db' === $name && null === $value) {
            if ($this->db !== null) {
                $this->db->close();
            }
        }
    }

    public static function getInst() {
        if (null === self::$inst) {
            self::$inst = new self();
        }

        return self::$inst;
    }

    public static function run($config = [], $isConsoleApp = false, $path = '') {
        static $inst = null;

        if (null === $inst) {
            $inst = new self($isConsoleApp);

            \CW::$app = $inst;

            $inst->components = isset($config['components']) ? $config['components'] : [];
            $inst->params = isset($config['params']) ? $config['params'] : [];

            if (!$isConsoleApp) {
                $inst->dispatch($inst->getPath($path));
            }
        }
    }

    private function __construct($isConsoleApp) {
        $this->isConsoleApp = $isConsoleApp;

        if (!$this->isConsoleApp) {
            session_start();
            $this->user = new \components\web\User();
        }

        $this->request = Request::getInstance();
        $this->response = Response::getInstance();

        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            if ('dev' === VI_ENV) {
                if ($this->isConsoleApp) {
                    if (in_array($errno, [E_USER_WARNING, E_WARNING])) {
                        $errType = "WARNING";
                    } else if (in_array($errno, [E_USER_NOTICE, E_NOTICE])) {
                        $errType = "NOTICE";
                    } else {
                        $errType = "ERROR";
                    } 
                    \Console::log("{0}\n",
                        ["$errType:\n$errstr on line $errline in $errfile"],
                        [Console::FG_RED]
                    );
                } else {
                    $msg = <<<HTML
                <div style="width : 100%; border : 2px solid black;">
                    error_no = $errno<br/>
                    error    = $errstr<br/>
                    file     = $errfile<br/>
                    line     = $errline<br/>
                </div>
HTML;
                }

                throw new ErrorException($msg);
            }
        });
        set_exception_handler(function($e) {
            $this->__get('db')->rollback();
            $this->__get('db')->close();

            ob_clean();

            $this->response->setContentType('text/html');

            try {
                if ('prod' !== CW_ENV) {
                    echo '<h1>EXCEPTION:</h1><pre>';
                    var_dump($e);
                    echo '</pre>';

                    echo '<br/><br/><br/><h1>STACK TRACE:</h1><pre>';
                    print_r(debug_backtrace());
                    echo '</pre>';
                } else {
                    if (null === $this->controllerInst) {
                        $this->controllerInst = new \controllers\BaseController();
                    }

                    echo $this->renderView(
                        $this->controllerInst->doError($e),
                        $this->controllerInst->actionId,
                        $this->controllerInst->id
                    );

                    exit;
                }
            } catch (\Exception $e) {
                echo 'An error occurred while processing another error.';
            }
        });
    }

    private function getPath($tp) {
        $a = explode('/', $tp);
        $a1 = [];
        $route = [];

        foreach ($a as $path) {
            if (!empty($path)) {
                $a1[] = $path;
            }
        }

        $c = count($a1);

        if ($c === 0) {
            $route['contr'] = self::DEFAULT_CONTROLLER;
            $route['action'] = Controller::DEFAULT_ACTION;
        } elseif ($c === 1) {
            $route['contr'] = $a1[0];
            $route['action'] = Controller::DEFAULT_ACTION;
        } elseif ($c >= 2) {
            $route['contr'] = $a1[0];
            $route['action'] = $a1[1];
        }

        return $route;
    }

    public function dispatch($route) {
        if (is_string($route)) {
            $route = $this->getPath($route);
        }

        $contrId = $contrName = $route['contr'];
        $contrName[0] = chr(ord($contrName) ^ 32);
        $action = $actionName = $route['action'];
        $actionName[0] = chr(ord($actionName) ^ 32);
        $controllerClass = "controllers\\{$contrName}Controller";

        $classPath = CW::$app->params['sitePath'] . str_replace('\\', '/', $controllerClass) . '.php';

        if (!file_exists($classPath)) {
            throw new NotFoundException();
        }

        $controllerClass = "\\{$controllerClass}";

        $this->controllerInst = new $controllerClass($contrId, $action);

        $actionMethod = "do{$actionName}";

        if (!$this->controllerInst->hasMethod($actionMethod)) {
            throw new NotFoundException();
        }

        $rules = $this->controllerInst->rules();

        $actionRules = isset($rules[$action]) ? $rules[$action] :
            (isset($rules['*']) ? $rules['*'] : null);

        if (null !== $actionRules) {
            if (isset($actionRules['response_type'])) {
                $this->controllerInst->responseType = $actionRules['response_type'];

                $this->response->setContentType($actionRules['response_type']);
            }

            if (isset($actionRules['methods']) &&
                !in_array(strtolower($_SERVER['REQUEST_METHOD']), $actionRules['methods'])
            ) {
                throw new WrongMethodException();
            }

            if (isset($actionRules['roles']) &&
                in_array(Controller::REQUIRED_LOGIN, $actionRules['roles']) &&
                !$this->user->inRole($actionRules['roles'])
            ) {
                if (!$this->request->isAjax()) {
                    $this->controllerInst->forward('site/login');

                    return;
                }

                throw new ForbiddenException();
            }
        }

        $this->controllerInst->beforeAction($action);

        if ($this->controllerInst->hasCsrfValidation &&
            (
                !$this->request->param('_csrf') ||
                !Security::verifyHash($_SESSION['_csrf'], $this->request->param('_csrf'))
            )
        ) {
            throw new ForbiddenException();
        }

        $view = $this->controllerInst->{$actionMethod}();

        $this->renderView($view, $action, $contrName);
    }

    private function renderView($content, $action, $contrName) {
        if ($this->controllerInst->responseType === 'text/html') {
            if ($content) {
                $layout = null === $this->controllerInst->layout ? \components\web\Controller::DEFAULT_LAYOUT : $this->controllerInst->layout;

                echo $this->controllerInst->view->renderView(
                    \CW::$app->params['sitePath'] . "/views/layouts/$layout.php",
                    [
                        'content' => $content,
                        'action' => $action,
                        'controller' => $contrName,
                        'view' => $this->controllerInst->view
                    ]
                );
            }
        } else {
            echo $content;
        }
    }

}
