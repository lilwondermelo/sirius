-- SQL-скрипт для заполнения базы данных тестовыми данными

USE `constructor`;

-- Отключаем проверку внешних ключей, чтобы можно было очистить таблицы
SET FOREIGN_KEY_CHECKS=0;

-- Очистим таблицы с товарами и ценами, используя TRUNCATE.
-- Порядок не важен при отключенных ключах.
TRUNCATE TABLE `list_items`;
TRUNCATE TABLE `events_items_prices`;

-- Включаем проверку внешних ключей обратно
SET FOREIGN_KEY_CHECKS=1;

-- --- Электроника (type_id = 4) ---

-- Товар 1
INSERT INTO `list_items` (`name`, `type_id`, `unit_id`, `timestamp`) VALUES ('Смартфон "Фотон-М" 128Гб', 4, 1, UNIX_TIMESTAMP());
SET @last_item_id = LAST_INSERT_ID();
INSERT INTO `events_items_prices` (`item_id`, `price`) VALUES (@last_item_id, 25990.00);

-- Товар 2
INSERT INTO `list_items` (`name`, `type_id`, `unit_id`, `timestamp`) VALUES ('Беспроводные наушники "Аурум-2"', 4, 1, UNIX_TIMESTAMP());
SET @last_item_id = LAST_INSERT_ID();
INSERT INTO `events_items_prices` (`item_id`, `price`) VALUES (@last_item_id, 7490.50);

-- Товар 3
INSERT INTO `list_items` (`name`, `type_id`, `unit_id`, `timestamp`) VALUES ('Ноутбук "Орион-Про" 15.6"', 4, 1, UNIX_TIMESTAMP());
SET @last_item_id = LAST_INSERT_ID();
INSERT INTO `events_items_prices` (`item_id`, `price`) VALUES (@last_item_id, 89900.00);

-- Товар 4
INSERT INTO `list_items` (`name`, `type_id`, `unit_id`, `timestamp`) VALUES ('Умные часы "Квазар-5"', 4, 1, UNIX_TIMESTAMP());
SET @last_item_id = LAST_INSERT_ID();
INSERT INTO `events_items_prices` (`item_id`, `price`) VALUES (@last_item_id, 18500.00);

-- Товар 5
INSERT INTO `list_items` (`name`, `type_id`, `unit_id`, `timestamp`) VALUES ('Планшет "Вега-10" 10.1"', 4, 1, UNIX_TIMESTAMP());
SET @last_item_id = LAST_INSERT_ID();
INSERT INTO `events_items_prices` (`item_id`, `price`) VALUES (@last_item_id, 32000.00);

-- --- Одежда (type_id = 3) ---

-- Товар 6
INSERT INTO `list_items` (`name`, `type_id`, `unit_id`, `timestamp`) VALUES ('Футболка "Космос-Принт" (L)', 3, 1, UNIX_TIMESTAMP());
SET @last_item_id = LAST_INSERT_ID();
INSERT INTO `events_items_prices` (`item_id`, `price`) VALUES (@last_item_id, 2499.99);

-- Товар 7
INSERT INTO `list_items` (`name`, `type_id`, `unit_id`, `timestamp`) VALUES ('Джинсы "Туманность" (синие, W32/L34)', 3, 1, UNIX_TIMESTAMP());
SET @last_item_id = LAST_INSERT_ID();
INSERT INTO `events_items_prices` (`item_id`, `price`) VALUES (@last_item_id, 5800.00);

-- Товар 8
INSERT INTO `list_items` (`name`, `type_id`, `unit_id`, `timestamp`) VALUES ('Худи "Черная дыра" (XL)', 3, 1, UNIX_TIMESTAMP());
SET @last_item_id = LAST_INSERT_ID();
INSERT INTO `events_items_prices` (`item_id`, `price`) VALUES (@last_item_id, 7990.00);

-- Товар 9
INSERT INTO `list_items` (`name`, `type_id`, `unit_id`, `timestamp`) VALUES ('Кроссовки "Экзопланета" (43 размер)', 3, 1, UNIX_TIMESTAMP());
SET @last_item_id = LAST_INSERT_ID();
INSERT INTO `events_items_prices` (`item_id`, `price`) VALUES (@last_item_id, 9200.00);

-- Товар 10
INSERT INTO `list_items` (`name`, `type_id`, `unit_id`, `timestamp`) VALUES ('Бейсболка "Метеор"', 3, 1, UNIX_TIMESTAMP());
SET @last_item_id = LAST_INSERT_ID();
INSERT INTO `events_items_prices` (`item_id`, `price`) VALUES (@last_item_id, 1800.00);

