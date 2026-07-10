<?php
require_once __DIR__ . '/../ini.php';

class Finance
{
	function getFinanceSummary()
	{
		$ini = new ini();
		$input = $this->input();
		$con = $this->connect($ini);
		$merchantKeyword = trim($this->inputValue($input, "merchantKeyword", ""));
		$merchantId = intval($this->inputValue($input, "merchantId", 0));
		$startDate = trim($this->inputValue($input, "startDate", ""));
		$endDate = trim($this->inputValue($input, "endDate", ""));
		$summary = array();
		$ledger = array();
		$payments = array();
		$debts = array();

		$merchants = $this->loadMerchants($con, $merchantKeyword, $merchantId);
		foreach ($merchants as $key => $merchant) {
			$summary[$key] = $merchant;
			$summary[$key]["billAmount"] = 0;
			$summary[$key]["paymentAmount"] = 0;
			$summary[$key]["debtAmount"] = 0;
			$summary[$key]["checkedBillCount"] = 0;
		}

		$billRows = $this->loadCheckedBills($con, $merchantKeyword, $merchantId, $startDate, $endDate);
		foreach ($billRows as $bill) {
			$key = $this->merchantKey($bill["merchantId"], $bill["merchantCode"], $bill["merchantName"]);
			if (!isset($summary[$key])) {
				$summary[$key] = $this->summaryItem($bill["merchantId"], $bill["merchantCode"], $bill["merchantName"]);
			}
			$amount = floatval($bill["totalAmount"]);
			$summary[$key]["billAmount"] += $amount;
			$summary[$key]["checkedBillCount"] += 1;
			array_push($ledger, array(
				'type' => 'bill',
				'typeName' => '账单入账',
				'merchantKey' => $key,
				'merchantId' => $bill["merchantId"],
				'merchantCode' => $bill["merchantCode"],
				'merchantName' => $bill["merchantName"],
				'amount' => $amount,
				'flowTime' => $bill["checkedTime"] ? $bill["checkedTime"] : $bill["createdTime"],
				'shareCode' => $bill["shareCode"],
				'remark' => '已核对账单'
			));
		}

		$paymentRows = $this->loadPayments($con, $merchantKeyword, $merchantId, $startDate, $endDate);
		foreach ($paymentRows as $payment) {
			$key = $this->merchantKey($payment["merchantId"], $payment["merchantCode"], $payment["merchantName"]);
			if (!isset($summary[$key])) {
				$summary[$key] = $this->summaryItem($payment["merchantId"], $payment["merchantCode"], $payment["merchantName"]);
			}
			$amount = floatval($payment["amount"]);
			$summary[$key]["paymentAmount"] += $amount;
			array_push($payments, $payment);
			array_push($ledger, array(
				'type' => 'payment',
				'typeName' => '收款',
				'merchantKey' => $key,
				'merchantId' => $payment["merchantId"],
				'merchantCode' => $payment["merchantCode"],
				'merchantName' => $payment["merchantName"],
				'amount' => -$amount,
				'flowTime' => $payment["paymentTime"],
				'paymentId' => $payment["id"],
				'remark' => $payment["remark"]
			));
		}

		$this->ensureDebtTable($con);
		$debtRows = $this->loadDebts($con, $merchantKeyword, $merchantId, $startDate, $endDate);
		foreach ($debtRows as $debt) {
			$key = $this->merchantKey($debt["merchantId"], $debt["merchantCode"], $debt["merchantName"]);
			if (!isset($summary[$key])) {
				$summary[$key] = $this->summaryItem($debt["merchantId"], $debt["merchantCode"], $debt["merchantName"]);
			}
			$amount = floatval($debt["amount"]);
			$summary[$key]["billAmount"] += $amount;
			array_push($debts, $debt);
			array_push($ledger, array(
				'type' => 'debt',
				'typeName' => '欠款',
				'merchantKey' => $key,
				'merchantId' => $debt["merchantId"],
				'merchantCode' => $debt["merchantCode"],
				'merchantName' => $debt["merchantName"],
				'amount' => $amount,
				'flowTime' => $debt["debtTime"],
				'debtId' => $debt["id"],
				'remark' => $debt["remark"]
			));
		}

		foreach ($summary as $key => $item) {
			$summary[$key]["billAmount"] = round(floatval($item["billAmount"]), 2);
			$summary[$key]["paymentAmount"] = round(floatval($item["paymentAmount"]), 2);
			$summary[$key]["debtAmount"] = round(floatval($item["billAmount"]) - floatval($item["paymentAmount"]), 2);
		}
		usort($summary, function ($a, $b) {
			if ($b["debtAmount"] == $a["debtAmount"]) {
				return 0;
			}
			return $b["debtAmount"] > $a["debtAmount"] ? 1 : -1;
		});
		usort($ledger, function ($a, $b) {
			return strcmp($b["flowTime"], $a["flowTime"]);
		});

		@mysqli_close($con);
		echo json_encode(array('success' => true, 'summary' => array_values($summary), 'ledger' => $ledger, 'payments' => $payments, 'debts' => $debts));
		exit;
	}

