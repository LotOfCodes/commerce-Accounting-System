<?php
require_once __DIR__ . '/base/adminAuth.php';
$authorization = current_api_token();
// 验证请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST'&&$_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    echo_error("REQUEST_METHOD only post");
    exit;
}
// 获取完整的Content-Type并提取主类型
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$mimeType = strtok($contentType, ';'); // 提取主类型（忽略参数）

// 严格匹配MIME类型
if ($mimeType !== 'application/json') {
    echo_error("CONTENT_TYPE only accepts application/json :".$mimeType);
    exit;
}
// 获取Authorization
//echo var_dump($_SERVER);
$authorization_ = $_SERVER['HTTP_TOKEN'] ?? '';//$_SERVER['AUTHORIZATION'] ?? '';
@$authorizationKey = $_SERVER['HTTP_TOKEN']; 

//echo 'authorization:'.$authorizationKey;
if ($authorization === '' || $authorizationKey !== $authorization) {
    echo_error("Authorization failed: Invalid token");
    exit;
}


// application/json
$input = json_decode(file_get_contents('php://input'), true);
$act = $input['act']??'';	


if (empty($authorizationKey) || $authorizationKey != $authorization) 
{
    $JsonArray = array(
        'success' => false, 
        'txt' => 'Authorization failed: Invalid token'
    );
    echo json_encode($JsonArray);
    exit;
}
function echo_error($message) {
    echo json_encode(['success'=>false, 'txt'=>$message]);
    exit;
}
// 方法1：标准获取方式
function getAuthHeader() {    
    foreach (getallheaders() as $name => $value) {
        if (strtolower($name) === 'Authorization') {
            return $value;
        }
    }
    return null;
}
?>
