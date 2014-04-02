CREATE TABLE IF NOT EXISTS `oauth_user` (
  `user_id` int(10) unsigned NOT NULL,
  `open_id` varchar(128) NOT NULL,
  `source` enum('qq','weibo') NOT NULL,
  `created_time` datetime NOT NULL,
  UNIQUE KEY `source_user_id` (`open_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;