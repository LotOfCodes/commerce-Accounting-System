<?php
require_once __DIR__ . '/../ini.php';

class Bill
{
	function addBill()
	{
		$ini = new ini();
		$input = $this->input();
		$con = $this->connect($ini);

		$shareCode = $this->inputValue($input, "shareCode", "");
		if ($shareCode === "") {
			$shareCode = $this->randomCode($con);
		} else if ($this->shareCodeExists($con, $shareCode)) {
			$oldCode = $shareCode;
			$shareCode = $this->randomCode($con);
		}
		$shareUrl = $this->inputValue($input, "shareUrl", "");
		if (isset($oldCode) && $shareUrl !== "") {
			$shareUrl = str_replace($oldCode, $shareCode, $shareUrl);
		}
		$merchantId = intval($this->inputValue($input, "merchantId", 0));
		$merchantCode = $this->inputValue($input, "merchantCode", "");
		$merchantName = $this->inputValue($input, "merchantName", "");
		$shopCode = $this->inputValue($input, "shopCode", "");
		$shopName = $this->inputValue($input, "shopName", "");
		$startTime = $this->inputValue($input, "startTime", null);
		$endTime = $this->inputValue($input, "endTime", null);
		$orderCount = intval($this->inputValue($input, "orderCount", 0));
		$remoteCount = intval($this->inputValue($input, "remoteCount", 0));
		$errorCount = intval($this->inputValue($input, "errorCount", 0));
		$totalAmount = strval($this->inputValue($input, "totalAmount", "0"));
		$billText = $this->inputValue($input, "billText", "");
		$orderData = $this->jsonValue($this->inputValue($input, "orderData", array()));

		$stmt = $con->prepare("INSERT INTO bills (`shareCode`, `shareUrl`, `merchantId`, `merchantCode`, `merchantName`, `shopCode`, `shopName`, `startTime`, `endTime`, `orderCount`, `remoteCount`, `errorCount`, `totalAmount`, `billText`, `orderData`, `createdTime`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
		if (!$stmt) {
			$this->echo_error(mysqli_error($con));
		}
		$stmt->bind_param("ssissssssiiisss", $shareCode, $shareUrl, $merchantId, $merchantCode, $merchantName, $shopCode, $shopName, $startTime, $endTime, $orderCount, $remoteCount, $errorCount, $totalAmount, $billText, $orderData);
		if (!$stmt->execute()) {
			$this->echo_error(mysqli_error($con));
		}
		$id = mysqli_insert_id($con);
		$stmt->close();
		@mysqli_close($con);
		echo json_encode(array('success' => true, 'id' => $id, 'shareCode' => $shareCode, 'shareUrl' => $shareUrl));
		exit;
	}

	function getBills()
	{
		$ini = new ini();
		$input = $this->input();
		$con = $this->connect($ini);
		$page = intval($this->inputValue($input, "page", 1));
		$pageSize = intval($this->inputValue($input, "pageSize", 50));
		$page = $page > 0 ? $page : 1;
		$pageSize = $pageSize > 0 ? $pageSize : 50;
		$pageSize = $pageSize > 200 ? 200 : $pageSize;
		$offset = ($page - 1) * $pageSize;
		$keyword = trim($this->inputValue($input, "keyword", ""));
		$merchantKeyword = trim($this->inputValue($input, "merchantKeyword", ""));
		$shopKeyword = trim($this->inputValue($input, "shopKeyword", ""));
		$createdStart = trim($this->inputValue($input, "createdStart", ""));
		$createdEnd = trim($this->inputValue($input, "createdEnd", ""));
		$checkedStatus = trim(strval($this->inputValue($input, "checkedStatus", "")));
		$whereSql = "1=1";
		$params = array();
		$types = "";
		$bills = array();
		$total = 0;

		if ($keyword !== "") {
			$whereSql .= " and (`shareCode` LIKE ? or `merchantCode` LIKE ? or `merchantName` LIKE ? or `shopCode` LIKE ? or `shopName` LIKE ?)";
			$keywordLike = "%".$keyword."%";
			$params[] = $keywordLike;
			$params[] = $keywordLike;
			$params[] = $keywordLike;
			$params[] = $keywordLike;
			$params[] = $keywordLike;
			$types .= "sssss";
		}
		if ($merchantKeyword !== "") {
			$whereSql .= " and (`merchantCode` LIKE ? or `merchantName` LIKE ?)";
			$merchantLike = "%".$merchantKeyword."%";
			$params[] = $merchantLike;
			$params[] = $merchantLike;
			$types .= "ss";
		}
		if ($shopKeyword !== "") {
			$whereSql .= " and (`shopCode` LIKE ? or `shopName` LIKE ?)";
			$shopLike = "%".$shopKeyword."%";
			$params[] = $shopLike;
			$params[] = $shopLike;
			$types .= "ss";
		}
		if ($createdStart !== "") {
			$whereSql .= " and `createdTime` >= ?";
			$params[] = $this->normalizeDateTime($createdStart, false);
			$types .= "s";
		}
		if ($createdEnd !== "") {
			$whereSql .= " and `createdTime` <= ?";
			$params[] = $this->normalizeDateTime($createdEnd, true);
			$types .= "s";
		}
		if ($checkedStatus === "0" || $checkedStatus === "1") {
			$whereSql .= " and `checkedStatus` = ?";
			$params[] = intval($checkedStatus);
			$types .= "i";
		}

		$countStmt = $con->prepare("SELECT COUNT(*) AS total FROM bills WHERE ".$whereSql);
		if (!$countStmt) {
			$this->echo_error(mysqli_error($con));
		}
		if ($types !== "") {
			$countParams = $params;
			array_unshift($countParams, $types);
			call_user_func_array(array($countStmt, "bind_param"), $this->refValues($countParams));
		}
		$countStmt->execute();
		$countResult = $countStmt->get_result();
		if ($countResult && $row = mysqli_fetch_assoc($countResult)) {
			$total = intval($row["total"]);
		}
		$countStmt->close();

		$stmt = $con->prepare("SELECT * FROM bills WHERE ".$whereSql." ORDER BY id DESC LIMIT ? OFFSET ?");
		if (!$stmt) {
			$this->echo_error(mysqli_error($con));
		}
		$queryParams = $params;
		$queryParams[] = $pageSize;
		$queryParams[] = $offset;
		array_unshift($queryParams, $types."ii");
		call_user_func_array(array($stmt, "bind_param"), $this->refValues($queryParams));
		$stmt->execute();
		$result = $stmt->get_result();
		while ($row = mysqli_fetch_assoc($result)) {
			array_push($bills, array(
				'id' => $row["id"],
				'shareCode' => $this->rowValue($row, "shareCode", ""),
				'shareUrl' => $this->rowValue($row, "shareUrl", ""),
				'merchantId' => $this->rowValue($row, "merchantId", ""),
				'merchantCode' => $this->rowValue($row, "merchantCode", ""),
				'merchantName' => $this->rowValue($row, "merchantName", ""),
				'shopCode' => $this->rowValue($row, "shopCode", ""),
				'shopName' => $this->rowValue($row, "shopName", ""),
				'startTime' => $this->rowValue($row, "startTime", ""),
				'endTime' => $this->rowValue($row, "endTime", ""),
				'orderCount' => $this->rowValue($row, "orderCount", "0"),
				'remoteCount' => $this->rowValue($row, "remoteCount", "0"),
				'errorCount' => $this->rowValue($row, "errorCount", "0"),
				'totalAmount' => $this->rowValue($row, "totalAmount", "0"),
				'billText' => $this->rowValue($row, "billText", ""),
				'checkedStatus' => $this->rowValue($row, "checkedStatus", "0"),
				'checkedTime' => $this->rowValue($row, "checkedTime", ""),
				'checkedRemark' => $this->rowValue($row, "checkedRemark", ""),
				'createdTime' => $this->rowValue($row, "createdTime", "")
			));
		}
		$stmt->close();
		@mysqli_close($con);
		echo json_encode(array('success' => true, 'data' => $bills, 'total' => $total, 'page' => $page, 'pageSize' => $pageSize));
		exit;
	}

	function getBillShare()
	{
		$ini = new ini();
		$input = $this->input();
		$con = $this->connect($ini);
		$shareCode = $this->inputValue($input, "shareCode", "");
		if ($shareCode === "") {
			$this->echo_error('empty params');
		}
		$stmt = $con->prepare("SELECT * FROM bills WHERE `shareCode` = ? LIMIT 1");
		if (!$stmt) {
			$this->echo_error(mysqli_error($con));
		}
		$stmt->bind_param("s", $shareCode);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = mysqli_fetch_assoc($result);
		if (!$row) {
			$stmt->close();
			@mysqli_close($con);
			$this->echo_error('bill not found');
		}
		$orderData = json_decode($this->rowValue($row, "orderData", "[]"), true);
		$orderData = is_array($orderData) ? $this->enrichBillOrders($con, $orderData) : array();
		$stmt->close();
		@mysqli_close($con);
		echo json_encode(array('success' => true, 'data' => array(
			'id' => $row["id"],
			'shareCode' => $this->rowValue($row, "shareCode", ""),
			'shareUrl' => $this->rowValue($row, "shareUrl", ""),
			'merchantCode' => $this->rowValue($row, "merchantCode", ""),
			'merchantName' => $this->rowValue($row, "merchantName", ""),
			'shopCode' => $this->rowValue($row, "shopCode", ""),
			'shopName' => $this->rowValue($row, "shopName", ""),
			'startTime' => $this->rowValue($row, "startTime", ""),
			'endTime' => $this->rowValue($row, "endTime", ""),
			'orderCount' => $this->rowValue($row, "orderCount", "0"),
			'remoteCount' => $this->rowValue($row, "remoteCount", "0"),
			'errorCount' => $this->rowValue($row, "errorCount", "0"),
			'totalAmount' => $this->rowValue($row, "totalAmount", "0"),
			'billText' => $this->rowValue($row, "billText", ""),
			'orderData' => $orderData,
			'checkedStatus' => $this->rowValue($row, "checkedStatus", "0"),
			'checkedTime' => $this->rowValue($row, "checkedTime", ""),
			'checkedRemark' => $this->rowValue($row, "checkedRemark", ""),
			'createdTime' => $this->rowValue($row, "createdTime", "")
		)));
		exit;
	}

	function enrichBillOrders($con, $orders)
	{
		$enriched = array();
		foreach ($orders as $order) {
			if (!is_array($order)) {
				continue;
			}
			$orderId = $this->arrayValue($order, "orderId", "");
			$dbOrder = $orderId !== "" ? $this->findOrderById($con, $orderId) : array();
			$beans = $orderId !== "" ? $this->findBeansByParentOrderId($con, $orderId) : array();
			$items = $this->arrayValue($order, "items", array());
			if (!is_array($items) || count($items) === 0) {
				$items = $this->beansToBillItems($beans, $order, $dbOrder);
			}
			$order["platId"] = $this->firstNonEmpty(array($this->arrayValue($order, "platId", ""), $this->arrayValue($dbOrder, "platId", "")));
			$order["tradeStatus"] = $this->firstNonEmpty(array($this->arrayValue($order, "tradeStatus", ""), $this->arrayValue($dbOrder, "tradeStatus", "")));
			$order["statusText"] = $this->firstNonEmpty(array($this->arrayValue($order, "statusText", ""), $this->statusText($order["tradeStatus"])));
			$order["customer"] = $this->firstNonEmpty(array($this->arrayValue($order, "customer", ""), $this->arrayValue($dbOrder, "customer", "")));
			$order["shopName"] = $this->firstNonEmpty(array($this->arrayValue($order, "shopName", ""), $this->arrayValue($dbOrder, "shopName", "")));
			$order["receiverName"] = $this->firstNonEmpty(array($this->arrayValue($order, "receiverName", ""), $this->arrayValue($dbOrder, "receiverName", "")));
			$order["receiverMobile"] = $this->firstNonEmpty(array($this->arrayValue($order, "receiverMobile", ""), $this->arrayValue($dbOrder, "receiverMobile", "")));
			$order["receiverProvince"] = $this->firstNonEmpty(array($this->arrayValue($order, "receiverProvince", ""), $this->arrayValue($dbOrder, "receiverProvince", "")));
			$order["receiverAddress"] = $this->firstNonEmpty(array($this->arrayValue($order, "receiverAddress", ""), $this->arrayValue($dbOrder, "receiverAddress", "")));
			$order["deliveryTime"] = $this->firstNonEmpty(array($this->arrayValue($order, "deliveryTime", ""), $this->arrayValue($dbOrder, "deliveryTime", "")));
			$order["printTime"] = $this->firstNonEmpty(array($this->arrayValue($order, "printTime", ""), $this->arrayValue($dbOrder, "printTime", "")));
			$order["items"] = $items;
			$enriched[] = $order;
		}
		return $enriched;
	}

	function findOrderById($con, $orderId)
	{
		$stmt = $con->prepare("SELECT * FROM `orders` WHERE `orderId` = ? LIMIT 1");
		if (!$stmt) {
			return array();
		}
		$stmt->bind_param("s", $orderId);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = mysqli_fetch_assoc($result);
		$stmt->close();
		return $row ? $row : array();
	}

	function findBeansByParentOrderId($con, $orderId)
	{
		$beans = array();
		$stmt = $con->prepare("SELECT * FROM `beans` WHERE `parentOrderId` = ? ORDER BY id ASC");
		if (!$stmt) {
			return $beans;
		}
		$stmt->bind_param("s", $orderId);
		$stmt->execute();
		$result = $stmt->get_result();
		while ($row = mysqli_fetch_assoc($result)) {
			$beans[] = $row;
		}
		$stmt->close();
		return $beans;
	}

	function beansToBillItems($beans, $order, $dbOrder = array())
	{
		$items = array();
		foreach ($beans as $bean) {
			$items[] = array(
				'sku' => $this->arrayValue($bean, "sku", ""),
				'quantity' => $this->arrayValue($bean, "total", ""),
				'agentPrice' => '',
				'subtotal' => '',
				'waybillCom' => $this->firstNonEmpty(array($this->arrayValue($bean, "WaybillCom", ""), $this->arrayValue($bean, "waybillCom", ""), $this->arrayValue($order, "waybillCom", ""), $this->arrayValue($dbOrder, "waybillCom", ""))),
				'waybillNumber' => $this->firstNonEmpty(array($this->arrayValue($bean, "WaybillNumber", ""), $this->arrayValue($bean, "waybillNumber", ""), $this->arrayValue($order, "waybillNumber", ""), $this->arrayValue($dbOrder, "waybillNumber", ""))),
				'weightActual' => $this->arrayValue($bean, "weightActual", "")
			);
		}
		return $items;
	}

	function statusText($value)
	{
		$map = array(
			'1' => '待发货',
			'2' => '已发货',
			'3' => '已回收',
			'WAIT_SEND' => '待发货',
			'SENT' => '已发货',
			'RECYCLED' => '已回收'
		);
		return isset($map[$value]) ? $map[$value] : $value;
	}

	function firstNonEmpty($values)
	{
		foreach ($values as $value) {
			if ($value !== null && $value !== "") {
				return $value;
			}
		}
		return "";
	}

	function checkBill()
	{
		$ini = new ini();
		$input = $this->input();
		$con = $this->connect($ini);
		$shareCode = $this->inputValue($input, "shareCode", "");
		$checkedStatus = intval($this->inputValue($input, "checkedStatus", 1)) === 1 ? 1 : 0;
		$checkedRemark = $this->inputValue($input, "checkedRemark", "");
		if ($shareCode === "") {
			$this->echo_error('empty params');
		}

		if ($checkedStatus === 1) {
			$stmt = $con->prepare("UPDATE bills SET `checkedStatus` = 1, `checkedTime` = NOW(), `checkedRemark` = ? WHERE `shareCode` = ?");
			if (!$stmt) {
				$this->echo_error(mysqli_error($con));
			}
			$stmt->bind_param("ss", $checkedRemark, $shareCode);
		} else {
			$stmt = $con->prepare("UPDATE bills SET `checkedStatus` = 0, `checkedTime` = NULL, `checkedRemark` = ? WHERE `shareCode` = ?");
			if (!$stmt) {
				$this->echo_error(mysqli_error($con));
			}
			$stmt->bind_param("ss", $checkedRemark, $shareCode);
		}
		if (!$stmt->execute()) {
			$this->echo_error(mysqli_error($con));
		}
		$affected = $stmt->affected_rows;
		$stmt->close();
		@mysqli_close($con);
		echo json_encode(array('success' => true, 'affected' => $affected));
		exit;
	}

	function delBill()
	{
		$ini = new ini();
		$input = $this->input();
		$con = $this->connect($ini);
		$shareCode = $this->inputValue($input, "shareCode", "");
		if ($shareCode === "") {
			$this->echo_error('empty params');
		}
		$stmt = $con->prepare("DELETE FROM bills WHERE `shareCode` = ?");
		if (!$stmt) {
			$this->echo_error(mysqli_error($con));
		}
		$stmt->bind_param("s", $shareCode);
		if (!$stmt->execute()) {
			$this->echo_error(mysqli_error($con));
		}
		$stmt->close();
		@mysqli_close($con);
		echo json_encode(array('success' => true));
		exit;
	}

	function randomCode($con)
	{
		$chars = '0123456789abcdef';
		do {
			$code = '';
			for ($i = 0; $i < 16; $i += 1) {
				if (function_exists('random_int')) {
					$code .= $chars[random_int(0, strlen($chars) - 1)];
				} else {
					$code .= $chars[mt_rand(0, strlen($chars) - 1)];
				}
			}
			$stmt = $con->prepare("SELECT id FROM bills WHERE shareCode = ? LIMIT 1");
			if (!$stmt) {
				return $code;
			}
			$stmt->bind_param("s", $code);
			$stmt->execute();
			$result = $stmt->get_result();
			$exists = mysqli_num_rows($result) > 0;
			$stmt->close();
		} while ($exists);
		return $code;
	}

	function shareCodeExists($con, $code)
	{
		$stmt = $con->prepare("SELECT id FROM bills WHERE shareCode = ? LIMIT 1");
		if (!$stmt) {
			return false;
		}
		$stmt->bind_param("s", $code);
		$stmt->execute();
		$result = $stmt->get_result();
		$exists = mysqli_num_rows($result) > 0;
		$stmt->close();
		return $exists;
	}

	function connect($ini)
	{
		$con = @mysqli_connect($ini->mySqlServer, $ini->mySqlUser, $ini->mySqlPass);
		if (!$con) {
			$this->echo_error('connect mysql error');
		}
		mysqli_select_db($con, $ini->mySqlDataBase);
		mysqli_query($con, "SET NAMES 'UTF8'");
		return $con;
	}

	function input()
	{
		$input = json_decode(file_get_contents('php://input'), true);
		return is_array($input) ? $input : array();
	}

	function inputValue($input, $key, $default = "")
	{
		return isset($input[$key]) ? $input[$key] : $default;
	}

	function rowValue($row, $key, $default = "")
	{
		return array_key_exists($key, $row) ? $row[$key] : $default;
	}

	function arrayValue($row, $key, $default = "")
	{
		return is_array($row) && array_key_exists($key, $row) ? $row[$key] : $default;
	}

	function jsonValue($value)
	{
		if (is_string($value)) {
			return $value;
		}
		return json_encode($value, JSON_UNESCAPED_UNICODE);
	}

	function normalizeDateTime($value, $isEnd)
	{
		$value = trim(str_replace('T', ' ', $value));
		if ($value === "") {
			return "";
		}
		if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $value)) {
			return $value . ($isEnd ? " 23:59:59" : " 00:00:00");
		}
		if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}\s+\d{1,2}:\d{1,2}$/', $value)) {
			return $value . ":00";
		}
		return substr($value, 0, 19);
	}

	function refValues(&$arr)
	{
		$refs = array();
		foreach ($arr as $key => $value) {
			$refs[$key] = &$arr[$key];
		}
		return $refs;
	}

	function echo_error($message)
	{
		echo json_encode(array('success' => false, 'txt' => $message));
		exit;
	}
}
?>
