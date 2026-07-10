<?php
require_once __DIR__ . '/../ini.php';

class ExpressAudit
{
	function addExpressAudit()
	{
		$ini = new ini();
		$input = $this->input();
		$con = $this->connect($ini);
		$company = trim($this->inputValue($input, "expressCompany", ""));
		$extraFee = floatval($this->inputValue($input, "extraFee", 0));
		$rows = $this->inputValue($input, "rows", array());
		if ($company === "" || !is_array($rows) || count($rows) === 0) {
			$this->echo_error('empty params');
		}

		$cleanRows = array();
		foreach ($rows as $row) {
			if (!is_array($row)) { continue; }
			$waybill = trim($this->arrayValue($row, "waybillNumber", ""));
			if ($waybill === "" || $this->looksLikeTotalRow($row)) { continue; }
			$cleanRows[] = array(
				'waybillNumber' => $waybill,
				'billDate' => $this->normalizeDateTime($this->arrayValue($row, "billDate", ""), false),
				'weight' => $this->numberValue($this->arrayValue($row, "weight", 0)),
				'actualFee' => $this->numberValue($this->arrayValue($row, "actualFee", 0)),
				'province' => trim($this->arrayValue($row, "province", "")),
				'rawData' => $this->jsonValue($row)
			);
		}
		if (count($cleanRows) === 0) {
			$this->echo_error('没有可上传的有效运单');
		}

		$taskDate = $this->taskDate($cleanRows);
		$stmt = $con->prepare("INSERT INTO express_audit_tasks (`taskDate`, `expressCompany`, `extraFee`, `totalCount`, `checkedCount`, `suspiciousCount`, `redCount`, `yellowCount`, `greenCount`, `deviationPercent`, `createdTime`, `updatedTime`) VALUES (?, ?, ?, ?, 0, 0, 0, 0, 0, 0, NOW(), NOW())");
		if (!$stmt) { $this->echo_error(mysqli_error($con)); }
		$totalCount = count($cleanRows);
		$stmt->bind_param("ssdi", $taskDate, $company, $extraFee, $totalCount);
		if (!$stmt->execute()) { $this->echo_error(mysqli_error($con)); }
		$taskId = mysqli_insert_id($con);
		$stmt->close();

		foreach ($cleanRows as $row) {
			$checked = $this->checkRow($con, $company, $extraFee, $row);
			$this->insertItem($con, $taskId, $row, $checked);
		}
		$this->recalcTask($con, $taskId);
		@mysqli_close($con);
		echo json_encode(array('success' => true, 'id' => $taskId));
		exit;
	}

	function getExpressAudits()
	{
		$ini = new ini();
		$con = $this->connect($ini);
		$tasks = array();
		$result = mysqli_query($con, "SELECT * FROM express_audit_tasks ORDER BY id DESC");
		if (!$result) { $this->echo_error(mysqli_error($con)); }
		while ($row = mysqli_fetch_assoc($result)) {
			$tasks[] = $this->taskRow($row);
		}
		@mysqli_close($con);
		echo json_encode(array('success' => true, 'data' => $tasks));
		exit;
	}

	function getExpressAuditDetail()
	{
		$ini = new ini();
		$input = $this->input();
		$con = $this->connect($ini);
		$id = intval($this->inputValue($input, "id", 0));
		if ($id <= 0) { $this->echo_error('empty params'); }
		$task = $this->findTask($con, $id);
		if (!$task) { $this->echo_error('task not found'); }
		$items = array();
		$stmt = $con->prepare("SELECT * FROM express_audit_items WHERE taskId = ? ORDER BY id ASC");
		if (!$stmt) { $this->echo_error(mysqli_error($con)); }
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$result = $stmt->get_result();
		while ($row = mysqli_fetch_assoc($result)) {
			$items[] = $this->itemRow($row);
		}
		$stmt->close();
		@mysqli_close($con);
		echo json_encode(array('success' => true, 'task' => $this->taskRow($task), 'items' => $items));
		exit;
	}

