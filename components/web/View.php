<?php
namespace components\web;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class View extends \classes\Objectv {
    protected $controller;

    public $title;
    private $metaTags = [];
    private $links    = [];

    public function registerMetaTag($meta) {
        if (!is_array($meta)) {
            return false;
        }

        $this->metaTags[] = $meta;
    }

    public function getMetaTags() {
        return $this->metaTags;
    }

    public function registerLink($link) {
        if (!is_array($link)) {
            return false;
        }

        $this->links[] = $link;
    }

    public function getLinks() {
        return $this->links;
    }

    public function __construct($controller) {
        $this->controller = $controller;
    }

    public function renderFile($path) {
        include $path;
    }

    public function render($view, $vars = []) {
        return $this->renderView(
            sprintf(
                "%s/views/%s/%s.php",
                \CW::$app->params['sitePath'],
                $this->controller->id,
                $view
            ),
            $vars
        );
    }

    public function renderView($path, $vars = []) {
        ob_start();
        ob_implicit_flush(false);
        extract($vars, EXTR_OVERWRITE);

        include $path;

        return ob_get_clean();
    }

}
