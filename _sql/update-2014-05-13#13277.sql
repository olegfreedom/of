ALTER TABLE `adverts` ADD FOREIGN KEY (`location`) REFERENCES `location_city` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;