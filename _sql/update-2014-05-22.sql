ALTER TABLE `vb_user`
ADD `city_id` int(10) unsigned NULL,
COMMENT='Дополнительное поле для городов';

ALTER TABLE `vb_user`
ADD FOREIGN KEY (`city_id`) REFERENCES `location_city` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `vb_user`
CHANGE `status` `status` enum('y','n') COLLATE 'utf8_general_ci' NOT NULL DEFAULT 'n' AFTER `google`,
COMMENT='';