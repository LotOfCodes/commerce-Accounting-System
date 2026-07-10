<?php
require_once __DIR__ . '/../ini.php';

class Merchant
{
	function getMerchants()
	{
		$ini = new ini();
		$input = $this->input();
		$con = $this->connect($ini);
		$keyword = isset($input["keyword"]) ? trim($input["keyword"]) : "";
		$merchants = array();

		if ($keyword !== "") {
			$like = "%".$keyword."%";
			$sql = "SELECT DISTINCT m.* FROM merchants m LEFT JOIN merchant_shops s ON m.id = s.merchantId WHERE m.merchantName LIKE ? OR m.mid LIKE ? OR s.shopName LIKE ? OR s.shopCode LIKE ? ORDER BY m.id DESC";
			$stmt = $con->prepare($sql);
			if (!$stmt) {
				$this->echo_error(mysqli_error($con));
			}
			$stmt->bind_param("ssss", $like, $like, $like, $like);
		} else {
			$stmt = $con->prepare("SELECT * FROM merchants ORDER BY id DESC");
			if (!$stmt) {
				$this->echo_error(mysqli_error($con));
			}
		}

		$stmt->execute();
		$result = $stmt->get_result();
		while ($row = mysqli_fetch_assoc($result)) {
			$item = array(
				'id' => $row["id"],
				'mid' => $this->rowValue($row, "mid", ""),
				'merchantName' => $this->rowValue($row, "merchantName", ""),
				'shops' => $this->getMerchantShops($con, $row["id"])
			);
			array_push($merchants, $item);
		}
		$stmt->close();
		@mysqli_close($con);
		echo json_encode(array('success' => true, 'data' => $merchants));
		exit;
	}

	function addMerchant()
	{
		$ini = new ini();
		$input = $this->input();
		$con = $this->connect($ini);
		$merchantName = $this->inputValue($input, "merchantName", "");
		$mid = $this->inputValue($input, "mid", "");
		$shops = $this->shopsFromInput($input);

		if (trim($merchantName) === "") {
			$this->echo_error('merchantName is required');
		}
		if (trim($mid) === "") {
			$mid = $this->makeMerchantCode($con);
		}

		mysqli_begin_transaction($con);
		$stmt = $con->prepare("INSERT INTO merchants (`mid`, `merchantName`) VALUES (?, ?)");
		if (!$stmt) {
			$this->rollback_error($con, mysqli_error($con));
		}
		$stmt->bind_param("ss", $mid, $merchantName);
		if (!$stmt->execute()) {
			$this->rollback_error($con, mysqli_error($con));
		}
		$merchantId = mysqli_insert_id($con);
		$stmt->close();
		$this->saveMerchantShops($con, $merchantId, $shops);
		mysqli_commit($con);
		@mysqli_close($con);
		echo json_encode(array('success' => true, 'id' => $merchantId, 'mid' => $mid));
		exit;
	}

	function updateMerchant()
	{
		$ini = new ini();
		$input = $this->input();
		$con = $this->connect($ini);
		$id = $this->inputValue($input, "id", "");
		$merchantName = $this->inputValue($input, "merchantName", "");
		$mid = $this->inputValue($input, "mid", "");
		$shops = $this->shopsFromInput($input);

		if ($id === "") {
			$this->echo_error('empty params');
		}
		if (trim($merchantName) === "") {
			$this->echo_error('merchantName is required');
		}
		if (trim($mid) === "") {
			$mid = "M".str_pad($id, 5, "0", STR_PAD_LEFT);
		}

		mysqli_begin_transaction($con);
		$stmt = $con->prepare("UPDATE merchants SET `mid` = ?, `merchantName` = ? WHERE `id` = ?");
		if (!$stmt) {
			$this->rollback_error($con, mysqli_error($con));
		}
		$stmt->bind_param("ssi", $mid, $merchantName, $id);
		if (!$stmt->execute()) {
			$this->rollback_error($con, mysqli_error($con));
		}
		$stmt->close();

		$del = $con->prepare("DELETE FROM merchant_shops WHERE `merchantId` = ?");
		if (!$del) {
			$this->rollback_error($con, mysqli_error($con));
		}
		$del->bind_param("i", $id);
		if (!$del->execute()) {
			$this->rollback_error($con, mysqli_error($con));
		}
		$del->close();
		$this->saveMerchantShops($con, $id, $shops);
		mysqli_commit($con);
		@mysqli_close($con);
		echo json_encode(array('success' => true));
		exit;
	}

