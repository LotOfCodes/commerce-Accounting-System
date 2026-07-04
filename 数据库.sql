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
  PRIMARY KEY (`id`) USING BTREE
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
  PRIMARY KEY (`id`) USING BTREE
)  AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='InnoDB free: 4096 kB';

-- ----------------------------
-- Records of beans
-- ----------------------------
-- ----------------------------
-- Table structure for parentProducts
-- ----------------------------
DROP TABLE IF EXISTS `parentProducts`;
CREATE TABLE `parentProducts` (
  	`id` int(11) NOT NULL AUTO_INCREMENT,
  	`product_name` varchar(765) DEFAULT NULL,
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
-- Records of parentProducts
-- ----------------------------

-- ----------------------------
-- Table structure for products
-- ----------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  	`id` int(11) NOT NULL AUTO_INCREMENT,
	`pid` varchar(765) DEFAULT NULL,
  	`productName` varchar(765) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
)  AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='InnoDB free: 4096 kB';

-- ----------------------------
-- Records of products
-- ----------------------------

-- ----------------------------
-- Table structure for merchants
-- ----------------------------
DROP TABLE IF EXISTS `merchants`;
CREATE TABLE `merchants` (
  	`id` int(11) NOT NULL AUTO_INCREMENT,
	`mid` varchar(765) DEFAULT NULL,
  	`merchantName` varchar(765) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
)  AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='InnoDB free: 4096 kB';

-- ----------------------------
-- Records of merchants
-- ----------------------------