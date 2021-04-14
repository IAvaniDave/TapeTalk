
ALTER TABLE `users` ADD `birth_date` DATE NULL DEFAULT NULL AFTER `gender`;
ALTER TABLE `chat_groups` CHANGE `group_name` `group_name` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `chat_groups` CHANGE `group_image` `group_image` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

/* 14-04-2021 */
ALTER TABLE `users` CHANGE `is_hi` `is_hi` TINYINT(20) NULL DEFAULT '0' COMMENT ' 0 = no , 1 = yes';