	function delMerchant()
	{
		$ini = new ini();
		$input = $this->input();
		$con = $this->connect($ini);
		$id = $this->inputValue($input, "id", "");
		if ($id === "") {
			$this->echo_error('empty params');
		}

		mysqli_begin_transaction($con);
		$stmt = $con->prepare("DELETE FROM merchant_shops WHERE `merchantId` = ?");
		if (!$stmt) {
			$this->rollback_error($con, mysqli_error($con));
		}
		$stmt->bind_param("i", $id);
		if (!$stmt->execute()) {
			$this->rollback_error($con, mysqli_error($con));
		}
		$stmt->close();

		$stmt = $con->prepare("DELETE FROM merchants WHERE `id` = ?");
		if (!$stmt) {
			$this->rollback_error($con, mysqli_error($con));
		}
		$stmt->bind_param("i", $id);
		if (!$stmt->execute()) {
			$this->rollback_error($con, mysqli_error($con));
		}
		$stmt->close();
		mysqli_commit($con);
		@mysqli_close($con);
		echo json_encode(array('success' => true));
		exit;
	}

	function getMerchantShops($con, $merchantId)
	{
		$shops = array();
		$stmt = $con->prepare("SELECT `id`, `shopCode`, `shopName` FROM merchant_shops WHERE `merchantId` = ? ORDER BY id ASC");
		if (!$stmt) {
			return $shops;
		}
		$stmt->bind_param("i", $merchantId);
		$stmt->execute();
		$result = $stmt->get_result();
		while ($row = mysqli_fetch_assoc($result)) {
			array_push($shops, array(
				'id' => $row["id"],
				'shopCode' => $this->rowValue($row, "shopCode", ""),
				'shopName' => $this->rowValue($row, "shopName", "")
			));
		}
		$stmt->close();
		return $shops;
	}

	function saveMerchantShops($con, $merchantId, $shops)
	{
		if (!is_array($shops) || count($shops) === 0) {
			return;
		}
		$stmt = $con->prepare("INSERT INTO merchant_shops (`merchantId`, `shopCode`, `shopName`) VALUES (?, ?, ?)");
		if (!$stmt) {
			$this->rollback_error($con, mysqli_error($con));
		}
		foreach ($shops as $shop) {
			$shopCode = isset($shop["shopCode"]) ? trim($shop["shopCode"]) : "";
			$shopName = isset($shop["shopName"]) ? trim($shop["shopName"]) : "";
			if ($shopCode === "" && $shopName === "") {
				continue;
			}
			$stmt->bind_param("iss", $merchantId, $shopCode, $shopName);
			if (!$stmt->execute()) {
				$this->rollback_error($con, mysqli_error($con));
			}
		}
		$stmt->close();
	}

	function shopsFromInput($input)
	{
		if (isset($input["shops"]) && is_array($input["shops"])) {
			return $input["shops"];
		}
		return array();
	}

	function makeMerchantCode($con)
	{
		$result = mysqli_query($con, "SELECT MAX(id) AS maxId FROM merchants");
		$row = $result ? mysqli_fetch_assoc($result) : null;
		$nextId = $row && $row["maxId"] ? intval($row["maxId"]) + 1 : 1;
		return "M".str_pad($nextId, 5, "0", STR_PAD_LEFT);
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

	function rollback_error($con, $message)
	{
		mysqli_rollback($con);
		$this->echo_error($message);
	}

	function echo_error($message)
	{
		echo json_encode(array('success' => false, 'txt' => $message));
		exit;
	}
}
?>
