SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `showstaff`
--

-- --------------------------------------------------------

--
-- Структура таблицы `Company`
--

CREATE TABLE `Company` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `extended_name` longtext COLLATE utf8_unicode_ci,
  `inn` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `Duty`
--

CREATE TABLE `Duty` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `OrderItem`
--

CREATE TABLE `OrderItem` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `booking_date` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `amount` double NOT NULL,
  `price` double NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq` (`booking_date`,`company_id`,`restaurant_id`,`product_id`),
  KEY `IDX_73D03BB5979B1AD6` (`company_id`),
  KEY `IDX_73D03BB5B1E7706E` (`restaurant_id`),
  KEY `IDX_73D03BB52ADD6D8C` (`supplier_id`),
  KEY `IDX_73D03BB54584665A` (`product_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `Orders`
--

CREATE TABLE `Orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `booking_date` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `completed` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E283F8D8979B1AD6` (`company_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `Permission`
--

CREATE TABLE `Permission` (
  `user_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`),
  KEY `IDX_AF14917A979B1AD6` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `Product`
--

CREATE TABLE `Product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `unit` int(11) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1CF73D31979B1AD6` (`company_id`),
  KEY `IDX_1CF73D31DCBB0C53` (`unit`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `Restaurant`
--

CREATE TABLE `Restaurant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `director` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_A4C811EF979B1AD6` (`company_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `Supplier`
--

CREATE TABLE `Supplier` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_625C0E28979B1AD6` (`company_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `SupplierProducts`
--

CREATE TABLE `SupplierProducts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `price` double NOT NULL,
  `primary_supplier` tinyint(1) NOT NULL,
  `supplier_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `company_id` int(11) NOT NULL,
  `active` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_sp` (`company_id`,`supplier_id`,`product_id`,`active`),
  KEY `IDX_A8DED6134584665A` (`product_id`),
  KEY `IDX_A8DED6132ADD6D8C` (`supplier_id`),
  KEY `IDX_A8DED613979B1AD6` (`company_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `Unit`
--

CREATE TABLE `Unit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `User`
--

CREATE TABLE `User` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` bigint(20) NOT NULL,
  `salt` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `fullname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `activation_code` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_2DA17977EFF286D2` (`username`),
  UNIQUE KEY `UNIQ_2DA17977E7927C74` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `UserRole`
--

CREATE TABLE `UserRole` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `role` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_D066285257698A6A` (`role`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users_restaurants`
--

CREATE TABLE `users_restaurants` (
  `user_id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`restaurant_id`),
  KEY `IDX_5A364BD6A76ED395` (`user_id`),
  KEY `IDX_5A364BD6B1E7706E` (`restaurant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `user_role`
--

CREATE TABLE `user_role` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `IDX_2DE8C6A3A76ED395` (`user_id`),
  KEY `IDX_2DE8C6A3D60322AC` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `WorkingHours`
--

CREATE TABLE `WorkingHours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `restaurant_id` int(11) DEFAULT NULL,
  `planhours` int(11) NOT NULL,
  `facthours` int(11) NOT NULL,
  `agreed` tinyint(1) DEFAULT NULL,
  `date` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `duty_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_50B58B90979B1AD6` (`company_id`),
  KEY `IDX_50B58B90A76ED395` (`user_id`),
  KEY `IDX_50B58B90B1E7706E` (`restaurant_id`),
  KEY `IDX_50B58B903A1F9EC1` (`duty_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `OrderItem`
--
ALTER TABLE `OrderItem`
  ADD CONSTRAINT `FK_2FB1D4424584665A` FOREIGN KEY (`product_id`) REFERENCES `Product` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_33E85E19B1E7706E` FOREIGN KEY (`restaurant_id`) REFERENCES `Restaurant` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_73D03BB52ADD6D8C` FOREIGN KEY (`supplier_id`) REFERENCES `Supplier` (`id`),
  ADD CONSTRAINT `FK_73D03BB5979B1AD6` FOREIGN KEY (`company_id`) REFERENCES `Company` (`id`);

--
-- Ограничения внешнего ключа таблицы `Orders`
--
ALTER TABLE `Orders`
  ADD CONSTRAINT `FK_E283F8D8979B1AD6` FOREIGN KEY (`company_id`) REFERENCES `Company` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `Permission`
--
ALTER TABLE `Permission`
  ADD CONSTRAINT `FK_AF14917A979B1AD6` FOREIGN KEY (`company_id`) REFERENCES `Company` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_AF14917AA76ED395` FOREIGN KEY (`user_id`) REFERENCES `User` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `Product`
--
ALTER TABLE `Product`
  ADD CONSTRAINT `FK_1CF73D31979B1AD6` FOREIGN KEY (`company_id`) REFERENCES `Company` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_1CF73D31DCBB0C53` FOREIGN KEY (`unit`) REFERENCES `Unit` (`id`);

--
-- Ограничения внешнего ключа таблицы `Restaurant`
--
ALTER TABLE `Restaurant`
  ADD CONSTRAINT `FK_A4C811EF979B1AD6` FOREIGN KEY (`company_id`) REFERENCES `Company` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `Supplier`
--
ALTER TABLE `Supplier`
  ADD CONSTRAINT `FK_625C0E28979B1AD6` FOREIGN KEY (`company_id`) REFERENCES `Company` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `SupplierProducts`
--
ALTER TABLE `SupplierProducts`
  ADD CONSTRAINT `FK_A8DED6132ADD6D8C` FOREIGN KEY (`supplier_id`) REFERENCES `Supplier` (`id`),
  ADD CONSTRAINT `FK_A8DED6134584665A` FOREIGN KEY (`product_id`) REFERENCES `Product` (`id`),
  ADD CONSTRAINT `FK_A8DED613979B1AD6` FOREIGN KEY (`company_id`) REFERENCES `Company` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `users_restaurants`
--
ALTER TABLE `users_restaurants`
  ADD CONSTRAINT `FK_5A364BD6A76ED395` FOREIGN KEY (`user_id`) REFERENCES `Permission` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_5A364BD6B1E7706E` FOREIGN KEY (`restaurant_id`) REFERENCES `Restaurant` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_role`
--
ALTER TABLE `user_role`
  ADD CONSTRAINT `FK_2DE8C6A3A76ED395` FOREIGN KEY (`user_id`) REFERENCES `User` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_2DE8C6A3D60322AC` FOREIGN KEY (`role_id`) REFERENCES `UserRole` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `WorkingHours`
--
ALTER TABLE `WorkingHours`
  ADD CONSTRAINT `FK_50B58B903A1F9EC1` FOREIGN KEY (`duty_id`) REFERENCES `Duty` (`id`),
  ADD CONSTRAINT `FK_50B58B90979B1AD6` FOREIGN KEY (`company_id`) REFERENCES `Company` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_50B58B90A76ED395` FOREIGN KEY (`user_id`) REFERENCES `User` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_50B58B90B1E7706E` FOREIGN KEY (`restaurant_id`) REFERENCES `Restaurant` (`id`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
