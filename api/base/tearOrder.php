<?php
require_once __DIR__ . '/../ini.php';

class TearOrder
{
	function getTearOrders()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		$con = $this->connect($ini);
		$keyword = trim($this->inputValue($input, "keyword", ""));
		$actionType = trim($this->inputValue($input, "actionType", ""));
		$params = array();
		$types = "";
		$where = "1=1";
		if ($keyword !== "") {
			$where .= " AND (`expressName` LIKE ? OR `waybillNumber` LIKE ? OR `remark` LIKE ?)";
			$like = "%".$keyword."%";
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$types .= "sss";
		}
		if ($actionType !== "" && $actionType !== "all") {
			$where .= " AND `actionType` = ?";
			$params[] = $actionType;
			$types .= "s";
		}
		$sql = "SELECT * FROM tear_orders WHERE ".$where." ORDER BY actionTime DESC, id DESC LIMIT 50000";
		$stmt = $con->prepare($sql);
		if (!$stmt) {
			$this->echo_error(mysqli_error($con));
		}
		if (count($params) > 0) {
			$stmt->bind_param($types, ...$params);
		}
		$stmt->execute();
		$result = $stmt->get_result();
		$rows = array();
		while ($row = mysqli_fetch_array($result)) {
			$rows[] = array(
				'id' => $row["id"],
				'expressName' => $this->rowValue($row, "expressName", ""),
				'waybillNumber' => $this->rowValue($row, "waybillNumber", ""),
				'actionType' => $this->rowValue($row, "actionType", ""),
				'actionTime' => $this->rowValue($row, "actionTime", ""),
				'remark' => $this->rowValue($row, "remark", ""),
				'createdTime' => $this->rowValue($row, "createdTime", ""),
				'updatedTime' => $this->rowValue($row, "updatedTime", "")
			);
		}
		$stmt->close();
		echo json_encode(array('success' => true, 'data' => $rows));
		@mysqli_close($con);
		exit;
	}

	function updateTearOrder()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		$con = $this->connect($ini);
		$records = isset($input["records"]) && is_array($input["records"]) ? $input["records"] : array($input);
		$saved = 0;
		foreach ($records as $record) {
			if ($this->saveOne($con, $record)) {
				$saved += 1;
			}
		}
		echo json_encode(array('success' => true, 'saved' => $saved));
		@mysqli_close($con);
		exit;
	}

	function delTearOrder()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		$con = $this->connect($ini);
		$id = trim($this->inputValue($input, "id", ""));
		$waybillNumber = $this->normalizeWaybill($this->inputValue($input, "waybillNumber", ""));
		if ($id === "" && $waybillNumber === "") {
			$this->echo_error('empty params');
		}
		if ($id !== "") {
			$stmt = $con->prepare("DELETE FROM tear_orders WHERE `id` = ?");
			if (!$stmt) {
				$this->echo_error(mysqli_error($con));
			}
			$stmt->bind_param("i", $id);
		} else {
			$stmt = $con->prepare("DELETE FROM tear_orders WHERE `waybillNumber` = ?");
			if (!$stmt) {
				$this->echo_error(mysqli_error($con));
			}
			$stmt->bind_param("s", $waybillNumber);
		}
		$stmt->execute();
		$stmt->close();
		echo json_encode(array('success' => true));
		@mysqli_close($con);
		exit;
	}

	function saveOne($con, $input)
	{
		$id = trim($this->inputValue($input, "id", ""));
		$expressName = trim($this->inputValue($input, "expressName", $this->inputValue($input, "express_name", "")));
		$waybillNumber = $this->normalizeWaybill($this->inputValue($input, "waybillNumber", $this->inputValue($input, "waybill_number", "")));
		$actionType = $this->normalizeActionType($this->inputValue($input, "actionType", $this->inputValue($input, "action_type", "撕单")));
		$actionTime = $this->normalizeDateTime($this->inputValue($input, "actionTime", $this->inputValue($input, "action_time", "")));
		$remark = trim($this->inputValue($input, "remark", ""));
		if ($waybillNumber === "") {
			return false;
		}
		if ($id !== "") {
			$stmt = $con->prepare("UPDATE tear_orders SET `expressName` = ?, `waybillNumber` = ?, `actionType` = ?, `actionTime` = ?, `remark` = ?, `updatedTime` = NOW() WHERE `id` = ?");
			if (!$stmt) { $this->echo_error(mysqli_error($con)); }
			$stmt->bind_param("sssssi", $expressName, $waybillNumber, $actionType, $actionTime, $remark, $id);
		} else {
			$stmt = $con->prepare("INSERT INTO tear_orders (`expressName`, `waybillNumber`, `actionType`, `actionTime`, `remark`, `createdTime`, `updatedTime`) VALUES (?, ?, ?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE `expressName` = VALUES(`expressName`), `actionType` = VALUES(`actionType`), `actionTime` = VALUES(`actionTime`), `remark` = VALUES(`remark`), `updatedTime` = NOW()");
			if (!$stmt) { $this->echo_error(mysqli_error($con)); }
			$stmt->bind_param("sssss", $expressName, $waybillNumber, $actionType, $actionTime, $remark);
		}
		$stmt->execute();
		$stmt->close();
		return true;
	}

	function connect($ini)
	{
		$con = @mysqli_connect($ini->mySqlServer, $ini->mySqlUser, $ini->mySqlPass);
		if (!$con) {
			$this->echo_error('连接数据库发生错误：“' . mysqli_error() . "。”");
		}
		mysqli_select_db($con, $ini->mySqlDataBase);
		mysqli_query($con, "SET NAMES 'UTF8'");
		return $con;
	}

	function inputValue($input, $key, $default = "")
	{
		return isset($input[$key]) ? $input[$key] : $default;
	}

	function rowValue($row, $key, $default = "")
	{
		return array_key_exists($key, $row) ? $row[$key] : $default;
	}

	function normalizeWaybill($value)
	{
		return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', strval($value)));
	}

	function normalizeActionType($value)
	{
		$text = trim(strval($value));
		return strpos($text, '拦截') !== false ? '拦截' : '撕单';
	}

	function normalizeDateTime($value)
	{
		$text = trim(strval($value));
		if ($text === "") {
			return date('Y-m-d H:i:s');
		}
		$time = strtotime(str_replace('/', '-', $text));
		return $time ? date('Y-m-d H:i:s', $time) : date('Y-m-d H:i:s');
	}

	function echo_error($message)
	{
		echo json_encode(array('success' => false, 'txt' => $message));
		exit;
	}
}
?>