	function addPayment()
	{
		$this->savePayment(false);
	}

	function updatePayment()
	{
		$this->savePayment(true);
	}

	function delPayment()
	{
		$ini = new ini();
		$input = $this->input();
		$con = $this->connect($ini);
		$id = intval($this->inputValue($input, "id", 0));
		if ($id <= 0) {
			$this->echo_error('empty params');
		}
		$stmt = $con->prepare("DELETE FROM merchant_payments WHERE `id` = ?");
		if (!$stmt) {
			$this->echo_error(mysqli_error($con));
		}
		$stmt->bind_param("i", $id);
		if (!$stmt->execute()) {
			$this->echo_error(mysqli_error($con));
		}
		$stmt->close();
		@mysqli_close($con);
		echo json_encode(array('success' => true));
		exit;
	}

	function addDebt()
	{
		$this->saveDebt(false);
	}

	function updateDebt()
	{
		$this->saveDebt(true);
	}

	function delDebt()
	{
		$ini = new ini();
		$input = $this->input();
		$con = $this->connect($ini);
		$this->ensureDebtTable($con);
		$id = intval($this->inputValue($input, "id", 0));
		if ($id <= 0) {
			$this->echo_error('empty params');
		}
		$stmt = $con->prepare("DELETE FROM merchant_debts WHERE `id` = ?");
		if (!$stmt) {
			$this->echo_error(mysqli_error($con));
		}
		$stmt->bind_param("i", $id);
		if (!$stmt->execute()) {
			$this->echo_error(mysqli_error($con));
		}
		$stmt->close();
		@mysqli_close($con);
		echo json_encode(array('success' => true));
		exit;
	}

