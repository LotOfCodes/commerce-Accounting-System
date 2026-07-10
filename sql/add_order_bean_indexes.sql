-- Speed up bulk order and bean imports.
-- Run this once on an existing database.

SET @has_orders_order_id_idx := (
	SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.STATISTICS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'orders'
	  AND INDEX_NAME = 'idx_orders_order_id'
);
SET @sql_orders_order_id_idx := IF(
	@has_orders_order_id_idx = 0,
	'ALTER TABLE `orders` ADD KEY `idx_orders_order_id` (`orderId`(191))',
	'SELECT 1'
);
PREPARE stmt_orders_order_id_idx FROM @sql_orders_order_id_idx;
EXECUTE stmt_orders_order_id_idx;
DEALLOCATE PREPARE stmt_orders_order_id_idx;

SET @has_orders_delivery_time_idx := (
	SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.STATISTICS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'orders'
	  AND INDEX_NAME = 'idx_orders_delivery_time'
);
SET @sql_orders_delivery_time_idx := IF(
	@has_orders_delivery_time_idx = 0,
	'ALTER TABLE `orders` ADD KEY `idx_orders_delivery_time` (`deliveryTime`)',
	'SELECT 1'
);
PREPARE stmt_orders_delivery_time_idx FROM @sql_orders_delivery_time_idx;
EXECUTE stmt_orders_delivery_time_idx;
DEALLOCATE PREPARE stmt_orders_delivery_time_idx;

SET @has_beans_order_id_idx := (
	SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.STATISTICS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'beans'
	  AND INDEX_NAME = 'idx_beans_order_id'
);
SET @sql_beans_order_id_idx := IF(
	@has_beans_order_id_idx = 0,
	'ALTER TABLE `beans` ADD KEY `idx_beans_order_id` (`orderId`(191))',
	'SELECT 1'
);
PREPARE stmt_beans_order_id_idx FROM @sql_beans_order_id_idx;
EXECUTE stmt_beans_order_id_idx;
DEALLOCATE PREPARE stmt_beans_order_id_idx;

SET @has_beans_parent_order_id_idx := (
	SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.STATISTICS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'beans'
	  AND INDEX_NAME = 'idx_beans_parent_order_id'
);
SET @sql_beans_parent_order_id_idx := IF(
	@has_beans_parent_order_id_idx = 0,
	'ALTER TABLE `beans` ADD KEY `idx_beans_parent_order_id` (`parentOrderId`(191))',
	'SELECT 1'
);
PREPARE stmt_beans_parent_order_id_idx FROM @sql_beans_parent_order_id_idx;
EXECUTE stmt_beans_parent_order_id_idx;
DEALLOCATE PREPARE stmt_beans_parent_order_id_idx;
