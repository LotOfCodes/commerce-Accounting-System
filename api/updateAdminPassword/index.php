<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
date_default_timezone_set('Asia/Shanghai');
require_once '../base/adminAuth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	admin_json(array('success' => true));
}
$session = admin_require_session();
$con = $session['con'];
$input = admin_input();
$oldPassword = isset($input['oldPassword']) ? $input['oldPassword'] : '';
$newPassword = isset($input['newPassword']) ? $input['newPassword'] : '';
if ($oldPassword === '' || strlen($newPassword) < 6) {
	admin_error('请输入原密码，新密码至少6位');
}
$adminId = intval($session['admin']['adminId']);
$stmt = $con->prepare("SELECT `passwordHash` FROM `admins` WHERE `id` = ? LIMIT 1");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$admin = mysqli_fetch_assoc($result);
$stmt->close();
if (!$admin || !password_verify($oldPassword, $admin['passwordHash'])) {
	admin_error('原密码错误');
}
$hash = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $con->prepare("UPDATE `admins` SET `passwordHash` = ?, `updatedTime` = NOW() WHERE `id` = ?");
$stmt->bind_param("si", $hash, $adminId);
$stmt->execute();
$stmt->close();
admin_json(array('success' => true));
?>