	function updateExpressAuditItem()
	{
		$ini = new ini();
		$input = $this->input();
		$con = $this->connect($ini);
		$itemId = intval($this->inputValue($input, "itemId", 0));
		$markStatus = trim($this->inputValue($input, "markStatus", ""));
		$remark = trim($this->inputValue($input, "remark", ""));
		if ($itemId <= 0 || !in_array($markStatus, array('red', 'yellow', 'green'))) {
			$this->echo_error('empty params');
		}
		$stmt = $con->prepare("UPDATE express_audit_items SET `markStatus` = ?, `manualStatus` = ?, `remark` = ? WHERE `id` = ?");
		if (!$stmt) { $this->echo_error(mysqli_error($con)); }
		$stmt->bind_param("sssi", $markStatus, $markStatus, $remark, $itemId);
		if (!$stmt->execute()) { $this->echo_error(mysqli_error($con)); }
		$stmt->close();
		$taskId = $this->taskIdByItem($con, $itemId);
		if ($taskId > 0) { $this->recalcTask($con, $taskId); }
		@mysqli_close($con);
		echo json_encode(array('success' => true));
		exit;
	}

	function delExpressAudit()
	{
		$ini = new ini();
		$input = $this->input();
		$con = $this->connect($ini);
		$id = intval($this->inputValue($input, "id", 0));
		if ($id <= 0) { $this->echo_error('empty params'); }
		$stmt = $con->prepare("DELETE FROM express_audit_tasks WHERE id = ?");
		if (!$stmt) { $this->echo_error(mysqli_error($con)); }
		$stmt->bind_param("i", $id);
		if (!$stmt->execute()) { $this->echo_error(mysqli_error($con)); }
		$stmt->close();
		@mysqli_close($con);
		echo json_encode(array('success' => true));
		exit;
	}

	function checkRow($con, $company, $extraFee, $row)
	{
		$order = $this->findOrderByWaybill($con, $row['waybillNumber']);
		if (!$order) {
			return array('orderId'=>'', 'receiverProvince'=>'', 'orderWeight'=>0, 'expectedFee'=>0, 'deviationPercent'=>100, 'markStatus'=>'red', 'reason'=>'非我方发货');
		}
		$beans = $this->findBeans($con, $this->arrayValue($order, "orderId", ""));
		$orderWeight = $this->sumWeight($con, $beans);
		$province = $row['province'] !== "" ? $row['province'] : $this->arrayValue($order, "receiverProvince", "");
		$weight = $row['weight'] > 0 ? $row['weight'] : $orderWeight;
		$expectedFee = $this->expectedFee($con, $company, $province, $weight) + $extraFee;
		$deviation = $expectedFee > 0 ? (($row['actualFee'] - $expectedFee) / $expectedFee * 100) : ($row['actualFee'] == 0 ? 0 : 100);
		$mark = abs($deviation) > 20 ? 'yellow' : 'green';
		return array(
			'orderId' => $this->arrayValue($order, "orderId", ""),
			'receiverProvince' => $province,
			'orderWeight' => $orderWeight,
			'expectedFee' => $expectedFee,
			'deviationPercent' => $deviation,
			'markStatus' => $mark,
			'reason' => $mark === 'yellow' ? '金额偏差超过20%' : '正常'
		);
	}

