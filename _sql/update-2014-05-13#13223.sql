ALTER TABLE `users` ADD `secondname` VARCHAR( 255 ) NOT NULL AFTER `level`;
ALTER TABLE `users` ADD `lastname` VARCHAR( 255 ) NOT NULL AFTER `name`;
ALTER TABLE `users` CHANGE `name` `firstname` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;