<?php
use CW;
use models\Update;
use components\UrlManager;

$categoryName = CW::$app->request->get('category');
$type = CW::$app->request->get('type');

if (
    !Update::isValidType($type) &&
    \components\web\Controller::DEFAULT_ACTION === $action &&
    App::DEFAULT_CONTROLLER === $controller
) {
    $type = Update::TYPE_FRESH;
}

?>
<!DOCTYPE html>
<html>
<head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script src="/js/app.js"></script>
<script src="http://masonry.desandro.com/masonry.pkgd.js"></script>

<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
<link href="/css/app.css" rel="stylesheet" type="text/css">
<link rel="shortcut icon" href="/images/logo.ico">
<script>
function sAjax(ajaxData, hasCsrf) {
    hasCsrf = undefined === hasCsrf ? true : hasCsrf;

    if (hasCsrf) {
        ajaxData.data._csrf = '<?= isset($_SESSION['_csrf']) ? \components\Security::hash($_SESSION['_csrf']) : null ?>';
    }

    return $.ajax(ajaxData);
}

$(function() {
    App.user.csrfToken = '<?= \components\Security::hash($_SESSION['_csrf']) ?>';
    App.user.id = <?= CW::$app->user->isLogged() ? CW::$app->user->identity->id : 'null' ?>;

    var categoriesMenuButton = document.getElementById('categories');
    var categoriesMenu = document.getElementById('categories-menu');

    var userMenu = document.getElementById('user-menu');
    var userImg = document.getElementById('user-menu-toggle');

    categoriesMenuButton.onclick = function(e) {
        if ('hidden' === categoriesMenu.getAttribute('class')) {
            categoriesMenu.setAttribute('class', '');
        } else {
            categoriesMenu.setAttribute('class', 'hidden');
        }

        e.preventDefault();
    };

    if (userImg) {
        userImg.onclick = function(e) {
            if ('hidden' === userMenu.getAttribute('class')) {
                userMenu.setAttribute('class', '');
            } else {
                userMenu.setAttribute('class', 'hidden');
            }

            e.preventDefault();
        };
    }

    $('#logout-btn').on('click', function(e) {
        App.user.logout();
        e.preventDefault();
    });
});
</script>
</head>
<body>
<div class="header-cont">
    <div id="categories-menu" class="hidden">
        <ul class="category-list">
        <?php foreach ($this->categories as $category) : ?>
            <li class="category-item">
                <a href="<?= UrlManager::to(['site/index', 'type' => 'fresh', 'category' => $category['name']]) ?>" class="category-item-link header-menu-link"><?= htmlspecialchars($category['name']) ?></a>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>
    <ul class="ul1">
        <li class="li1 <?= (!$categoryName && $type === Update::TYPE_TRENDING) ? 'li1-selected' : '' ?>">
            <a href="<?= UrlManager::to(['site/index', 'type' => 'trending']) ?>" class="category-type">Trending</a>
        </li>
        <li class="li1 <?= (!$categoryName && $type === Update::TYPE_FRESH) ? 'li1-selected' : '' ?>">
            <a href="<?= UrlManager::to(['site/index', 'type' => 'fresh']) ?>" class="category-type">Fresh</a>
        </li>
        <li class="li1">
            <a id="categories" href="#" class="category-type">Categories</a>
        </li>
    </ul>

    <div class="search-cont">
        <form action="<?= UrlManager::to(['site/search']) ?>">
            <span class="search-cont-1">
                <button tyle="submit" class="search-btn"></button>
                <input name="term" value="<?= CW::$app->request->get('term') ?>" class="search-inp" type="text" placeholder="search for...">
            </span>
        </form>
    </div>

    <?php if (CW::$app->user->isLogged()) : ?>
    <div class="header-user-area-cont">
        <div id="user-menu-toggle">
            <img id="user-img" class="header-user-img" src="<?= CW::$app->user->identity->getProfilePicUrl() ?>" width="30px" height="30">
            <span class="header-username"><?= htmlspecialchars(CW::$app->user->identity->username) ?></span>
        </div>
        <a href="<?= UrlManager::to(['update/create']) ?>" class="header-btn">submit</a>
    </div>

    <div id="user-menu" class="hidden">
        <ul class="category-list">
            <li class="category-item">
                <a href="<?= UrlManager::to(['user/view', 'id' => CW::$app->user->identity->id]) ?>" class="header-user-menu-item header-menu-link">profile</a>
            </li>
            <li class="category-item">
                <a href="<?= UrlManager::to(['user/settings', 't' => \models\User::SETTINGS_PROFILE]) ?>" class="header-user-menu-item header-menu-link">settings</a>
            </li>
            <li class="category-item">
                <a id="logout-btn" class="header-user-menu-item header-menu-link" href="#">logout</a>
            </li>
        </ul>
    </div>
    <?php else : ?>
    <div class="header-user-option-cont">
        <a href="<?= UrlManager::to(['site/signUp']) ?>" class="header-btn"> sign up </a>
        <a href="<?= UrlManager::to(['site/login']) ?>" class="header-btn"> login </a>
    </div>
    <?php endif; ?>
</div>
<div class="page-container">
    <?= $view ?>
</div>
</body>
</html>
