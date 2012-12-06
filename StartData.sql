--
-- База данных: `showstaff`
--

--
-- Дамп данных таблицы `Unit`
--
INSERT INTO `Unit` (`id`, `name`) VALUES
(1, 'кг'),
(2, 'литр'),
(3, 'шт'),
(4, 'пучок'),
(5, 'бутылка');

--
-- Дамп данных таблицы `UserRole`
--
INSERT INTO `UserRole` (`id`, `name`, `role`) VALUES
(1, 'Администратор сервиса', 'ROLE_SUPER_ADMIN'),
(2, 'Администратор компании', 'ROLE_COMPANY_ADMIN'),
(3, 'Менеджер ресторана', 'ROLE_RESTAURANT_ADMIN'),
(4, 'Менеджер по закупкам', 'ROLE_ORDER_MANAGER'),
(5, 'Управляющий', 'ROLE_ADMIN'),
(6, 'Директор ресторана', 'ROLE_RESTAURANT_DIRECTOR'),
(7, 'Пользователь', 'ROLE_USER');
