
CREATE TABLE IF NOT EXISTS `misc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `s_key` varchar(255) NOT NULL,
  `s_value` int(11) NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_provider` int(11) NOT NULL COMMENT '1:APN Production 2:APN Development 2:GCM',
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `payload` text COLLATE utf8_unicode_ci NOT NULL,
  `state` int(11) NOT NULL COMMENT '1:Waiting 2:Processing 3:Sent 4:Error',
  `queued` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `sent` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `result_from_service_provider` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_state` (`state`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `queue_state_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `capture_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notifications_queued` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
