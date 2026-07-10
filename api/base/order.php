<?php
class Order
{
	function __construct()
    {
        //$this->crypto = new crypto($key, $iv);
        //$this->ini    = new ini();

    }
	//添加订单
	function addOrder()
	{
		$input = json_decode(file_get_contents('php://input'), true);
		if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
			$this->echo_error('JSON格式错误');
		}

		$batchInfo = $this->normalizeOrderPayload($input);
		$orders = $batchInfo['orders'];
		$isBatch = $batchInfo['isBatch'];
		if (count($orders) <= 0) {
			$this->echo_error('empty params');
		}

		$con = $this->connectDb();
		$inserted = 0;
		$updated = 0;
		$results = array();
		$existingOrderIds = $isBatch ? $this->loadExistingOrderIds($con, $orders) : null;
		if ($isBatch) {
			mysqli_begin_transaction($con);
		}
		try {
			foreach ($orders as $index => $order) {
				if (!is_array($order)) {
					throw new Exception('第'.($index + 1).'条订单格式错误');
				}
				$result = $this->saveOrderRecord($con, $order, $index, $existingOrderIds);
				if ($result['action'] === 'inserted') {
					$inserted++;
				} else {
					$updated++;
				}
				$results[] = $result;
			}
			if ($isBatch) {
				mysqli_commit($con);
			}
		} catch (Exception $e) {
			if ($isBatch) {
				mysqli_rollback($con);
			}
			@mysqli_close($con);
			$this->echo_error($e->getMessage());
		}

