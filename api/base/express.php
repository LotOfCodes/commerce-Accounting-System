<?php
require_once __DIR__ . '/../ini.php';

class Express
{
	function addExpress()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		$con = $this->connect($ini);
		$SQLArray = $this->expressSqlArray($input);
		mysqli_query($con, "insert into expresses " . $this->ArraytoSQLaddstr($con, $SQLArray)) or die($this->echo_error(mysqli_error($con)));
		echo json_encode(array('success' => true));
		@mysqli_close($con);
		exit;
	}

	function updateExpress()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		$id = isset($input["id"]) ? $input["id"] : "";
		if ($id === "") {
			$this->echo_error('empty params');
		}
		$con = $this->connect($ini);
		$SQLArray = $this->expressSqlArray($input);
		mysqli_query($con, "UPDATE `expresses` set " . $this->ArraytoSQLstr($con, $SQLArray) . " where `id`='" . $this->escape($con, $id) . "'") or die($this->echo_error(mysqli_error($con)));
		echo json_encode(array('success' => true));
		@mysqli_close($con);
		exit;
	}

	function getExpresses()
	{
		$ini = new ini();
		$con = $this->connect($ini);
		$expresses = array();
		$sql = "SELECT * FROM `expresses` ORDER BY id DESC";
		$stmt = $con->prepare($sql);
		if ($stmt) {
			$stmt->execute();
			$result = $stmt->get_result();
			if (!$result) {
				$this->echo_error($sql);
				@mysqli_close($con);
				exit;
			}
			if (mysqli_num_rows($result) > 0) {
				while ($row = mysqli_fetch_array($result)) {
					array_push($expresses, array(
						'id' => $row["id"],
						'expressName' => $this->rowValue($row, "expressName", ""),
						'templateCode' => $this->rowValue($row, "templateCode", ""),
						'regexRules' => $this->rowValue($row, "regexRules", ""),
						'feeRules' => $this->rowValue($row, "feeRules", ""),
						'settleTarget' => $this->rowValue($row, "settleTarget", ""),
						'freightPayer' => $this->rowValue($row, "freightPayer", ""),
						'status' => $this->rowValue($row, "status", ""),
						'remoteNote' => $this->rowValue($row, "remoteNote", ""),
						'remark' => $this->rowValue($row, "remark", ""),
						'createdTime' => $this->rowValue($row, "createdTime", ""),
						'updatedTime' => $this->rowValue($row, "updatedTime", "")
					));
				}
			}
			echo json_encode(array('success' => true, 'data' => $expresses));
			$stmt->close();
			exit;
		}
		$this->echo_error('error');
	}

	function delExpress()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		$id = isset($input["id"]) ? $input["id"] : "";
		if ($id === "") {
			$this->echo_error('empty params');
		}
		$con = $this->connect($ini);
		$sql_del = "DELETE FROM expresses WHERE `id`= ?";
		$stmt = $con->prepare($sql_del);
		if ($stmt) {
			$stmt->bind_param("s", $id);
			$stmt->execute();
			$stmt->close();
			echo json_encode(array('success' => true));
			exit;
		}
		$this->echo_error('error');
	}

	function connect($ini)
	{
		$con = @mysqli_connect($ini->mySqlServer, $ini->mySqlUser, $ini->mySqlPass);
		if (!$con) {
			$this->echo_error('连接数据库发生错误：“' . mysqli_error() . "。”");
			@mysqli_close($con);
			exit;
		}
		mysqli_select_db($con, $ini->mySqlDataBase);
		mysqli_query($con, "SET NAMES 'UTF8'");
		return $con;
	}

	function expressSqlArray($input)
	{
		return array(
			'expressName' => $this->inputValue($input, "expressName", $this->inputValue($input, "express_name", "")),
			'templateCode' => $this->inputValue($input, "templateCode", $this->inputValue($input, "template_code", "")),
			'regexRules' => $this->inputValue($input, "regexRules", $this->inputValue($input, "regex_rules", "")),
			'feeRules' => $this->inputValue($input, "feeRules", $this->inputValue($input, "fee_rules", "")),
			'settleTarget' => $this->inputValue($input, "settleTarget", $this->inputValue($input, "settle_target", "")),
			'freightPayer' => $this->inputValue($input, "freightPayer", $this->inputValue($input, "freight_payer", "")),
			'status' => $this->inputValue($input, "status", "有效"),
			'remoteNote' => $this->inputValue($input, "remoteNote", $this->inputValue($input, "remote_note", "")),
			'remark' => $this->inputValue($input, "remark", "")
		);
	}

	function inputValue($input, $key, $default = "")
	{
		return isset($input[$key]) ? $input[$key] : $default;
	}

	function rowValue($row, $key, $default = "")
	{
		return array_key_exists($key, $row) ? $row[$key] : $default;
	}

	function escape($con, $value)
	{
		return mysqli_real_escape_string($con, $value);
	}

	function sqlValue($con, $value)
	{
		if ($value === null || $value === '') {
			return 'NULL';
		}
		return "'" . $this->escape($con, $value) . "'";
	}

	function ArraytoSQLstr($con, $arra)
	{
		$tempstr = '';
		$num = 1;
		foreach ($arra as $key => $value) {
			if ($num == 1) {
				$tempstr = $key . " = " . $this->sqlValue($con, $value) . " ";
			} else {
				$tempstr = $tempstr . ", " . $key . " = " . $this->sqlValue($con, $value);
			}
			$num++;
		}
		return $tempstr;
	}

	function ArraytoSQLaddstr($con, $arra)
	{
		$names = '';
		$values = '';
		$num = 1;
		foreach ($arra as $key => $value) {
			if ($num == 1) {
				$names = $key;
				$values = $this->sqlValue($con, $value);
			} else {
				$names = $names . "," . $key;
				$values = $values . "," . $this->sqlValue($con, $value);
			}
			$num++;
		}
		return "(" . $names . ") VALUES (" . $values . ")";
	}

	function echo_error($message)
	{
		echo json_encode(array('success' => false, 'txt' => $message));
		exit;
	}
}
?>
