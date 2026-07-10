-- ----------------------------
-- Table structure for orders
-- ----------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`customer` varchar(765) DEFAULT NULL,
	`orderId` varchar(765) DEFAULT NULL,
	`platId` varchar(765) DEFAULT NULL,
	`orderType` int(11) DEFAULT NULL,
	`tradeStatus` varchar(765) DEFAULT NULL,
	`shopName` varchar(765) DEFAULT NULL,
	`receiverName` varchar(765) DEFAULT NULL,
	`receiverProvince` varchar(765) DEFAULT NULL,
	`receiverAddress` varchar(765) DEFAULT NULL,
	`receiverMobile` varchar(765) DEFAULT NULL,
	`remark` varchar(765) DEFAULT NULL,
	`waybillNumber` varchar(765) DEFAULT NULL,
	`waybillCom` LONGTEXT DEFAULT NULL,
	`waybillTemplate` varchar(765) DEFAULT NULL,
	`deliveryTime` TIMESTAMP DEFAULT NULL,
	`printTime` TIMESTAMP DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_orders_order_id` (`orderId`(191)),
  KEY `idx_orders_delivery_time` (`deliveryTime`)
)  AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='InnoDB free: 4096 kB';

-- ----------------------------
-- Records of orders
-- ----------------------------

-- ----------------------------
-- Table structure for beans
-- ----------------------------
DROP TABLE IF EXISTS `beans`;
CREATE TABLE `beans` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`customer` varchar(765) DEFAULT NULL,
	`parentOrderId` varchar(765) DEFAULT NULL,
	`orderId` varchar(765) DEFAULT NULL,
	`parentPlatId` varchar(765) DEFAULT NULL,
	`platId` varchar(765) DEFAULT NULL,
	`orderType` varchar(765) DEFAULT NULL,
	`shopName` varchar(765) DEFAULT NULL,
	`tradeStatus` varchar(765) DEFAULT NULL,
	`sku` varchar(765) DEFAULT NULL,
	`picUrl` varchar(765) DEFAULT NULL,
	`total` varchar(765) DEFAULT NULL,
	`weightActual` varchar(765) DEFAULT NULL,
	`deliveryTime` TIMESTAMP DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_beans_order_id` (`orderId`(191)),
  KEY `idx_beans_parent_order_id` (`parentOrderId`(191))
)  AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='InnoDB free: 4096 kB';

-- ----------------------------
-- Records of beans
-- ----------------------------
-- ----------------------------
-- Table structure for products
-- ----------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
  `productName` varchar(765) DEFAULT NULL,
	`merchantIds` varchar(2000) DEFAULT NULL,
	`matchRule` LONGTEXT DEFAULT NULL,
	`price` varchar(765) DEFAULT NULL,
	`mPrice` varchar(765) DEFAULT NULL,
	`remotePrice` varchar(765) DEFAULT NULL,
	`packPrice` varchar(765) DEFAULT NULL,
	`expressPayer` varchar(765) DEFAULT NULL,
	`weight` LONGTEXT DEFAULT NULL,
	`startTime` TIMESTAMP DEFAULT NULL,
	`endTime` TIMESTAMP DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
)  AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='InnoDB free: 4096 kB';

-- ----------------------------
-- Records of products
-- ----------------------------

