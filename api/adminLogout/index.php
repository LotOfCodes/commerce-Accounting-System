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
$tokenHash = $session['tokenHash'];
$stmt = $con->prepare("DELETE FROM `admin_sessions` WHERE `tokenHash` = ?");
$stmt->bind_param("s", $tokenHash);
$stmt->execute();
$stmt->close();
admin_json(array('success' => true));
?>
