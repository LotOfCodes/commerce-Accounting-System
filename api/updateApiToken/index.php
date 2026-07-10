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
$token = isset($input['apiToken']) ? trim($input['apiToken']) : '';
if ($token === '') {
	admin_error('API token不能为空');
}
admin_setting_set($con, 'api_token', $token);
admin_setting_set($con, 'api_token_deleted', '0');
admin_json(array('success' => true, 'apiToken' => $token));
?>
