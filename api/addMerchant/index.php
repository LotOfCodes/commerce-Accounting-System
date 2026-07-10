<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
date_default_timezone_set('Asia/Shanghai');

require_once '../Authorization.php';
require_once '../base/merchant.php';
$MerchantCls = new Merchant();
$MerchantCls->addMerchant();
?>
