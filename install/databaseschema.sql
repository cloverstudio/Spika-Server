DROP TABLE IF EXISTS `emoticon`;
CREATE TABLE IF NOT EXISTS `emoticon` (
  `_id` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) COLLATE utf8_bin NOT NULL,
  `file_id` varchar(255) COLLATE utf8_bin NOT NULL,
  `created` int(11) NOT NULL,
  `modified` int(11) NOT NULL,
  PRIMARY KEY (`_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `password_change_request`;
CREATE TABLE IF NOT EXISTS `password_change_request` (
  `_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) COLLATE utf8_bin NOT NULL,
  `valid` tinyint(1) NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `group`;
CREATE TABLE IF NOT EXISTS `group` (
  `_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `description` text COLLATE utf8_bin NOT NULL,
  `group_password` varchar(255) COLLATE utf8_bin NOT NULL,
  `category_id` int(11) NOT NULL,
  `avatar_file_id` varchar(255) COLLATE utf8_bin NOT NULL,
  `avatar_thumb_file_id` varchar(255) COLLATE utf8_bin NOT NULL,
  `created` int(11) NOT NULL,
  `modified` int(11) NOT NULL,
  PRIMARY KEY (`_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `group_category`;
CREATE TABLE IF NOT EXISTS `group_category` (
  `_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_bin NOT NULL,
  `avatar_file_id` varchar(255) COLLATE utf8_bin NOT NULL,
  `created` int(11) NOT NULL,
  `modified` int(11) NOT NULL,
  PRIMARY KEY (`_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `group_watch_log`;
CREATE TABLE IF NOT EXISTS `group_watch_log` (
  `_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`_id`),
  UNIQUE KEY `group_id` (`group_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `media_comment`;
CREATE TABLE IF NOT EXISTS `media_comment` (
  `_id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `comment` text COLLATE utf8_bin NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`_id`),
  KEY `message_id` (`message_id`,`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `message`;
CREATE TABLE IF NOT EXISTS `message` (
  `_id` int(11) NOT NULL AUTO_INCREMENT,
  `from_user_id` int(11) NOT NULL,
  `to_user_id` int(11) NOT NULL,
  `to_group_id` int(11) NOT NULL,
  `to_group_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `body` text COLLATE utf8_bin NOT NULL,
  `message_target_type` varchar(10) COLLATE utf8_bin NOT NULL,
  `message_type` varchar(10) COLLATE utf8_bin NOT NULL,
  `emoticon_image_url` varchar(255) COLLATE utf8_bin NOT NULL,
  `picture_file_id` varchar(255) COLLATE utf8_bin NOT NULL,
  `picture_thumb_file_id` varchar(255) COLLATE utf8_bin NOT NULL,
  `voice_file_id` varchar(255) COLLATE utf8_bin NOT NULL,
  `video_file_id` varchar(255) COLLATE utf8_bin NOT NULL,
  `longitude` float NOT NULL,
  `latitude` float NOT NULL,
  `valid` int(11) NOT NULL,
  `from_user_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `to_user_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `created` int(11) NOT NULL,
  `modified` int(11) NOT NULL,
  `delete_type` INT NOT NULL DEFAULT '0',
  `delete_at` int(11) NOT NULL DEFAULT '0',
  `delete_flagged_at` int(11) NOT NULL DEFAULT '0',
  `delete_after_shown` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` int(11) NOT NULL DEFAULT '0',  
  `report_count` int(11) NOT NULL DEFAULT '0', 
  `comment_count` int(11) NOT NULL DEFAULT '0', 
  PRIMARY KEY (`_id`),
  KEY `from_user_id` (`from_user_id`,`to_user_id`,`message_target_type`,`message_type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `notification`;
CREATE TABLE IF NOT EXISTS `notification` (
  `_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `from_user_id` int(11) NOT NULL,
  `to_group_id` int(11) NOT NULL COMMENT '1:direct message 2: group message',
  `target_type` varchar(16) COLLATE utf8_bin NOT NULL,
  `message` varchar(255) COLLATE utf8_bin NOT NULL,
  `user_image_url` varchar(255) COLLATE utf8_bin NOT NULL,
  `count` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  `modified` int(11) NOT NULL,
  PRIMARY KEY (`_id`),
  KEY `user_id` (`user_id`,`to_group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `about` text COLLATE utf8_bin NOT NULL,
  `email` varchar(255) COLLATE utf8_bin NOT NULL,
  `password` varchar(255) COLLATE utf8_bin NOT NULL,
  `online_status` varchar(16) COLLATE utf8_bin NOT NULL,
  `max_contact_count` int(11) NOT NULL,
  `max_favorite_count` int(11) NOT NULL,
  `token` varchar(255) COLLATE utf8_bin NOT NULL,
  `token_timestamp` int(11) NOT NULL,
  `last_login` int(11) NOT NULL,
  `birthday` int(11) DEFAULT NULL,
  `gender` varchar(128) COLLATE utf8_bin NOT NULL,
  `avatar_file_id` varchar(255) COLLATE utf8_bin NOT NULL,
  `avatar_thumb_file_id` varchar(255) COLLATE utf8_bin NOT NULL,
  `ios_push_token` varchar(255) COLLATE utf8_bin NOT NULL,
  `android_push_token` varchar(255) COLLATE utf8_bin NOT NULL,
  `created` int(11) NOT NULL,
  `modified` int(11) NOT NULL,
  PRIMARY KEY (`_id`),
  KEY `name` (`name`,`email`,`token`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `user_contact`;
CREATE TABLE IF NOT EXISTS `user_contact` (
  `_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `contact_user_id` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`_id`),
  KEY `user_id` (`user_id`,`contact_user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `user_group`;
CREATE TABLE IF NOT EXISTS `user_group` (
  `_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`_id`),
  KEY `user_id` (`user_id`,`group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `news`;
CREATE TABLE IF NOT EXISTS `news` (
  `_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8_bin NOT NULL,
  `content` text COLLATE utf8_bin NOT NULL,
  `story_url` text COLLATE utf8_bin NOT NULL,
  `created` int(11) NOT NULL,
  `modified` int(11) NOT NULL,
  PRIMARY KEY (`_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `news_comment`;
CREATE TABLE IF NOT EXISTS `news_comment` (
  `_id` int(11) NOT NULL AUTO_INCREMENT,
  `story_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `comment` text COLLATE utf8_bin NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`_id`),
  KEY `story_id` (`story_id`,`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `servers`;
CREATE TABLE IF NOT EXISTS `servers` (
  `_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `url` text COLLATE utf8_bin NOT NULL,
  `created` int(11) NOT NULL,
  `modified` int(11) NOT NULL,
  PRIMARY KEY (`_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

