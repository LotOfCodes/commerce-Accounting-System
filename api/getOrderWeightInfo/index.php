<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
date_default_timezone_set('Asia/Shanghai');

require_once '../Authorization.php';
require_once '../ini.php';

$ini = new ini();
$con = @mysqli_connect($ini->mySqlServer, $ini->mySqlUser, $ini->mySqlPass);
if (!$con) {
	echo json_encode(array('success' => false, 'txt' => 'connect mysql error'));
	exit;
}
mysqli_select_db($con, $ini->mySqlDataBase);
mysqli_query($con, "SET NAMES 'UTF8'");
$columnResult = mysqli_query($con, "SHOW COLUMNS FROM `products` LIKE 'merchantIds'");
if (!$columnResult || mysqli_num_rows($columnResult) === 0) {
	mysqli_query($con, "ALTER TABLE `products` ADD COLUMN `merchantIds` varchar(2000) DEFAULT NULL AFTER `productName`");
}

$products = array();
$auditItems = array();

$productResult = mysqli_query($con, "SELECT id, productName, merchantIds, matchRule, weight FROM products ORDER BY id DESC");
if ($productResult) {
	while ($row = mysqli_fetch_assoc($productResult)) {
		$products[] = array(
			'id' => $row['id'],
			'productName' => isset($row['productName']) ? $row['productName'] : '',
			'merchantIds' => isset($row['merchantIds']) ? $row['merchantIds'] : '',
			'matchRule' => isset($row['matchRule']) ? $row['matchRule'] : '',
			'weight' => isset($row['weight']) ? $row['weight'] : ''
		);
	}
}

$auditResult = mysqli_query($con, "SELECT waybillNumber, weight, actualFee, billDate, province, taskId FROM express_audit_items WHERE waybillNumber IS NOT NULL AND waybillNumber <> '' ORDER BY id DESC LIMIT 50000");
if ($auditResult) {
	while ($row = mysqli_fetch_assoc($auditResult)) {
		$waybill = isset($row['waybillNumber']) ? trim($row['waybillNumber']) : '';
		if ($waybill === '' || isset($auditItems[$waybill])) {
			continue;
		}
		$auditItems[$waybill] = array(
			'waybillNumber' => $waybill,
			'weight' => isset($row['weight']) ? $row['weight'] : '',
			'actualFee' => isset($row['actualFee']) ? $row['actualFee'] : '',
			'billDate' => isset($row['billDate']) ? $row['billDate'] : '',
			'province' => isset($row['province']) ? $row['province'] : '',
			'taskId' => isset($row['taskId']) ? $row['taskId'] : ''
		);
	}
}

@mysqli_close($con);
echo json_encode(array('success' => true, 'products' => $products, 'auditItems' => array_values($auditItems)));
?>
