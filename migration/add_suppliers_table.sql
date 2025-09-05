USE `constructor`;

--
-- Структура таблицы `suppliers` (Поставщики)
--
CREATE TABLE `suppliers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `inn` VARCHAR(12) NOT NULL UNIQUE,
  `email` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
