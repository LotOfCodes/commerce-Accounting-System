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
admin_setting_set($con, 'api_token', '');
admin_setting_set($con, 'api_token_deleted', '1');
admin_json(array('success' => true));
?>
