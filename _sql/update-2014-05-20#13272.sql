CREATE TABLE `users_key_temp` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `key` varchar(32) NOT NULL,
  `username` char(100) NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) COMMENT='Таблица для временного хранения key' ENGINE='InnoDB' COLLATE 'utf8_general_ci';