	function savePayment($isUpdate)
	{
		$ini = new ini();
		$input = $this->input();
		$con = $this->connect($ini);
		$id = intval($this->inputValue($input, "id", 0));
		$merchantId = intval($this->inputValue($input, "merchantId", 0));
		$merchantCode = trim($this->inputValue($input, "merchantCode", ""));
		$merchantName = trim($this->inputValue($input, "merchantName", ""));
		$amount = strval($this->inputValue($input, "amount", "0"));
		$paymentTime = trim($this->inputValue($input, "paymentTime", ""));
		$remark = trim($this->inputValue($input, "remark", ""));
		if ($isUpdate && $id <= 0) {
			$this->echo_error('empty params');
		}
		if ($merchantId <= 0 && $merchantCode === "" && $merchantName === "") {
			$this->echo_error('merchant is required');
		}
		if (floatval($amount) <= 0) {
			$this->echo_error('amount is required');
		}
		if ($paymentTime === "") {
			$paymentTime = date('Y-m-d H:i:s');
		}
		$merchant = $this->findMerchant($con, $merchantId, $merchantCode);
		if ($merchant) {
			$merchantId = intval($merchant["id"]);
			$merchantCode = $merchant["mid"];
			$merchantName = $merchant["merchantName"];
		}

		if ($isUpdate) {
			$stmt = $con->prepare("UPDATE merchant_payments SET `merchantId` = ?, `merchantCode` = ?, `merchantName` = ?, `amount` = ?, `paymentTime` = ?, `remark` = ? WHERE `id` = ?");
			if (!$stmt) {
				$this->echo_error(mysqli_error($con));
			}
			$stmt->bind_param("isssssi", $merchantId, $merchantCode, $merchantName, $amount, $paymentTime, $remark, $id);
		} else {
			$stmt = $con->prepare("INSERT INTO merchant_payments (`merchantId`, `merchantCode`, `merchantName`, `amount`, `paymentTime`, `remark`, `createdTime`) VALUES (?, ?, ?, ?, ?, ?, NOW())");
			if (!$stmt) {
				$this->echo_error(mysqli_error($con));
			}
			$stmt->bind_param("isssss", $merchantId, $merchantCode, $merchantName, $amount, $paymentTime, $remark);
		}
		if (!$stmt->execute()) {
			$this->echo_error(mysqli_error($con));
		}
		$newId = $isUpdate ? $id : mysqli_insert_id($con);
		$stmt->close();
		@mysqli_close($con);
		echo json_encode(array('success' => true, 'id' => $newId));
		exit;
	}

	function saveDebt($isUpdate)
	{
		$ini = new ini();
		$input = $this->input();
		$con = $this->connect($ini);
		$this->ensureDebtTable($con);
		$id = intval($this->inputValue($input, "id", 0));
		$merchantId = intval($this->inputValue($input, "merchantId", 0));
		$merchantCode = trim($this->inputValue($input, "merchantCode", ""));
		$merchantName = trim($this->inputValue($input, "merchantName", ""));
		$amount = strval($this->inputValue($input, "amount", "0"));
		$debtTime = trim($this->inputValue($input, "debtTime", ""));
		$remark = trim($this->inputValue($input, "remark", ""));
		if ($isUpdate && $id <= 0) {
			$this->echo_error('empty params');
		}
		if ($merchantId <= 0 && $merchantCode === "" && $merchantName === "") {
			$this->echo_error('merchant is required');
		}
		if (floatval($amount) <= 0) {
			$this->echo_error('amount is required');
		}
		if ($debtTime === "") {
			$debtTime = date('Y-m-d H:i:s');
		}
		$merchant = $this->findMerchant($con, $merchantId, $merchantCode);
		if ($merchant) {
			$merchantId = intval($merchant["id"]);
			$merchantCode = $merchant["mid"];
			$merchantName = $merchant["merchantName"];
		}

		if ($isUpdate) {
			$stmt = $con->prepare("UPDATE merchant_debts SET `merchantId` = ?, `merchantCode` = ?, `merchantName` = ?, `amount` = ?, `debtTime` = ?, `remark` = ? WHERE `id` = ?");
			if (!$stmt) {
				$this->echo_error(mysqli_error($con));
			}
			$stmt->bind_param("isssssi", $merchantId, $merchantCode, $merchantName, $amount, $debtTime, $remark, $id);
		} else {
			$stmt = $con->prepare("INSERT INTO merchant_debts (`merchantId`, `merchantCode`, `merchantName`, `amount`, `debtTime`, `remark`, `createdTime`) VALUES (?, ?, ?, ?, ?, ?, NOW())");
			if (!$stmt) {
				$this->echo_error(mysqli_error($con));
			}
			$stmt->bind_param("isssss", $merchantId, $merchantCode, $merchantName, $amount, $debtTime, $remark);
		}
		if (!$stmt->execute()) {
			$this->echo_error(mysqli_error($con));
		}
		$newId = $isUpdate ? $id : mysqli_insert_id($con);
		$stmt->close();
		@mysqli_close($con);
		echo json_encode(array('success' => true, 'id' => $newId));
		exit;
	}

