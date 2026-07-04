<?php
// 显示所有错误（包含E_NOTICE级别）
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
// 设置时区（根据需要调整）
date_default_timezone_set('Asia/Shanghai');

require_once '../Authorization.php';
require_once '../base/order.php';
$orderCls = new Order();
$orderCls->delOrder();
?>