-- phpMyAdmin SQL Dump
-- version 3.4.5
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Окт 12 2012 г., 10:55
-- Версия сервера: 5.5.24
-- Версия PHP: 5.3.10-1ubuntu3.4

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
-- Структура таблицы `UserRole`
--

CREATE TABLE IF NOT EXISTS `UserRole` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `role` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_D066285257698A6A` (`role`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=6 ;

--
-- Дамп данных таблицы `UserRole`
--

INSERT INTO `UserRole` (`id`, `name`, `role`) VALUES
(1, 'Администратор сервиса', 'ROLE_SUPER_ADMIN'),
(2, 'Администратор компании', 'ROLE_COMPANY_ADMIN'),
(3, 'Менеджер ресторана', 'ROLE_RESTAURANT_ADMIN'),
(4, 'Менеджер по закупкам', 'ROLE_ORDER_MANAGER'),
(5, 'Управляющий', 'ROLE_MANAGER');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