	function loadMerchants($con, $keyword, $merchantId)
	{
		$items = array();
		if ($merchantId > 0) {
			$stmt = $con->prepare("SELECT `id`, `mid`, `merchantName` FROM merchants WHERE `id` = ? ORDER BY id DESC");
			if (!$stmt) { return $items; }
			$stmt->bind_param("i", $merchantId);
		} else if ($keyword !== "") {
			$like = "%".$keyword."%";
			$stmt = $con->prepare("SELECT `id`, `mid`, `merchantName` FROM merchants WHERE `mid` LIKE ? OR `merchantName` LIKE ? ORDER BY id DESC");
			if (!$stmt) { return $items; }
			$stmt->bind_param("ss", $like, $like);
		} else {
			$stmt = $con->prepare("SELECT `id`, `mid`, `merchantName` FROM merchants ORDER BY id DESC");
			if (!$stmt) { return $items; }
		}
		$stmt->execute();
		$result = $stmt->get_result();
		while ($row = mysqli_fetch_assoc($result)) {
			$item = $this->summaryItem($row["id"], $this->rowValue($row, "mid", ""), $this->rowValue($row, "merchantName", ""));
			$items[$item["merchantKey"]] = $item;
		}
		$stmt->close();
		return $items;
	}

	function loadCheckedBills($con, $keyword, $merchantId, $startDate, $endDate)
	{
		$where = "`checkedStatus` = 1";
		$params = array();
		$types = "";
		if ($merchantId > 0) {
			$where .= " and `merchantId` = ?";
			$params[] = $merchantId;
			$types .= "i";
		} else if ($keyword !== "") {
			$where .= " and (`merchantCode` LIKE ? or `merchantName` LIKE ?)";
			$like = "%".$keyword."%";
			$params[] = $like;
			$params[] = $like;
			$types .= "ss";
		}
		if ($startDate !== "") {
			$where .= " and COALESCE(`checkedTime`, `createdTime`) >= ?";
			$params[] = $this->normalizeDateTime($startDate, false);
			$types .= "s";
		}
		if ($endDate !== "") {
			$where .= " and COALESCE(`checkedTime`, `createdTime`) <= ?";
			$params[] = $this->normalizeDateTime($endDate, true);
			$types .= "s";
		}
		$sql = "SELECT `id`, `shareCode`, `merchantId`, `merchantCode`, `merchantName`, `totalAmount`, `checkedTime`, `createdTime` FROM bills WHERE ".$where." ORDER BY COALESCE(`checkedTime`, `createdTime`) DESC";
		return $this->fetchRows($con, $sql, $types, $params);
	}

	function loadPayments($con, $keyword, $merchantId, $startDate, $endDate)
	{
		$where = "1=1";
		$params = array();
		$types = "";
		if ($merchantId > 0) {
			$where .= " and `merchantId` = ?";
			$params[] = $merchantId;
			$types .= "i";
		} else if ($keyword !== "") {
			$where .= " and (`merchantCode` LIKE ? or `merchantName` LIKE ?)";
			$like = "%".$keyword."%";
			$params[] = $like;
			$params[] = $like;
			$types .= "ss";
		}
		if ($startDate !== "") {
			$where .= " and `paymentTime` >= ?";
			$params[] = $this->normalizeDateTime($startDate, false);
			$types .= "s";
		}
		if ($endDate !== "") {
			$where .= " and `paymentTime` <= ?";
			$params[] = $this->normalizeDateTime($endDate, true);
			$types .= "s";
		}
		$sql = "SELECT `id`, `merchantId`, `merchantCode`, `merchantName`, `amount`, `paymentTime`, `remark`, `createdTime` FROM merchant_payments WHERE ".$where." ORDER BY `paymentTime` DESC, `id` DESC";
		return $this->fetchRows($con, $sql, $types, $params);
	}

