CREATE TABLE IF NOT EXISTS `tear_orders` (
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