	function expectedFee($con, $company, $province, $weight)
	{
		$express = $this->findExpress($con, $company);
		if (!$express) { return 0; }
		$fees = json_decode($this->arrayValue($express, "feeRules", "[]"), true);
		if (!is_array($fees)) { return 0; }
		$matched = null;
		foreach ($fees as $fee) {
			$names = preg_split('/[、,，\s]+/u', $this->arrayValue($fee, "provinces", $this->arrayValue($fee, "province", "")));
			if (in_array($province, $names)) {
				$matched = $fee;
				break;
			}
		}
		if (!$matched) { return 0; }
		$w = floatval($weight);
		if ($w <= 1) { return $this->numberValue($this->arrayValue($matched, "kg1", 0)); }
		if ($w <= 2) { return $this->numberValue($this->arrayValue($matched, "kg2", 0)); }
		if ($w <= 3) { return $this->numberValue($this->arrayValue($matched, "kg3", 0)); }
		if ($w <= 4) { return $this->numberValue($this->arrayValue($matched, "kg4", 0)); }
		return $this->numberValue($this->arrayValue($matched, "kg4", 0)) + ceil($w - 4) * $this->numberValue($this->arrayValue($matched, "renew", $this->arrayValue($matched, "continuePrice", 0)));
	}

	function findExpress($con, $company)
	{
		$stmt = $con->prepare("SELECT * FROM expresses WHERE expressName = ? LIMIT 1");
		if (!$stmt) { return null; }
		$stmt->bind_param("s", $company);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = mysqli_fetch_assoc($result);
		$stmt->close();
		return $row ? $row : null;
	}

	function findOrderByWaybill($con, $waybill)
	{
		$like = "%".$waybill."%";
		$stmt = $con->prepare("SELECT * FROM orders WHERE waybillNumber = ? OR waybillNumber LIKE ? LIMIT 1");
		if (!$stmt) { return null; }
		$stmt->bind_param("ss", $waybill, $like);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = mysqli_fetch_assoc($result);
		$stmt->close();
		return $row ? $row : null;
	}

	function findBeans($con, $orderId)
	{
		$beans = array();
		$stmt = $con->prepare("SELECT * FROM beans WHERE parentOrderId = ? OR orderId = ?");
		if (!$stmt) { return $beans; }
		$stmt->bind_param("ss", $orderId, $orderId);
		$stmt->execute();
		$result = $stmt->get_result();
		while ($row = mysqli_fetch_assoc($result)) { $beans[] = $row; }
		$stmt->close();
		return $beans;
	}

	function insertItem($con, $taskId, $row, $checked)
	{
		$stmt = $con->prepare("INSERT INTO express_audit_items (`taskId`, `waybillNumber`, `billDate`, `weight`, `actualFee`, `province`, `orderId`, `orderWeight`, `expectedFee`, `deviationPercent`, `markStatus`, `reason`, `rawData`, `createdTime`, `updatedTime`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
		if (!$stmt) { $this->echo_error(mysqli_error($con)); }
		$stmt->bind_param("issddssdddsss", $taskId, $row['waybillNumber'], $row['billDate'], $row['weight'], $row['actualFee'], $row['province'], $checked['orderId'], $checked['orderWeight'], $checked['expectedFee'], $checked['deviationPercent'], $checked['markStatus'], $checked['reason'], $row['rawData']);
		if (!$stmt->execute()) { $this->echo_error(mysqli_error($con)); }
		$stmt->close();
	}

	function recalcTask($con, $taskId)
	{
		$stmt = $con->prepare("SELECT COUNT(*) totalCount, SUM(markStatus='red') redCount, SUM(markStatus='yellow') yellowCount, SUM(markStatus='green') greenCount, AVG(ABS(deviationPercent)) deviationPercent FROM express_audit_items WHERE taskId = ?");
		if (!$stmt) { return; }
		$stmt->bind_param("i", $taskId);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = mysqli_fetch_assoc($result);
		$stmt->close();
		if (!$row) { return; }
		$total = intval($this->arrayValue($row, "totalCount", 0));
		$red = intval($this->arrayValue($row, "redCount", 0));
		$yellow = intval($this->arrayValue($row, "yellowCount", 0));
		$green = intval($this->arrayValue($row, "greenCount", 0));
		$suspicious = $red + $yellow;
		$checked = $total;
		$deviation = $this->numberValue($this->arrayValue($row, "deviationPercent", 0));
		$update = $con->prepare("UPDATE express_audit_tasks SET totalCount=?, checkedCount=?, suspiciousCount=?, redCount=?, yellowCount=?, greenCount=?, deviationPercent=?, updatedTime=NOW() WHERE id=?");
		if (!$update) { return; }
		$update->bind_param("iiiiiidi", $total, $checked, $suspicious, $red, $yellow, $green, $deviation, $taskId);
		$update->execute();
		$update->close();
	}

	function taskDate($rows)
	{
		foreach ($rows as $row) {
			if ($row['billDate'] !== null && $row['billDate'] !== "") {
				$date = substr($row['billDate'], 0, 10);
				if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
					return $date;
				}
			}
		}
		return date('Y-m-d');
	}