	function loadDebts($con, $keyword, $merchantId, $startDate, $endDate)
	{
		$where = "1=1";
		$params = array();
		$types = "";
		if ($merchantId > 0) {
			$where .= " and `merchantId` = ?";
			$params[] = $merchantId;
			$types .= "i";
		} else if ($keyword !== "") {
			$where .= " and (`merchantCode` LIKE ? or `merchantName` LIKE ?)";
			$like = "%".$keyword."%";
			$params[] = $like;
			$params[] = $like;
			$types .= "ss";
		}
		if ($startDate !== "") {
			$where .= " and `debtTime` >= ?";
			$params[] = $this->normalizeDateTime($startDate, false);
			$types .= "s";
		}
		if ($endDate !== "") {
			$where .= " and `debtTime` <= ?";
			$params[] = $this->normalizeDateTime($endDate, true);
			$types .= "s";
		}
		$sql = "SELECT `id`, `merchantId`, `merchantCode`, `merchantName`, `amount`, `debtTime`, `remark`, `createdTime` FROM merchant_debts WHERE ".$where." ORDER BY `debtTime` DESC, `id` DESC";
		return $this->fetchRows($con, $sql, $types, $params);
	}

	function ensureDebtTable($con)
	{
		$sql = "CREATE TABLE IF NOT EXISTS `merchant_debts` (
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
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Merchant debt records'";
		if (!mysqli_query($con, $sql)) {
			$this->echo_error(mysqli_error($con));
		}
	}

	function fetchRows($con, $sql, $types, $params)
	{
		$rows = array();
		$stmt = $con->prepare($sql);
		if (!$stmt) {
			$this->echo_error(mysqli_error($con));
		}
		if ($types !== "") {
			array_unshift($params, $types);
			call_user_func_array(array($stmt, "bind_param"), $this->refValues($params));
		}
		$stmt->execute();
		$result = $stmt->get_result();
		while ($row = mysqli_fetch_assoc($result)) {
			array_push($rows, $row);
		}
		$stmt->close();
		return $rows;
	}

	function findMerchant($con, $merchantId, $merchantCode)
	{
		if ($merchantId > 0) {
			$stmt = $con->prepare("SELECT `id`, `mid`, `merchantName` FROM merchants WHERE `id` = ? LIMIT 1");
			if (!$stmt) { return null; }
			$stmt->bind_param("i", $merchantId);
		} else if ($merchantCode !== "") {
			$stmt = $con->prepare("SELECT `id`, `mid`, `merchantName` FROM merchants WHERE `mid` = ? LIMIT 1");
			if (!$stmt) { return null; }
			$stmt->bind_param("s", $merchantCode);
		} else {
			return null;
		}
		$stmt->execute();
		$result = $stmt->get_result();
		$row = mysqli_fetch_assoc($result);
		$stmt->close();
		return $row ? $row : null;
	}

	function summaryItem($merchantId, $merchantCode, $merchantName)
	{
		return array(
			'merchantKey' => $this->merchantKey($merchantId, $merchantCode, $merchantName),
			'merchantId' => $merchantId,
			'merchantCode' => $merchantCode,
			'merchantName' => $merchantName,
			'billAmount' => 0,
			'paymentAmount' => 0,
			'debtAmount' => 0,
			'checkedBillCount' => 0
		);
	}

	function merchantKey($merchantId, $merchantCode, $merchantName)
	{
		if (intval($merchantId) > 0) {
			return "id:".intval($merchantId);
		}
		if (trim($merchantCode) !== "") {
			return "code:".trim($merchantCode);
		}
		return "name:".trim($merchantName);
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
