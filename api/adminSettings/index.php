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
$token = current_api_token();
admin_json(array(
	'success' => true,
	'username' => $session['admin']['username'],
	'apiToken' => $token,
	'apiTokenExists' => $token !== ''
));
?>
