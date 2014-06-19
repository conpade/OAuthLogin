ALTER TABLE `oauth_user`
ADD COLUMN `last_updated_time`  datetime NOT NULL AFTER `created_time`;