	function looksLikeTotalRow($row)
	{
		$text = implode(' ', array_map('strval', $row));
		return preg_match('/合计|总计|小计|total/i', $text) && trim($this->arrayValue($row, "waybillNumber", "")) === "";
	}

	function sumWeight($con, $beans)
	{
		$sum = 0;
		foreach ($beans as $bean) {
			$actual = $this->numberValue($this->arrayValue($bean, "weightActual", 0));
			if ($actual > 0) {
				$sum += $actual;
			} else {
				$sum += $this->productWeight($con, $this->arrayValue($bean, "sku", "")) * max(1, $this->numberValue($this->arrayValue($bean, "total", 1)));
			}
		}
		return $sum;
	}

	function productWeight($con, $sku)
	{
		$skuText = trim($sku);
		if ($skuText === "") { return 0; }
		$result = mysqli_query($con, "SELECT productName, matchRule, weight FROM products ORDER BY id DESC");
		if (!$result) { return 0; }
		while ($row = mysqli_fetch_assoc($result)) {
			$rule = trim($this->arrayValue($row, "matchRule", ""));
			$name = trim($this->arrayValue($row, "productName", ""));
			if ($rule !== "" && @preg_match('/'.$rule.'/iu', $skuText)) {
				return $this->numberValue($this->arrayValue($row, "weight", 0));
			}
			if ($name !== "" && (stripos($skuText, $name) !== false || stripos($name, $skuText) !== false)) {
				return $this->numberValue($this->arrayValue($row, "weight", 0));
			}
		}
		return 0;
	}

	function taskIdByItem($con, $itemId)
	{
		$stmt = $con->prepare("SELECT taskId FROM express_audit_items WHERE id = ? LIMIT 1");
		if (!$stmt) { return 0; }
		$stmt->bind_param("i", $itemId);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = mysqli_fetch_assoc($result);
		$stmt->close();
		return $row ? intval($row["taskId"]) : 0;
	}

	function findTask($con, $id)
	{
		$stmt = $con->prepare("SELECT * FROM express_audit_tasks WHERE id = ? LIMIT 1");
		if (!$stmt) { return null; }
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = mysqli_fetch_assoc($result);
		$stmt->close();
		return $row ? $row : null;
	}

	function taskRow($row)
	{
		return array(
			'id' => $this->arrayValue($row, "id", ""),
			'taskDate' => $this->arrayValue($row, "taskDate", ""),
			'expressCompany' => $this->arrayValue($row, "expressCompany", ""),
			'extraFee' => $this->arrayValue($row, "extraFee", "0"),
			'totalCount' => $this->arrayValue($row, "totalCount", "0"),
			'checkedCount' => $this->arrayValue($row, "checkedCount", "0"),
			'suspiciousCount' => $this->arrayValue($row, "suspiciousCount", "0"),
			'redCount' => $this->arrayValue($row, "redCount", "0"),
			'yellowCount' => $this->arrayValue($row, "yellowCount", "0"),
			'greenCount' => $this->arrayValue($row, "greenCount", "0"),
			'deviationPercent' => $this->arrayValue($row, "deviationPercent", "0"),
			'createdTime' => $this->arrayValue($row, "createdTime", ""),
			'updatedTime' => $this->arrayValue($row, "updatedTime", "")
		);
	}

