<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
date_default_timezone_set('Asia/Shanghai');
require_once '../base/adminAuth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	admin_json(array('success' => true));
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	admin_error('REQUEST_METHOD only post');
}

$input = admin_input();
$username = isset($input['username']) ? trim($input['username']) : '';
$password = isset($input['password']) ? $input['password'] : '';
if ($username === '' || $password === '') {
	admin_error('请输入管理员账号和密码');
}

$con = admin_connect();
$countResult = mysqli_query($con, "SELECT COUNT(*) AS total FROM `admins`");
if (!$countResult) {
	admin_error('请先导入数据库.sql中的管理员表结构');
}
$countRow = mysqli_fetch_assoc($countResult);
if (intval($countRow['total']) === 0) {
	$ini = new ini();
	$initialAdminUser = isset($ini->initialAdminUser) ? trim($ini->initialAdminUser) : '';
	$initialAdminPass = isset($ini->initialAdminPass) ? $ini->initialAdminPass : '';
	if ($initialAdminUser === '' || $initialAdminPass === '' || $initialAdminPass === 'change_this_initial_admin_password') {
		admin_error('请先在api/ini.php中设置初始管理员账号和密码');
	}
	if ($username !== $initialAdminUser || $password !== $initialAdminPass) {
		admin_error('初始管理员账号或密码错误');
	}
	$hash = password_hash($password, PASSWORD_DEFAULT);
	$stmt = $con->prepare("INSERT INTO `admins` (`username`, `passwordHash`, `createdTime`, `updatedTime`) VALUES (?, ?, NOW(), NOW())");
	$stmt->bind_param("ss", $username, $hash);
	$stmt->execute();
	$stmt->close();
}

$stmt = $con->prepare("SELECT `id`, `username`, `passwordHash`, `status` FROM `admins` WHERE `username` = ? LIMIT 1");
if (!$stmt) {
	admin_error('管理员表不可用');
}
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$admin = mysqli_fetch_assoc($result);
$stmt->close();
if (!$admin || intval($admin['status']) !== 1 || !password_verify($password, $admin['passwordHash'])) {
	admin_error('管理员账号或密码错误');
}

$token = admin_random_token();
$tokenHash = hash('sha256', $token);
$adminId = intval($admin['id']);
$stmt = $con->prepare("INSERT INTO `admin_sessions` (`adminId`, `tokenHash`, `expiresAt`, `createdTime`) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY), NOW())");
if (!$stmt) {
	admin_error('管理员会话表不可用');
}
$stmt->bind_param("is", $adminId, $tokenHash);
$stmt->execute();
$stmt->close();
admin_json(array(
	'success' => true,
	'token' => $token,
	'username' => $admin['username'],
	'apiToken' => current_api_token()
));
?>
