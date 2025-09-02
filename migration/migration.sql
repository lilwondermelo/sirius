-- Создание и использование базы данных
CREATE DATABASE IF NOT EXISTS `constructor` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `constructor`;

-- Удаление старых таблиц, если они существуют, для идемпотентности скрипта
DROP TABLE IF EXISTS `events_items_prices`;
DROP TABLE IF EXISTS `list_items`;
DROP TABLE IF EXISTS `list_units`;
DROP TABLE IF EXISTS `list_types`;

-- --------------------------------------------------------

--
-- Структура таблицы `list_types` (Категории товаров)
--
CREATE TABLE `list_types` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `list_types` (из .ini файлов)
--
INSERT INTO `list_types` (`id`, `name`) VALUES
(1, 'бытовая техника'),
(2, 'обувь'),
(3, 'одежда'),
(4, 'электроника');

-- --------------------------------------------------------

--
-- Структура таблицы `list_units` (Единицы измерения)
--
CREATE TABLE `list_units` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `list_units`
--
INSERT INTO `list_units` (`id`, `name`) VALUES
(1, 'шт.'),
(2, 'кг.'),
(3, 'пара');

-- --------------------------------------------------------

--
-- Структура таблицы `list_items` (Товары)
--
CREATE TABLE `list_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `type_id` INT NOT NULL,
  `unit_id` INT NOT NULL,
  `timestamp` BIGINT,
  FOREIGN KEY (`type_id`) REFERENCES `list_types`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`unit_id`) REFERENCES `list_units`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `events_items_prices` (История цен)
--
CREATE TABLE `events_items_prices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_id` INT NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`item_id`) REFERENCES `list_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

