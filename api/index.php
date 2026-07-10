<?php
// 显示所有错误（包含E_NOTICE级别）
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once 'base/parentProduct.php';
require_once 'base/order.php';
require_once 'ini.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
//header('Access-Control-Allow-Headers: token, Content-Type');

// 设置时区（根据需要调整）
date_default_timezone_set('Asia/Shanghai');

switch($act)
{
	case "checkServer":
		 checkServer();
		break;
	case 'addOrders':
		$orderCls = new Order();
		$orderCls->addOrder();
		break;
	case 'getOrders':
		$orderCls = new Order();
		$orderCls->getOrders();
		break;
	case 'del':
		$orderCls = new Order();
		$orderCls->delOrder();
		break;
	case 'addParentProduct':
		$parentProductCls = new ParentProduct();
		$parentProductCls->addParentProduct();
		break;
	case 'updateParentProduct':
		$parentProductCls = new ParentProduct();
		$parentProductCls->updateParentProduct();
		break;
	case 'getParentProducts':
		$parentProductCls = new ParentProduct();
		$parentProductCls->getParentProducts();
		break;
	case 'delParentProduct':
		$parentProductCls = new ParentProduct();
		$parentProductCls->delParentProduct();
		break;
	case 'addProduct':
		$ProductCls = new Product();
		$ProductCls->addProduct();
		break;
	case 'updateProduct':
		$ProductCls = new Product();
		$ProductCls->updateProduct();
		break;
	case 'getProducts':
		$ProductCls = new Product();
		$ProductCls->getProducts();
		break;
	case 'delProduct':
		$ProductCls = new Product();
		$ProductCls->delProduct();
		break;
}


function checkServer()
{
	global $input;
	$timestamp_ = $input['timestamp']??0;
	$JsonArray = array(
		'success' => true, 
		'timestamp' => $timestamp_
	);  
	echo json_encode($JsonArray);
	exit;
}



function echo_error($message) {
    echo json_encode(['success'=>false, 'txt'=>$message]);
    exit;
}
?>