	function itemRow($row)
	{
		return array(
			'id' => $this->arrayValue($row, "id", ""),
			'taskId' => $this->arrayValue($row, "taskId", ""),
			'waybillNumber' => $this->arrayValue($row, "waybillNumber", ""),
			'billDate' => $this->arrayValue($row, "billDate", ""),
			'weight' => $this->arrayValue($row, "weight", "0"),
			'actualFee' => $this->arrayValue($row, "actualFee", "0"),
			'province' => $this->arrayValue($row, "province", ""),
			'orderId' => $this->arrayValue($row, "orderId", ""),
			'orderWeight' => $this->arrayValue($row, "orderWeight", "0"),
			'expectedFee' => $this->arrayValue($row, "expectedFee", "0"),
			'deviationPercent' => $this->arrayValue($row, "deviationPercent", "0"),
			'markStatus' => $this->arrayValue($row, "markStatus", ""),
			'manualStatus' => $this->arrayValue($row, "manualStatus", ""),
			'reason' => $this->arrayValue($row, "reason", ""),
			'remark' => $this->arrayValue($row, "remark", "")
		);
	}

	function connect($ini)
	{
		$con = @mysqli_connect($ini->mySqlServer, $ini->mySqlUser, $ini->mySqlPass);
		if (!$con) { $this->echo_error('connect mysql error'); }
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

	function arrayValue($row, $key, $default = "")
	{
		return is_array($row) && array_key_exists($key, $row) ? $row[$key] : $default;
	}

	function numberValue($value)
	{
		$value = preg_replace('/[^0-9.\-]/', '', strval($value));
		return is_numeric($value) ? floatval($value) : 0;
	}

	function jsonValue($value)
	{
		return json_encode($value, JSON_UNESCAPED_UNICODE);
	}

	function normalizeDateTime($value, $isEnd)
	{
		$value = trim(str_replace('号', '日', str_replace('/', '-', str_replace('T', ' ', strval($value)))));
		$value = preg_replace('/\s+/', ' ', $value);
		if ($value === "") { return null; }
		if (preg_match('/^\d+(\.\d+)?$/', $value)) { return null; }
		if (preg_match('/^(\d{4})年\s*(\d{1,2})月\s*(\d{1,2})日?(?:\s+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/u', $value, $m)) {
			return $this->dateParts($m[1], $m[2], $m[3], isset($m[4]) ? $m[4] : null, isset($m[5]) ? $m[5] : null, isset($m[6]) ? $m[6] : null, $isEnd);
		}
		if (preg_match('/^(\d{1,2})月\s*(\d{1,2})日?(?:\s+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/u', $value, $m)) {
			return $this->dateParts(date('Y'), $m[1], $m[2], isset($m[3]) ? $m[3] : null, isset($m[4]) ? $m[4] : null, isset($m[5]) ? $m[5] : null, $isEnd);
		}
		if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $m)) {
			return $this->dateParts($m[1], $m[2], $m[3], null, null, null, $isEnd);
		}
		if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})\s+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?/', $value, $m)) {
			return $this->dateParts($m[1], $m[2], $m[3], $m[4], $m[5], isset($m[6]) ? $m[6] : null, $isEnd);
		}
		return null;
	}

	function dateParts($year, $month, $day, $hour, $minute, $second, $isEnd)
	{
		$year = intval($year);
		$month = intval($month);
		$day = intval($day);
		if ($year < 1970 || $month < 1 || $month > 12 || $day < 1 || $day > 31 || !checkdate($month, $day, $year)) {
			return null;
		}
		$hour = $hour === null || $hour === "" ? ($isEnd ? 23 : 0) : intval($hour);
		$minute = $minute === null || $minute === "" ? ($isEnd ? 59 : 0) : intval($minute);
		$second = $second === null || $second === "" ? ($isEnd ? 59 : 0) : intval($second);
		return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
	}

	function echo_error($message)
	{
		echo json_encode(array('success' => false, 'txt' => $message));
		exit;
	}
}
?>
