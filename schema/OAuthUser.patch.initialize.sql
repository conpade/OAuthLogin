ALTER TABLE `oauth_user`
ADD COLUMN `source_user_name`  varchar(64) NOT NULL AFTER `created_time`,
ADD COLUMN `initialized`  enum('0','1') NOT NULL DEFAULT '0' AFTER `source_user_name`;