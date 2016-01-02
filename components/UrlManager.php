<?php
namespace components;

class UrlManager {

    private static $urls = [
        '/profile/view.php' => [
            'user/view',
        ],
        '/update/view.php' => [
            'update/view',
        ],
        '/update/create.php' => [
            'update/create',
        ],
        '/login.php' => [
            'site/login',
        ],
        '/logout.php' => [
            'site/logout',
        ],
        '/sign-up.php' => [
            'site/signUp',
        ],
        '/index.php' => [
            'site/index',
        ],
        '/search.php' => [
            'site/search',
        ],
        '/profile/settings.php' => [
            'user/settings',
        ],
        '/ajax/search.php' => [
            'site/ajaxSearch',
        ],
        '/ajax/update-load.php' => [
            'update/ajaxLoad',
        ],
        '/ajax/user-activity.php' => [
            'update/ajaxUserUpdates',
        ],
        '/ajax/update-upvote.php' => [
            'update/upvote',
        ],
        '/ajax/update-unvote.php' => [
            'update/unvote',
        ],
    ];

    public static function to($a) {
        $_url  = null;
        $_opts = [];
        $_a    = [];

        foreach (static::$urls as $url => $opts) {
            if ($a[0] === $opts[0]) {
                $_a = $a;
                $_opts = $opts;
                unset($_a[0]);
                $match = true;

                foreach ($opts as $k => $v) {
                    if (0 !== $k) {
                        if (!isset($a[$k]) || !preg_match($v, $a[$k])) {
                            $match = false;
                            break;
                        }
                    }
                }

                if ($match) {
                    $_url = $url;

                    foreach ($_a as $k => $v) {
                        $replaced[] = $k;
                        $_url = str_replace('{' . $k . '}', $v, $_url);
                    }

                    break;
                }
            }
        }

        foreach ($_a as $key => $val) {
            if (isset($_opts[$key])) {
                unset($_a[$key]);
            }
        }

        return null !== $_url ?
            ($_url . (0 < count($_a) ? ('?' . http_build_query($_a)) : ''))
            : null;
    }
}
