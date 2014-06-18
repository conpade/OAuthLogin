ALTER TABLE `oauth_user`
ADD COLUMN `source_user_name`  varchar(64) NOT NULL AFTER `created_time`,
ADD COLUMN `initialized`  enum('true','false') NOT NULL DEFAULT 'false' AFTER `source_user_name`;