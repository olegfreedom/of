DROP TABLE IF EXISTS `forum_question_comment`;
DROP TABLE IF EXISTS `forum_question`;
DROP TABLE IF EXISTS `forum_theme`;
DROP TABLE IF EXISTS `forum_group`;
DROP TABLE IF EXISTS `forum_type_organization`;

CREATE TABLE `forum_type_organization`(
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(255),
    PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `forum_type_organization` (`id`, `title`) VALUES
(1, 'Кооператив'),
(2, 'Групи'),
(3, 'Фірми');

CREATE TABLE `forum_group`(
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_user` int(10) unsigned NOT NULL,
    `type_organization_id` int(10) unsigned NOT NULL,
    `title` varchar(255),
    `description` TEXT,
    `active` TINYINT NOT NULL,
    `avaliable` TINYINT NOT NULL,
    `creation`  TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
    `avatar`    TEXT,
    PRIMARY KEY (`id`),
    KEY `id_user` (`id_user`),
    KEY `type_organization_id` (`type_organization_id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `forum_theme`(
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_forum_group` int(10) unsigned NOT NULL,
    `id_user` int(10) unsigned NOT NULL,
    `title` varchar(255),
    `description` TEXT,
    `type` TINYINT NOT NULL,
    `status` TINYINT NOT NULL,
    `visibility` TINYINT NOT NULL,
    `begin_date`  TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
    `end_date`  TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
    `creation`  TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `id_user` (`id_user`),
    KEY `id_forum_group` (`id_forum_group`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `forum_question`(
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_user` int(10) unsigned NOT NULL,
    `id_theme` int(10) unsigned NOT NULL,
    `title` varchar(255),
    `description` TEXT,
    `status` TINYINT NOT NULL,
    `rating` TINYINT unsigned NOT NULL,
    `count_user_vote` TINYINT NOT NULL,
    `creation`  TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `id_user` (`id_user`),
    KEY `id_theme` (`id_theme`)

)ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `forum_question_comment`(
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `pid` int(10) UNSIGNED NOT NULL,
    `id_user` int(10) unsigned NOT NULL,
    `id_question` int(10) unsigned NOT NULL,
    `comment` TEXT,
    `vote` TINYINT NOT NULL,
    `creation`  TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `id_user` (`id_user`),
    KEY `id_question` (`id_question`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `forum_question_vote`(
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_user` int(10) unsigned NOT NULL,
    `id_question` int(10) unsigned NOT NULL,
    `vote` TINYINT NOT NULL,
    `creation`  TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `id_user` (`id_user`),
    KEY `id_question` (`id_question`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `forum_group` ADD FOREIGN KEY (`id_user`) REFERENCES `vb_user` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `forum_group` ADD FOREIGN KEY (`type_organization_id`) REFERENCES `forum_type_organization` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `forum_theme` ADD FOREIGN KEY (`id_user`) REFERENCES `vb_user` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `forum_theme` ADD FOREIGN KEY (`id_forum_group`) REFERENCES `forum_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `forum_question` ADD FOREIGN KEY (`id_user`) REFERENCES `vb_user` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `forum_question` ADD FOREIGN KEY (`id_theme`) REFERENCES `forum_theme` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `forum_question_comment` ADD FOREIGN KEY (`id_user`) REFERENCES `vb_user` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `forum_question_comment` ADD FOREIGN KEY (`id_question`) REFERENCES `forum_question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE  `forum_question_vote` ADD FOREIGN KEY (`id_user`) REFERENCES `vb_user` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE  `forum_question_vote` ADD FOREIGN KEY (`id_question`) REFERENCES `forum_question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;