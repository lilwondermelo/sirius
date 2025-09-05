USE `constructor`;

ALTER TABLE `list_items`
ADD COLUMN `supplier_id` INT NULL,
ADD COLUMN `supplier_code` VARCHAR(255) NULL,
ADD COLUMN `supplier_product_name` VARCHAR(255) NULL,
ADD FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL;