-- ----------------------------
-- Table structure for expresses
-- ----------------------------
DROP TABLE IF EXISTS `expresses`;
CREATE TABLE `expresses` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`expressName` varchar(765) DEFAULT NULL,
	`templateCode` varchar(765) DEFAULT NULL,
	`regexRules` LONGTEXT DEFAULT NULL,
	`feeRules` LONGTEXT DEFAULT NULL,
	`settleTarget` varchar(765) DEFAULT NULL,
	`freightPayer` varchar(765) DEFAULT NULL,
	`status` varchar(765) DEFAULT '有效',
	`remoteNote` varchar(765) DEFAULT NULL,
	`remark` varchar(765) DEFAULT NULL,
	`createdTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	`updatedTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_expresses_name` (`expressName`(191)),
  KEY `idx_expresses_status` (`status`(191)),
  KEY `idx_expresses_settle` (`settleTarget`(191))
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Express fee templates';

-- ----------------------------
-- Records of expresses
-- ----------------------------
INSERT INTO `expresses` (`expressName`, `templateCode`, `regexRules`, `feeRules`, `settleTarget`, `freightPayer`, `status`, `remoteNote`, `remark`)
VALUES (
	'圆通快递',
	'YT-DEFAULT-2026',
	'圆通|YT|YTO\n圆通速递',
	'[{\"region\":\"华北\",\"provinces\":\"北京、天津、河北、山西、内蒙古\",\"kg1\":\"5.00\",\"kg2\":\"7.00\",\"kg3\":\"9.00\",\"kg4\":\"11.00\",\"renew\":\"2.00\"},{\"region\":\"华东\",\"provinces\":\"上海、江苏、浙江、安徽、福建、江西、山东\",\"kg1\":\"4.00\",\"kg2\":\"6.00\",\"kg3\":\"8.00\",\"kg4\":\"10.00\",\"renew\":\"2.00\"},{\"region\":\"华中\",\"provinces\":\"河南、湖北、湖南\",\"kg1\":\"4.00\",\"kg2\":\"6.00\",\"kg3\":\"9.00\",\"kg4\":\"12.00\",\"renew\":\"2.00\"},{\"region\":\"华南\",\"provinces\":\"广东、广西、海南\",\"kg1\":\"3.00\",\"kg2\":\"5.00\",\"kg3\":\"8.00\",\"kg4\":\"11.00\",\"renew\":\"2.00\"},{\"region\":\"西南\",\"provinces\":\"重庆、四川、贵州、云南、西藏\",\"kg1\":\"6.00\",\"kg2\":\"8.00\",\"kg3\":\"11.00\",\"kg4\":\"14.00\",\"renew\":\"3.00\"},{\"region\":\"西北\",\"provinces\":\"陕西、甘肃、青海、宁夏、新疆\",\"kg1\":\"7.00\",\"kg2\":\"9.00\",\"kg3\":\"12.00\",\"kg4\":\"15.00\",\"renew\":\"3.00\"},{\"region\":\"东北\",\"provinces\":\"辽宁、吉林、黑龙江\",\"kg1\":\"7.00\",\"kg2\":\"9.00\",\"kg3\":\"11.00\",\"kg4\":\"13.00\",\"renew\":\"3.00\"},{\"region\":\"港澳台\",\"provinces\":\"香港、澳门、台湾\",\"kg1\":\"18.00\",\"kg2\":\"28.00\",\"kg3\":\"38.00\",\"kg4\":\"48.00\",\"renew\":\"10.00\"}]',
	'商家',
	'商家承担',
	'有效',
	'偏远费单独核算',
	'默认模板'
);

-- ----------------------------
-- Table structure for tear orders
-- ----------------------------
DROP TABLE IF EXISTS `tear_orders`;
CREATE TABLE `tear_orders` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`expressName` varchar(765) DEFAULT NULL,
	`waybillNumber` varchar(765) NOT NULL,
	`actionType` varchar(32) DEFAULT '撕单',
	`actionTime` TIMESTAMP NULL DEFAULT NULL,
	`remark` varchar(765) DEFAULT NULL,
	`createdTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	`updatedTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_tear_orders_waybill` (`waybillNumber`(191)),
  KEY `idx_tear_orders_express` (`expressName`(191)),
  KEY `idx_tear_orders_action` (`actionType`),
  KEY `idx_tear_orders_time` (`actionTime`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Tear/intercept waybill records';

-- ----------------------------
-- Records of tear_orders
-- ----------------------------

-- ----------------------------
-- Table structure for express audit
-- ----------------------------
DROP TABLE IF EXISTS `express_audit_items`;
DROP TABLE IF EXISTS `express_audit_tasks`;
CREATE TABLE `express_audit_tasks` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`taskDate` date DEFAULT NULL,
	`expressCompany` varchar(765) DEFAULT NULL,
	`extraFee` decimal(12,2) DEFAULT 0.00,
	`totalCount` int(11) DEFAULT 0,
	`checkedCount` int(11) DEFAULT 0,
	`suspiciousCount` int(11) DEFAULT 0,
	`redCount` int(11) DEFAULT 0,
	`yellowCount` int(11) DEFAULT 0,
	`greenCount` int(11) DEFAULT 0,
	`deviationPercent` decimal(10,2) DEFAULT 0.00,
	`createdTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	`updatedTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_express_audit_task_date` (`taskDate`),
  KEY `idx_express_audit_company` (`expressCompany`(191))
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Express bill audit tasks';

CREATE TABLE `express_audit_items` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`taskId` int(11) NOT NULL,
	`waybillNumber` varchar(765) DEFAULT NULL,
	`billDate` TIMESTAMP NULL DEFAULT NULL,
	`weight` decimal(12,3) DEFAULT 0.000,
	`actualFee` decimal(12,2) DEFAULT 0.00,
	`province` varchar(765) DEFAULT NULL,
	`orderId` varchar(765) DEFAULT NULL,
	`orderWeight` decimal(12,3) DEFAULT 0.000,
	`expectedFee` decimal(12,2) DEFAULT 0.00,
	`deviationPercent` decimal(10,2) DEFAULT 0.00,
	`markStatus` varchar(32) DEFAULT NULL,
	`manualStatus` varchar(32) DEFAULT NULL,
	`reason` varchar(765) DEFAULT NULL,
	`remark` varchar(765) DEFAULT NULL,
	`rawData` LONGTEXT DEFAULT NULL,
	`createdTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	`updatedTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_express_audit_items_task` (`taskId`),
  KEY `idx_express_audit_waybill` (`waybillNumber`(191)),
  KEY `idx_express_audit_mark` (`markStatus`),
  CONSTRAINT `fk_express_audit_items_task` FOREIGN KEY (`taskId`) REFERENCES `express_audit_tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Express bill audit items';


-- ----------------------------
-- Table structure for merchants
-- ----------------------------
DROP TABLE IF EXISTS `merchant_shops`;
DROP TABLE IF EXISTS `merchants`;
CREATE TABLE `merchants` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`mid` varchar(765) DEFAULT NULL,
	`merchantName` varchar(765) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_merchants_mid` (`mid`(191)),
  KEY `idx_merchants_name` (`merchantName`(191))
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='InnoDB free: 4096 kB';

-- ----------------------------
-- Records of merchants
-- ----------------------------

-- ----------------------------
-- Table structure for merchant_shops
-- ----------------------------
CREATE TABLE `merchant_shops` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`merchantId` int(11) NOT NULL,
	`shopCode` varchar(765) DEFAULT NULL,
	`shopName` varchar(765) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_merchant_shops_merchant` (`merchantId`),
  KEY `idx_merchant_shops_code` (`shopCode`(191)),
  KEY `idx_merchant_shops_name` (`shopName`(191)),
  CONSTRAINT `fk_merchant_shops_merchant` FOREIGN KEY (`merchantId`) REFERENCES `merchants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Merchant shop bindings';

-- ----------------------------
-- Records of merchant_shops
-- ----------------------------

-- ----------------------------
-- Table structure for bills
-- ----------------------------
DROP TABLE IF EXISTS `bills`;
CREATE TABLE `bills` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`shareCode` varchar(32) NOT NULL,
	`shareUrl` varchar(1024) DEFAULT NULL,
	`merchantId` int(11) DEFAULT NULL,
	`merchantCode` varchar(765) DEFAULT NULL,
	`merchantName` varchar(765) DEFAULT NULL,
	`shopCode` varchar(765) DEFAULT NULL,
	`shopName` varchar(765) DEFAULT NULL,
	`startTime` TIMESTAMP NULL DEFAULT NULL,
	`endTime` TIMESTAMP NULL DEFAULT NULL,
	`orderCount` int(11) DEFAULT 0,
	`remoteCount` int(11) DEFAULT 0,
	`errorCount` int(11) DEFAULT 0,
	`totalAmount` decimal(12,2) DEFAULT 0.00,
	`billText` LONGTEXT DEFAULT NULL,
	`orderData` LONGTEXT DEFAULT NULL,
	`checkedStatus` tinyint(1) DEFAULT 0,
	`checkedTime` TIMESTAMP NULL DEFAULT NULL,
	`checkedRemark` varchar(765) DEFAULT NULL,
	`createdTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_bills_share_code` (`shareCode`),
  KEY `idx_bills_created_time` (`createdTime`),
  KEY `idx_bills_checked_status` (`checkedStatus`),
  KEY `idx_bills_checked_time` (`checkedTime`),
  KEY `idx_bills_merchant_id` (`merchantId`),
  KEY `idx_bills_merchant_code` (`merchantCode`(191)),
  KEY `idx_bills_shop_code` (`shopCode`(191))
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Bill share records';

-- ----------------------------
-- Records of bills
-- ----------------------------

-- ----------------------------
-- Table structure for merchant_payments
-- ----------------------------
DROP TABLE IF EXISTS `merchant_payments`;
CREATE TABLE `merchant_payments` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`merchantId` int(11) DEFAULT NULL,
	`merchantCode` varchar(765) DEFAULT NULL,
	`merchantName` varchar(765) DEFAULT NULL,
	`amount` decimal(12,2) DEFAULT 0.00,
	`paymentTime` TIMESTAMP NULL DEFAULT NULL,
	`remark` varchar(765) DEFAULT NULL,
	`createdTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_merchant_payments_merchant_id` (`merchantId`),
  KEY `idx_merchant_payments_merchant_code` (`merchantCode`(191)),
  KEY `idx_merchant_payments_payment_time` (`paymentTime`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Merchant payment records';
-- ----------------------------
-- Records of merchant_payments
-- ----------------------------

-- ----------------------------
-- Table structure for merchant_debts
-- ----------------------------
DROP TABLE IF EXISTS `merchant_debts`;
CREATE TABLE `merchant_debts` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`merchantId` int(11) DEFAULT NULL,
	`merchantCode` varchar(765) DEFAULT NULL,
	`merchantName` varchar(765) DEFAULT NULL,
	`amount` decimal(12,2) DEFAULT 0.00,
	`debtTime` TIMESTAMP NULL DEFAULT NULL,
	`remark` varchar(765) DEFAULT NULL,
	`createdTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_merchant_debts_merchant_id` (`merchantId`),
  KEY `idx_merchant_debts_merchant_code` (`merchantCode`(191)),
  KEY `idx_merchant_debts_debt_time` (`debtTime`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Merchant debt records';
-- ----------------------------
-- Records of merchant_debts
-- ----------------------------

-- ----------------------------
-- Table structure for admins
-- ----------------------------
DROP TABLE IF EXISTS `admin_sessions`;
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`username` varchar(100) NOT NULL,
	`passwordHash` varchar(255) NOT NULL,
	`status` tinyint(1) NOT NULL DEFAULT 1,
	`createdTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	`updatedTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_admins_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Admin accounts';

-- 首次导入后请先在 api/ini.php 中设置初始管理员账号和密码，首次登录后系统会自动写入密码哈希。

-- ----------------------------
-- Table structure for admin_sessions
-- ----------------------------
CREATE TABLE `admin_sessions` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`adminId` int(11) NOT NULL,
	`tokenHash` varchar(64) NOT NULL,
	`expiresAt` TIMESTAMP NULL DEFAULT NULL,
	`createdTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_admin_sessions_token` (`tokenHash`),
  KEY `idx_admin_sessions_admin` (`adminId`),
  KEY `idx_admin_sessions_expires` (`expiresAt`),
  CONSTRAINT `fk_admin_sessions_admin` FOREIGN KEY (`adminId`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Admin login sessions';

-- ----------------------------
-- Table structure for system_settings
-- ----------------------------
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`setting_key` varchar(100) NOT NULL,
	`setting_value` LONGTEXT DEFAULT NULL,
	`remark` varchar(765) DEFAULT NULL,
	`createdTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	`updatedTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_system_settings_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='System settings';

-- API token 默认兼容 api/ini.php 中的 $authorization。
-- 如需切换到数据库配置，可在系统设置页面保存 token，或手动执行：
-- INSERT INTO `system_settings` (`setting_key`, `setting_value`, `remark`) VALUES ('api_token', 'your_api_token', 'External API token') ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`), `updatedTime` = NOW();
-- INSERT INTO `system_settings` (`setting_key`, `setting_value`, `remark`) VALUES ('api_token_deleted', '0', '1 means token disabled') ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`), `updatedTime` = NOW();

-- ----------------------------
-- Incremental SQL for existing database
-- ----------------------------
SET @has_products_merchant_ids := (
	SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'products'
	  AND COLUMN_NAME = 'merchantIds'
);
SET @sql_products_merchant_ids := IF(
	@has_products_merchant_ids = 0,
	'ALTER TABLE `products` ADD COLUMN `merchantIds` varchar(2000) DEFAULT NULL AFTER `productName`',
	'SELECT 1'
);
PREPARE stmt_products_merchant_ids FROM @sql_products_merchant_ids;
EXECUTE stmt_products_merchant_ids;
DEALLOCATE PREPARE stmt_products_merchant_ids;

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

CREATE TABLE IF NOT EXISTS `bills` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`shareCode` varchar(32) NOT NULL,
	`shareUrl` varchar(1024) DEFAULT NULL,
	`merchantId` int(11) DEFAULT NULL,
	`merchantCode` varchar(765) DEFAULT NULL,
	`merchantName` varchar(765) DEFAULT NULL,
	`shopCode` varchar(765) DEFAULT NULL,
	`shopName` varchar(765) DEFAULT NULL,
	`startTime` TIMESTAMP NULL DEFAULT NULL,
	`endTime` TIMESTAMP NULL DEFAULT NULL,
	`orderCount` int(11) DEFAULT 0,
	`remoteCount` int(11) DEFAULT 0,
	`errorCount` int(11) DEFAULT 0,
	`totalAmount` decimal(12,2) DEFAULT 0.00,
	`billText` LONGTEXT DEFAULT NULL,
	`orderData` LONGTEXT DEFAULT NULL,
	`checkedStatus` tinyint(1) DEFAULT 0,
	`checkedTime` TIMESTAMP NULL DEFAULT NULL,
	`checkedRemark` varchar(765) DEFAULT NULL,
	`createdTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_bills_share_code` (`shareCode`),
  KEY `idx_bills_created_time` (`createdTime`),
  KEY `idx_bills_checked_status` (`checkedStatus`),
  KEY `idx_bills_checked_time` (`checkedTime`),
  KEY `idx_bills_merchant_id` (`merchantId`),
  KEY `idx_bills_merchant_code` (`merchantCode`(191)),
  KEY `idx_bills_shop_code` (`shopCode`(191))
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Bill share records';

ALTER TABLE `bills` ADD COLUMN `checkedStatus` tinyint(1) DEFAULT 0 AFTER `orderData`;
ALTER TABLE `bills` ADD COLUMN `checkedTime` TIMESTAMP NULL DEFAULT NULL AFTER `checkedStatus`;
ALTER TABLE `bills` ADD COLUMN `checkedRemark` varchar(765) DEFAULT NULL AFTER `checkedTime`;
ALTER TABLE `bills` ADD KEY `idx_bills_checked_status` (`checkedStatus`);
ALTER TABLE `bills` ADD KEY `idx_bills_checked_time` (`checkedTime`);

CREATE TABLE IF NOT EXISTS `merchant_payments` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`merchantId` int(11) DEFAULT NULL,
	`merchantCode` varchar(765) DEFAULT NULL,
	`merchantName` varchar(765) DEFAULT NULL,
	`amount` decimal(12,2) DEFAULT 0.00,
	`paymentTime` TIMESTAMP NULL DEFAULT NULL,
	`remark` varchar(765) DEFAULT NULL,
	`createdTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_merchant_payments_merchant_id` (`merchantId`),
  KEY `idx_merchant_payments_merchant_code` (`merchantCode`(191)),
  KEY `idx_merchant_payments_payment_time` (`paymentTime`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Merchant payment records';

CREATE TABLE IF NOT EXISTS `merchant_debts` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`merchantId` int(11) DEFAULT NULL,
	`merchantCode` varchar(765) DEFAULT NULL,
	`merchantName` varchar(765) DEFAULT NULL,
	`amount` decimal(12,2) DEFAULT 0.00,
	`debtTime` TIMESTAMP NULL DEFAULT NULL,
	`remark` varchar(765) DEFAULT NULL,
	`createdTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_merchant_debts_merchant_id` (`merchantId`),
  KEY `idx_merchant_debts_merchant_code` (`merchantCode`(191)),
  KEY `idx_merchant_debts_debt_time` (`debtTime`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Merchant debt records';

CREATE TABLE IF NOT EXISTS `admins` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`username` varchar(100) NOT NULL,
	`passwordHash` varchar(255) NOT NULL,
	`status` tinyint(1) NOT NULL DEFAULT 1,
	`createdTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	`updatedTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_admins_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Admin accounts';

CREATE TABLE IF NOT EXISTS `admin_sessions` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`adminId` int(11) NOT NULL,
	`tokenHash` varchar(64) NOT NULL,
	`expiresAt` TIMESTAMP NULL DEFAULT NULL,
	`createdTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_admin_sessions_token` (`tokenHash`),
  KEY `idx_admin_sessions_admin` (`adminId`),
  KEY `idx_admin_sessions_expires` (`expiresAt`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Admin login sessions';

CREATE TABLE IF NOT EXISTS `system_settings` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`setting_key` varchar(100) NOT NULL,
	`setting_value` LONGTEXT DEFAULT NULL,
	`remark` varchar(765) DEFAULT NULL,
	`createdTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	`updatedTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_system_settings_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='System settings';

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `remark`)
VALUES ('api_token_deleted', '0', '1 means token disabled')
ON DUPLICATE KEY UPDATE `setting_value` = `setting_value`;

CREATE TABLE IF NOT EXISTS `expresses` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`expressName` varchar(765) DEFAULT NULL,
	`templateCode` varchar(765) DEFAULT NULL,
	`regexRules` LONGTEXT DEFAULT NULL,
	`feeRules` LONGTEXT DEFAULT NULL,
	`settleTarget` varchar(765) DEFAULT NULL,
	`freightPayer` varchar(765) DEFAULT NULL,
	`status` varchar(765) DEFAULT '有效',
	`remoteNote` varchar(765) DEFAULT NULL,
	`remark` varchar(765) DEFAULT NULL,
	`createdTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	`updatedTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_expresses_name` (`expressName`(191)),
  KEY `idx_expresses_status` (`status`(191)),
  KEY `idx_expresses_settle` (`settleTarget`(191))
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Express fee templates';

CREATE TABLE IF NOT EXISTS `express_audit_tasks` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`taskDate` date DEFAULT NULL,
	`expressCompany` varchar(765) DEFAULT NULL,
	`extraFee` decimal(12,2) DEFAULT 0.00,
	`totalCount` int(11) DEFAULT 0,
	`checkedCount` int(11) DEFAULT 0,
	`suspiciousCount` int(11) DEFAULT 0,
	`redCount` int(11) DEFAULT 0,
	`yellowCount` int(11) DEFAULT 0,
	`greenCount` int(11) DEFAULT 0,
	`deviationPercent` decimal(10,2) DEFAULT 0.00,
	`createdTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	`updatedTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_express_audit_task_date` (`taskDate`),
  KEY `idx_express_audit_company` (`expressCompany`(191))
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Express bill audit tasks';

CREATE TABLE IF NOT EXISTS `express_audit_items` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`taskId` int(11) NOT NULL,
	`waybillNumber` varchar(765) DEFAULT NULL,
	`billDate` TIMESTAMP NULL DEFAULT NULL,
	`weight` decimal(12,3) DEFAULT 0.000,
	`actualFee` decimal(12,2) DEFAULT 0.00,
	`province` varchar(765) DEFAULT NULL,
	`orderId` varchar(765) DEFAULT NULL,
	`orderWeight` decimal(12,3) DEFAULT 0.000,
	`expectedFee` decimal(12,2) DEFAULT 0.00,
	`deviationPercent` decimal(10,2) DEFAULT 0.00,
	`markStatus` varchar(32) DEFAULT NULL,
	`manualStatus` varchar(32) DEFAULT NULL,
	`reason` varchar(765) DEFAULT NULL,
	`remark` varchar(765) DEFAULT NULL,
	`rawData` LONGTEXT DEFAULT NULL,
	`createdTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	`updatedTime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_express_audit_items_task` (`taskId`),
  KEY `idx_express_audit_waybill` (`waybillNumber`(191)),
  KEY `idx_express_audit_mark` (`markStatus`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Express bill audit items';

-- 首次导入后请先在 api/ini.php 中设置初始管理员账号和密码，首次登录后系统会自动写入管理员密码哈希。
