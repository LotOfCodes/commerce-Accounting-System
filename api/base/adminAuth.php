<?php
require_once __DIR__ . '/../ini.php';

function admin_json($data)
{
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($data);
	exit;
}

function admin_error($message)
{
	admin_json(array('success' => false, 'txt' => $message));
}

function admin_connect()
{
	$ini = new ini();
	$con = @mysqli_connect($ini->mySqlServer, $ini->mySqlUser, $ini->mySqlPass);
	if (!$con) {
		admin_error('数据库连接失败');
	}
	if (!mysqli_select_db($con, $ini->mySqlDataBase)) {
		admin_error('数据库选择失败');
	}
	mysqli_query($con, "SET NAMES 'UTF8'");
	return $con;
}

function admin_input()
{
	$raw = file_get_contents('php://input');
	$input = json_decode($raw, true);
	return is_array($input) ? $input : array();
}

function admin_random_token()
{
	if (function_exists('random_bytes')) {
		return bin2hex(random_bytes(32));
	}
	if (function_exists('openssl_random_pseudo_bytes')) {
		return bin2hex(openssl_random_pseudo_bytes(32));
	}
	return hash('sha256', uniqid('', true) . mt_rand());
}

function admin_setting_get($con, $key, $default = null)
{
	$stmt = $con->prepare("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = ? LIMIT 1");
	if (!$stmt) {
		return $default;
	}
	$stmt->bind_param("s", $key);
	$stmt->execute();
	$result = $stmt->get_result();
	$value = $default;
	if ($row = mysqli_fetch_assoc($result)) {
		$value = $row['setting_value'];
	}
	$stmt->close();
	return $value;
}

function admin_setting_set($con, $key, $value)
{
	$stmt = $con->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`, `updatedTime`) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`), `updatedTime` = NOW()");
	if (!$stmt) {
		admin_error('系统设置表不可用');
	}
	$stmt->bind_param("ss", $key, $value);
	$stmt->execute();
	$stmt->close();
}

function admin_require_session()
{
	$token = $_SERVER['HTTP_ADMIN_TOKEN'] ?? '';
	if ($token === '') {
		admin_error('管理员未登录');
	}
	$con = admin_connect();
	$tokenHash = hash('sha256', $token);
	$stmt = $con->prepare("SELECT s.`id`, s.`adminId`, a.`username` FROM `admin_sessions` s INNER JOIN `admins` a ON a.`id` = s.`adminId` WHERE s.`tokenHash` = ? AND s.`expiresAt` > NOW() LIMIT 1");
	if (!$stmt) {
		admin_error('管理员会话表不可用');
	}
	$stmt->bind_param("s", $tokenHash);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = mysqli_fetch_assoc($result);
	$stmt->close();
	if (!$row) {
		@mysqli_close($con);
		admin_error('管理员登录已失效');
	}
	return array('con' => $con, 'admin' => $row, 'tokenHash' => $tokenHash);
}

function current_api_token()
{
	$ini = new ini();
	$fallback = isset($ini->authorization) ? $ini->authorization : '';
	$con = @mysqli_connect($ini->mySqlServer, $ini->mySqlUser, $ini->mySqlPass);
	if (!$con || !@mysqli_select_db($con, $ini->mySqlDataBase)) {
		return $fallback;
	}
	mysqli_query($con, "SET NAMES 'UTF8'");
	$deleted = admin_setting_get($con, 'api_token_deleted', '0');
	if ($deleted === '1') {
		@mysqli_close($con);
		return '';
	}
	$token = admin_setting_get($con, 'api_token', null);
	@mysqli_close($con);
	if ($token === null || $token === '') {
		return $fallback;
	}
	return $token;
}
?>
