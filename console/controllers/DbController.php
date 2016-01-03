<?php

namespace console\controllers;

class DbController {

    public function actionInit() {
        $tableOpts = 'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';

        \CW::$app->db->executeUpdate(<<<QUERY
            CREATE TABLE IF NOT EXISTS `categories` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
            `created_at` INT NOT NULL,
            `position` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`)
          ) $tableOpts
QUERY
        );

        $categories = [
            'satisfying',
            'calming',
            'girls',
            'funny',
            'motivationg',
            'inspiring',
            'meme',
            'geeky',
            'animals',
            'food',
            'technology',
            'travel',
            'sports',
            'vehicles',
            'people',
        ];

        $pos = 1;

        $insertQuery = sprintf(
            "INSERT INTO `categories` (`name`, `position`, `created_at`) VALUES %s",
            \components\helpers\ArrayHelper::getArrayToString($categories, ', ', function($v) use (&$pos) {
                return sprintf("('%s', %d, %d)", $v, $pos++, time());
            })
        );

        \CW::$app->db->executeUpdate($insertQuery);

        \CW::$app->db->executeUpdate(<<<QUERY
            CREATE TABLE IF NOT EXISTS `users` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
            `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
            `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
            `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
            `has_profile_pic` bit(1) DEFAULT b'0',
            `profile_img_id` bigint(20) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`),
            UNIQUE KEY `email` (`email`)
          ) $tableOpts
QUERY
        );

        \CW::$app->db->executeUpdate(<<<QUERY
            CREATE TABLE IF NOT EXISTS `updates` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `user_id` bigint(20) NOT NULL,
            `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
            `comments` int(11) NOT NULL DEFAULT '0',
            `upvotes` int(11) NOT NULL DEFAULT '0',
            `rate` double NOT NULL DEFAULT '0',
            `is_gif` BIT DEFAULT 0,
            `created_at` INT NOT NULL,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            CONSTRAINT `updates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
          ) $tableOpts
QUERY
        );

        \CW::$app->db->executeUpdate(<<<QUERY
            CREATE TABLE IF NOT EXISTS `comments` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `update_id` bigint(20) NOT NULL,
            `reply_to` bigint(20) DEFAULT NULL,
            `content` text COLLATE utf8_unicode_ci NOT NULL,
            `upvotes` int(11) NOT NULL DEFAULT '0',
            `rate` int(11) NOT NULL DEFAULT '0',
            `posted_on` INT NOT NULL,
            `user_id` bigint(20) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `update_id` (`update_id`),
            KEY `reply_to` (`reply_to`),
            CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`update_id`) REFERENCES `updates` (`id`),
            CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`reply_to`) REFERENCES `comments` (`id`)
          ) $tableOpts
QUERY
        );
        
        \CW::$app->db->executeUpdate(<<<QUERY
            CREATE TABLE IF NOT EXISTS `comment_upvoters` (
            `user_id` bigint(20) NOT NULL,
            `comment_id` bigint(20) NOT NULL,
            `voted_at` INT NOT NULL,
            UNIQUE KEY `user_id` (`user_id`,`comment_id`)
          ) $tableOpts
QUERY
        );

        \CW::$app->db->executeUpdate(<<<QUERY
            CREATE TABLE IF NOT EXISTS `images` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `rel_id` bigint(20) NOT NULL,
            `rel_type` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
            `image_type` tinyint(4) NOT NULL,
            `type` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
            `is_deleted` bit(1) NOT NULL DEFAULT b'0',
            `created_at` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `ix-rel_id-rel_type` (`rel_id`,`rel_type`)
          ) $tableOpts
QUERY
        );

        \CW::$app->db->executeUpdate(<<<QUERY
            CREATE TABLE IF NOT EXISTS `remember_user` (
            `user_id` bigint(20) NOT NULL,
            `uuid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
            KEY `user_id` (`user_id`),
            CONSTRAINT `remember_user_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
          ) $tableOpts
QUERY
        );

        \CW::$app->db->executeUpdate(<<<QUERY
            CREATE TABLE IF NOT EXISTS `tags` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
            `is_deleted` bit(1) NOT NULL DEFAULT b'0',
            `created_at` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`)
          ) $tableOpts
QUERY
        );
        
        \CW::$app->db->executeUpdate(<<<QUERY
            CREATE TABLE IF NOT EXISTS `update_categories` (
            `update_id` bigint(20) NOT NULL,
            `category_id` bigint(20) NOT NULL,
            UNIQUE KEY `update_id` (`update_id`,`category_id`),
            KEY `category_id` (`category_id`),
            CONSTRAINT `update_categories_ibfk_1` FOREIGN KEY (`update_id`) REFERENCES `updates` (`id`),
            CONSTRAINT `update_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
          ) $tableOpts
QUERY
        );
        
        \CW::$app->db->executeUpdate(<<<QUERY
            CREATE TABLE IF NOT EXISTS `update_tags` (
            `update_id` bigint(20) NOT NULL,
            `tag_id` bigint(20) NOT NULL,
            UNIQUE KEY `update_id` (`update_id`,`tag_id`),
            KEY `tag_id` (`tag_id`),
            CONSTRAINT `update_tags_ibfk_1` FOREIGN KEY (`update_id`) REFERENCES `updates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `update_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE

          ) $tableOpts
QUERY
        );
        
        \CW::$app->db->executeUpdate(<<<QUERY
            CREATE TABLE IF NOT EXISTS `update_upvoters` (
            `user_id` bigint(20) NOT NULL,
            `update_id` bigint(20) NOT NULL,
            `voted_at` INT NOT NULL,
            UNIQUE KEY `user_id` (`user_id`,`update_id`),
            KEY `update_id` (`update_id`),
            CONSTRAINT `update_upvoters_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `update_upvoters_ibfk_2` FOREIGN KEY (`update_id`) REFERENCES `updates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
          ) $tableOpts
QUERY
        );
        
        \CW::$app->db->executeUpdate(<<<QUERY
            CREATE TABLE IF NOT EXISTS `user_categories` (
            `user_id` bigint(20) NOT NULL,
            `category_id` bigint(20) NOT NULL,
            UNIQUE KEY `user_id` (`user_id`,`category_id`),
            KEY `category_id` (`category_id`),
            CONSTRAINT `user_categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
            CONSTRAINT `user_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
          ) $tableOpts
QUERY
        );
        
        \CW::$app->db->executeUpdate(<<<QUERY
            CREATE TABLE IF NOT EXISTS `user_update_activity` (
            `user_id` bigint(20) NOT NULL,
            `update_id` bigint(20) NOT NULL,
            `time` INT NOT NULL,
            `type_posted` bit(1) NOT NULL DEFAULT b'0',
            `type_upvoted` bit(1) NOT NULL DEFAULT b'0',
            `type_commented` bit(1) NOT NULL DEFAULT b'0',
            UNIQUE KEY `user_id` (`user_id`,`update_id`),
            KEY `update_id` (`update_id`),
            CONSTRAINT `user_update_activity_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `user_update_activity_ibfk_2` FOREIGN KEY (`update_id`) REFERENCES `updates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
          ) $tableOpts
QUERY
        );

    }

}
