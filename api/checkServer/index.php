<?php
checkServer();
function checkServer()
{
	$input = json_decode(file_get_contents('php://input'), true);
	$timestamp_ = $input['timestamp']??0;
	$JsonArray = array(
		'success' => true, 
		'timestamp' => $timestamp_
	);  
	echo json_encode($JsonArray);
	exit;
}
?>