		$JsonArray = array(
			'success' => true
		);
		if ($isBatch) {
			$JsonArray['total'] = count($orders);
			$JsonArray['inserted'] = $inserted;
			$JsonArray['updated'] = $updated;
			$JsonArray['data'] = $results;
		}
		echo json_encode($JsonArray);
		@mysqli_close($con);
		exit;
	}
	//订单是否存在
	function isOrderExist($orderId, $con = null)
	{
		$ini = new ini();
		$shouldClose = false;
		if ($con === null) {
			$con = @mysqli_connect($ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);
			mysqli_query($con,"set names 'UTF8' ");
			mysqli_select_db($con,$ini->mySqlDataBase);
			$shouldClose = true;
		}
		$sql    = "SELECT * FROM  `orders` WHERE `orderId` =?";
		$stmt = $con->prepare($sql);
		if ($stmt) 
		{
			$stmt->bind_param("s", $orderId);
			$stmt->execute();
			$stmt->store_result();
			$exists = $stmt->num_rows > 0;
			$stmt->close();
			if ($shouldClose) {
				@mysqli_close($con);
			}
			return $exists;
		}
		else
		{
			if ($shouldClose) {
				@mysqli_close($con);
			}
			return false;
		}
	}
	//更新订单
	function updateOrder()
	{
		$input = json_decode(file_get_contents('php://input'), true);
		if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
			$this->echo_error('JSON格式错误');
		}
		$con = $this->connectDb();
		try {
			$this->updateOrderRecord($con, $this->orderSqlArray($input), trim(strval($this->arrayValue($input, 'orderId', ''))));
		} catch (Exception $e) {
			@mysqli_close($con);
			$this->echo_error($e->getMessage());
		}
		$JsonArray = array(
			'success' => true
		);
		echo json_encode($JsonArray);
		@mysqli_close($con);
		exit;
	}
	//获取订单
	function getOrders()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		$con = @mysqli_connect($ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);	
		if (!$con)
		{
			$this->echo_error('连接数据库发生错误：“'. mysqli_error()."。”");
			@mysqli_close($con);
			exit;
		} 
		mysqli_query($con,"set names 'UTF8' ");
		$db_selected = mysqli_select_db($con,$ini->mySqlDataBase);
		$merchantLookup = $this->loadMerchantLookup($con);
		//获取数据库列表
		$orders = array();
		//php
		//查询条件
		$start_time = $input["startTime"];//date('Y-m-d H:i:s',time()- 1*24*60*60);
		$end_time   = $input["endTime"];//date('Y-m-d H:i:s',time()+ 1*24*60*60);
		$page = isset($input["page"]) ? intval($input["page"]) : 1;
		$pageSize = isset($input["pageSize"]) ? intval($input["pageSize"]) : 50;
		$page = $page > 0 ? $page : 1;
		$pageSize = $pageSize > 0 ? $pageSize : 50;
		$pageSize = $pageSize > 500 ? 500 : $pageSize;
		$offset = ($page - 1) * $pageSize;
		$merchant = isset($input["merchant"]) ? trim($input["merchant"]) : "";
		$merchantId = isset($input["merchantId"]) ? intval($input["merchantId"]) : 0;
		$shopName = isset($input["shopName"]) ? trim($input["shopName"]) : "";
		$status = isset($input["status"]) ? trim($input["status"]) : "";
		$orderType = isset($input["orderType"]) ? trim(strval($input["orderType"])) : "";
		$keywordType = isset($input["keywordType"]) ? trim($input["keywordType"]) : "";
		$keyword = isset($input["keyword"]) ? trim($input["keyword"]) : "";
		$whereSql = "`deliveryTime`>= ? and `deliveryTime`<= ?";
		$params = array($start_time, $end_time);
		$types = "ss";
		if ($merchantId > 0) {
			$whereSql .= " and (`customer` = (SELECT `mid` FROM `merchants` WHERE `id` = ?) or `customer` in (SELECT `shopCode` FROM `merchant_shops` WHERE `merchantId` = ?) or `shopName` in (SELECT `shopName` FROM `merchant_shops` WHERE `merchantId` = ?))";
			$params[] = $merchantId;
			$params[] = $merchantId;
			$params[] = $merchantId;
			$types .= "iii";
		} else if ($merchant !== "" && $merchant !== "all") {
			$whereSql .= " and (`customer` = ? or `customer` in (SELECT `shopCode` FROM `merchant_shops` WHERE `merchantId` in (SELECT `id` FROM `merchants` WHERE `mid` = ?)) or `shopName` in (SELECT `shopName` FROM `merchant_shops` WHERE `merchantId` in (SELECT `id` FROM `merchants` WHERE `mid` = ?)))";
			$params[] = $merchant;
			$params[] = $merchant;
			$params[] = $merchant;
			$types .= "sss";
		}
		if ($shopName !== "" && $shopName !== "all") {
			$whereSql .= " and (`shopName` = ? or `customer` = ?)";
			$params[] = $shopName;
			$params[] = $shopName;
			$types .= "ss";
		}
		if ($status !== "" && $status !== "all") {
			if ($status === "ready") {
				$whereSql .= " and (`tradeStatus` = ? or `tradeStatus` = ? or `tradeStatus` = ?)";
				$params[] = "2";
				$params[] = "已发货";
				$params[] = "SENT";
				$types .= "sss";
			} else {
				$whereSql .= " and `tradeStatus` = ?";
				$params[] = $status;
				$types .= "s";
			}
		}
		if ($orderType !== "" && $orderType !== "all") {
			$whereSql .= " and `orderType` = ?";
			$params[] = $orderType;
			$types .= "s";
		}
		if ($keyword !== "") {
			$keywordLike = "%" . $keyword . "%";
			if ($keywordType === "express") {
				$whereSql .= " and `waybillNumber` LIKE ?";
				$params[] = $keywordLike;
				$types .= "s";
			} else if ($keywordType === "goods") {
				$whereSql .= " and EXISTS (SELECT 1 FROM `beans` WHERE `beans`.`parentOrderId` = `orders`.`orderId` and `beans`.`sku` LIKE ?)";
				$params[] = $keywordLike;
				$types .= "s";
			} else if ($keywordType === "mobile") {
				$whereSql .= " and `receiverMobile` LIKE ?";
				$params[] = $keywordLike;
				$types .= "s";
			} else {
				$whereSql .= " and (`orderId` LIKE ? or `platId` LIKE ?)";
				$params[] = $keywordLike;
				$params[] = $keywordLike;
				$types .= "ss";
			}
		}
		//echo $start_time;

		$total = 0;
		$countSql = "SELECT COUNT(*) as total FROM `orders` WHERE " . $whereSql;
		$countStmt = $con->prepare($countSql);
		if ($countStmt) {
			$countParams = $params;
			array_unshift($countParams, $types);
			call_user_func_array(array($countStmt, "bind_param"), $this->refValues($countParams));
			$countStmt->execute();
			$countResult = $countStmt->get_result();
			if ($countRow = mysqli_fetch_array($countResult)) {
				$total = intval($countRow["total"]);
			}
			$countStmt->close();
		}

		$sql    = "SELECT * FROM  `orders` WHERE " . $whereSql . " ORDER BY id DESC LIMIT ? OFFSET ?";
		$stmt = $con->prepare($sql);
		if ($stmt) {
			// 绑定参数
			$queryParams = $params;
			$queryParams[] = $pageSize;
			$queryParams[] = $offset;
			array_unshift($queryParams, $types . "ii");
			call_user_func_array(array($stmt, "bind_param"), $this->refValues($queryParams));

			// 执行查询
			$stmt->execute();

			// 获取结果
			$result = $stmt->get_result();
			//$result = mysqli_query($con,$sql);
			if (mysqli_num_rows($result)>0)
			{	
				while($row = mysqli_fetch_array($result))
				{			
					$tempList = array(
						'customer'=>$row["customer"],
						'orderId'=>$row["orderId"],
						'platId'=>$row["platId"],
						'orderType'=>$row["orderType"],
						'tradeStatus'=>$row["tradeStatus"],
						'shopName'=>$row["shopName"],
						'receiverName'=>$row["receiverName"],
						'receiverProvince'=>$row["receiverProvince"],
						'receiverAddress'=>$row["receiverAddress"],
						'receiverMobile'=>$row["receiverMobile"],
						'remark'=>$row["remark"],
						'waybillNumber'=>$row["waybillNumber"],
						'waybillCom'=>$row["waybillCom"],
						'waybillTemplate'=>$row["waybillTemplate"],
						'deliveryTime'=>$row["deliveryTime"],
						'printTime'=>isset($row["printTime"]) ? $row["printTime"] : ""
					);
					$merchantInfo = $this->merchantInfoForOrder($merchantLookup, $tempList["customer"], $tempList["shopName"]);
					$tempList["merchantId"] = $merchantInfo["merchantId"];
					$tempList["merchantCode"] = $merchantInfo["merchantCode"];
					$tempList["merchantName"] = $merchantInfo["merchantName"];
					array_push($orders,$tempList);
				}
			}
			//输出状态和结果
			$JsonArray = array(
				'success' => true, 
				'data' => $orders,
				'total' => $total,
				'page' => $page,
				'pageSize' => $pageSize
			);  
			echo json_encode($JsonArray);
			// 关闭语句
			$stmt->close();
			exit;
		}
		else{
			$this->echo_error('error');
		}

	}
	//删除订单
	function delOrder()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		if(!isset($input["orderId"])){
			$this->echo_error('empty params');
		}	
		$orderId = trim($input['orderId']);
		if ($orderId === '') {
			$this->echo_error('empty params');
		}
		$con = @mysqli_connect($ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);
		if (!$con)
		{
			$this->echo_error('连接数据库发生错误：“'. mysqli_connect_error()."。”");
		}
		mysqli_query($con,"set names 'UTF8' ");
		mysqli_select_db($con,$ini->mySqlDataBase);
		mysqli_begin_transaction($con);

		$beanStmt = $con->prepare("DELETE FROM `beans` WHERE `parentOrderId`= ?");
		if (!$beanStmt) {
			mysqli_rollback($con);
			@mysqli_close($con);
			$this->echo_error('删除发货内容失败');
		}
		$beanStmt->bind_param("s", $orderId);
		$beanStmt->execute();
		if ($beanStmt->errno) {
			$message = $beanStmt->error;
			$beanStmt->close();
			mysqli_rollback($con);
			@mysqli_close($con);
			$this->echo_error($message);
		}
		$deletedBeans = $beanStmt->affected_rows;
		$beanStmt->close();

		$orderStmt = $con->prepare("DELETE FROM `orders` WHERE `orderId`= ?");
		if (!$orderStmt) {
			mysqli_rollback($con);
			@mysqli_close($con);
			$this->echo_error('删除订单失败');
		}
		$orderStmt->bind_param("s", $orderId);
		$orderStmt->execute();
		if ($orderStmt->errno) {
			$message = $orderStmt->error;
			$orderStmt->close();
			mysqli_rollback($con);
			@mysqli_close($con);
			$this->echo_error($message);
		}
		$deletedOrders = $orderStmt->affected_rows;
		$orderStmt->close();
		mysqli_commit($con);
		$JsonArray = array(
			'success' => true,
			'deletedOrders' => $deletedOrders,
			'deletedBeans' => $deletedBeans
		);
		echo json_encode($JsonArray);
		@mysqli_close($con);
		exit;
	}


	//ArraytoSQLstr
	function ArraytoSQLstr($arra)
	{
			$tempstr='';
			$num=1;
			foreach ($arra as $key=>$value)
			{
				 //echo $key;
				 if ($num==1)
				 { 
				 $tempstr=$key." = '".$value."' ";
				 }
				 else
				 {$tempstr=$tempstr.", ".$key." = '".$value."'";}; 
				 $num++; 
				}
			return $tempstr;
		}
	//ArraytoSQLaddstr
	function ArraytoSQLaddstr($arra)
	{
		$names='';
		$values='';
		$num=1;
		foreach ($arra as $key=>$value)
		{
			 //echo $key;
			 if ($num==1)
			 { 
			 $names=$key;
			 $values="'".$value."'";
			 }
			 else
			 { 
			 $names=$names.",".$key;
			 $values=$values.",'".$value."'";
			 }
			 $num++; 
			} 
		return "(".$names.") VALUES (".$values.")";
	}
	function echo_error($message) {
    	echo json_encode(['success'=>false, 'txt'=>$message]);
    	exit;
	}
	function normalizeOrderPayload($input) {
		if ($this->isListArray($input)) {
			return array('orders' => $input, 'isBatch' => true);
		}
		if (isset($input['orders']) && is_array($input['orders'])) {
			return array('orders' => $input['orders'], 'isBatch' => true);
		}
		if (isset($input['data']) && is_array($input['data']) && $this->isListArray($input['data'])) {
			return array('orders' => $input['data'], 'isBatch' => true);
		}
		return array('orders' => array($input), 'isBatch' => false);
	}
	function isListArray($array) {
		if (!is_array($array)) {
			return false;
		}
		$index = 0;
		foreach ($array as $key => $value) {
			if ($key !== $index) {
				return false;
			}
			$index++;
		}
		return true;
	}
	function connectDb() {
		$ini = new ini();
		$con = @mysqli_connect($ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);
		if (!$con)
		{
			$this->echo_error('连接数据库发生错误：“'. mysqli_connect_error()."。”");
		}
		if (!mysqli_select_db($con,$ini->mySqlDataBase)) {
			$message = mysqli_error($con);
			@mysqli_close($con);
			$this->echo_error('选择数据库发生错误：“'.$message.'。”');
		}
		mysqli_query($con,"SET NAMES 'UTF8'");
		return $con;
	}
	function saveOrderRecord($con, $input, $index = 0, &$existingOrderIds = null) {
		$orderId = trim(strval($this->arrayValue($input, 'orderId', '')));
		if ($orderId === '') {
			throw new Exception('第'.($index + 1).'条订单缺少orderId');
		}
		$SQLArray = $this->orderSqlArray($input);
		$exists = is_array($existingOrderIds) ? isset($existingOrderIds[$orderId]) : $this->isOrderExist($orderId, $con);
		if ($exists) {
			$this->updateOrderTradeStatusRecord($con, $SQLArray, $orderId);
			$action = 'updated';
		} else {
			$this->insertOrderRecord($con, $SQLArray);
			if (is_array($existingOrderIds)) {
				$existingOrderIds[$orderId] = true;
			}
			$action = 'inserted';
		}
		return array(
			'index' => $index,
			'orderId' => $orderId,
			'action' => $action
		);
	}
	function orderSqlArray($input) {
		$SQLArray = array(
			'customer'=>$this->arrayValue($input, 'customer', ''),
			'orderId'=>$this->arrayValue($input, 'orderId', ''),
			'platId'=>$this->arrayValue($input, 'platId', ''),
			'orderType'=>$this->arrayValue($input, 'orderType', ''),
			'tradeStatus'=>$this->arrayValue($input, 'tradeStatus', ''),
			'shopName'=>$this->arrayValue($input, 'shopName', ''),
			'receiverName'=>$this->arrayValue($input, 'receiverName', ''),
			'receiverProvince'=>$this->arrayValue($input, 'receiverProvince', ''),
			'receiverAddress'=>$this->arrayValue($input, 'receiverAddress', ''),
			'receiverMobile'=>$this->arrayValue($input, 'receiverMobile', ''),
			'remark'=>$this->arrayValue($input, 'remark', ''),
			'waybillNumber'=>$this->arrayValue($input, 'waybillNumber', ''),
			'waybillCom'=>$this->arrayValue($input, 'waybillCom', ''),
			'waybillTemplate'=>$this->arrayValue($input, 'waybillTemplate', ''),
			'deliveryTime'=>$this->arrayValue($input, 'deliveryTime', null)
		);
		if (isset($input["printTime"]) && trim(strval($input["printTime"])) !== "") {
			$SQLArray["printTime"] = $input["printTime"];
		}
		return $SQLArray;
	}
	function insertOrderRecord($con, $SQLArray) {
		$columns = array_keys($SQLArray);
		$columnSql = '`'.implode('`,`', $columns).'`';
		$placeholders = implode(',', array_fill(0, count($columns), '?'));
		$stmt = $con->prepare("INSERT INTO `orders` (".$columnSql.") VALUES (".$placeholders.")");
		if (!$stmt) {
			throw new Exception('新增订单失败：'.$con->error);
		}
		$values = array_values($SQLArray);
		$this->bindDynamicParams($stmt, str_repeat('s', count($values)), $values);
		$stmt->execute();
		if ($stmt->errno) {
			$message = $stmt->error;
			$stmt->close();
			throw new Exception($message);
		}
		$stmt->close();
	}
	function updateOrderRecord($con, $SQLArray, $orderId) {
		if ($orderId === '') {
			throw new Exception('订单缺少orderId');
		}
		$sets = array();
		foreach (array_keys($SQLArray) as $column) {
			$sets[] = '`'.$column.'` = ?';
		}
		$stmt = $con->prepare("UPDATE `orders` SET ".implode(',', $sets)." WHERE `orderId` = ?");
		if (!$stmt) {
			throw new Exception('更新订单失败：'.$con->error);
		}
		$values = array_values($SQLArray);
		$values[] = $orderId;
		$this->bindDynamicParams($stmt, str_repeat('s', count($values)), $values);
		$stmt->execute();
		if ($stmt->errno) {
			$message = $stmt->error;
			$stmt->close();
			throw new Exception($message);
		}
		$stmt->close();
	}
	function updateOrderTradeStatusRecord($con, $SQLArray, $orderId) {
		if ($orderId === '') {
			throw new Exception('订单缺少orderId');
		}
		$stmt = $con->prepare("UPDATE `orders` SET `tradeStatus` = ? WHERE `orderId` = ?");
		if (!$stmt) {
			throw new Exception('更新订单状态失败：'.$con->error);
		}
		$tradeStatus = isset($SQLArray['tradeStatus']) ? $SQLArray['tradeStatus'] : '';
		$stmt->bind_param("ss", $tradeStatus, $orderId);
		$stmt->execute();
		if ($stmt->errno) {
			$message = $stmt->error;
			$stmt->close();
			throw new Exception($message);
		}
		$stmt->close();
	}
	function loadExistingOrderIds($con, $orders) {
		$orderIds = array();
		foreach ($orders as $order) {
			if (!is_array($order)) {
				continue;
			}
			$orderId = trim(strval($this->arrayValue($order, 'orderId', '')));
			if ($orderId !== '') {
				$orderIds[$orderId] = true;
			}
		}
		$existing = array();
		$ids = array_keys($orderIds);
		foreach (array_chunk($ids, 500) as $chunk) {
			if (!count($chunk)) {
				continue;
			}
			$placeholders = implode(',', array_fill(0, count($chunk), '?'));
			$stmt = $con->prepare("SELECT `orderId` FROM `orders` WHERE `orderId` IN (".$placeholders.")");
			if (!$stmt) {
				throw new Exception('查询已有订单失败：'.$con->error);
			}
			$this->bindDynamicParams($stmt, str_repeat('s', count($chunk)), $chunk);
			$stmt->execute();
			if ($stmt->errno) {
				$message = $stmt->error;
				$stmt->close();
				throw new Exception($message);
			}
			$result = $stmt->get_result();
			while ($row = mysqli_fetch_assoc($result)) {
				$existing[$row['orderId']] = true;
			}
			$stmt->close();
		}
		return $existing;
	}
	function bindDynamicParams($stmt, $types, &$values) {
		array_unshift($values, $types);
		call_user_func_array(array($stmt, 'bind_param'), $this->refValues($values));
	}
	function refValues(&$arr) {
		$refs = array();
		foreach ($arr as $key => $value) {
			$refs[$key] = &$arr[$key];
		}
		return $refs;
	}
	function arrayValue($array, $key, $default = '') {
		return isset($array[$key]) ? $array[$key] : $default;
	}
	function loadMerchantLookup($con) {
		$lookup = array('merchants' => array(), 'values' => array());
		$result = mysqli_query($con, "SELECT `id`, `mid`, `merchantName` FROM `merchants` ORDER BY id DESC");
		if ($result) {
			while ($row = mysqli_fetch_assoc($result)) {
				$item = array(
					'merchantId' => isset($row["id"]) ? $row["id"] : "",
					'merchantCode' => isset($row["mid"]) ? $row["mid"] : "",
					'merchantName' => isset($row["merchantName"]) ? $row["merchantName"] : ""
				);
				$lookup['merchants'][$item['merchantId']] = $item;
				$this->addMerchantLookupValue($lookup, $item['merchantId'], $item);
				$this->addMerchantLookupValue($lookup, $item['merchantCode'], $item);
				$this->addMerchantLookupValue($lookup, $item['merchantName'], $item);
			}
		}
		$result = mysqli_query($con, "SELECT `merchantId`, `shopCode`, `shopName` FROM `merchant_shops` ORDER BY id DESC");
		if ($result) {
			while ($row = mysqli_fetch_assoc($result)) {
				$merchantId = isset($row["merchantId"]) ? $row["merchantId"] : "";
				if (!isset($lookup['merchants'][$merchantId])) {
					continue;
				}
				$item = $lookup['merchants'][$merchantId];
				$this->addMerchantLookupValue($lookup, isset($row["shopCode"]) ? $row["shopCode"] : "", $item);
				$this->addMerchantLookupValue($lookup, isset($row["shopName"]) ? $row["shopName"] : "", $item);
			}
		}
		return $lookup;
	}
	function addMerchantLookupValue(&$lookup, $value, $merchant) {
		$key = $this->matchKey($value);
		if ($key !== "" && !isset($lookup['values'][$key])) {
			$lookup['values'][$key] = $merchant;
		}
	}
	function merchantInfoForOrder($lookup, $customer, $shopName) {
		$customerKey = $this->matchKey($customer);
		$shopNameKey = $this->matchKey($shopName);
		if ($customerKey !== "" && isset($lookup['values'][$customerKey])) {
			return $lookup['values'][$customerKey];
		}
		if ($shopNameKey !== "" && isset($lookup['values'][$shopNameKey])) {
			return $lookup['values'][$shopNameKey];
		}
		return array('merchantId' => "", 'merchantCode' => "", 'merchantName' => "");
	}
	function matchKey($value) {
		$value = strtolower(trim(strval($value)));
		return preg_replace('/[^\x{4e00}-\x{9fa5}a-z0-9]/u', '', $value);
	}
}
?>
