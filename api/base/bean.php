<?php
class Bean
{
	function __construct()
    {
        //$this->crypto = new crypto($key, $iv);
        //$this->ini    = new ini();

    }
	//添加订发货内容
	function addBean()
	{
		$input = json_decode(file_get_contents('php://input'), true);
		if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
			$this->echo_error('JSON格式错误');
		}

		$batchInfo = $this->normalizeBeanPayload($input);
		$beans = $batchInfo['beans'];
		$isBatch = $batchInfo['isBatch'];
		if (count($beans) <= 0) {
			if ($isBatch) {
				echo json_encode(array(
					'success' => true,
					'total' => 0,
					'inserted' => 0,
					'updated' => 0,
					'data' => array()
				));
				exit;
			}
			$this->echo_error('empty params');
		}

		$con = $this->connectDb();
		$inserted = 0;
		$updated = 0;
		$results = array();
		$existingBeans = $isBatch ? $this->loadExistingBeans($con, $beans) : null;
		if ($isBatch) {
			mysqli_begin_transaction($con);
		}
		try {
			foreach ($beans as $index => $bean) {
				if (!is_array($bean)) {
					throw new Exception('第'.($index + 1).'条发货内容格式错误');
				}
				$result = $this->saveBeanRecord($con, $bean, $index, $existingBeans);
				if ($result['action'] === 'inserted') {
					$inserted++;
				} else {
					$updated++;
				}
				$results[] = $result;
			}
			if ($isBatch) {
				mysqli_commit($con);
			}
		} catch (Exception $e) {
			if ($isBatch) {
				mysqli_rollback($con);
			}
			@mysqli_close($con);
			$this->echo_error($e->getMessage());
		}

		$JsonArray = array(
			'success' => true
		);
		if ($isBatch) {
			$JsonArray['total'] = count($beans);
			$JsonArray['inserted'] = $inserted;
			$JsonArray['updated'] = $updated;
			$JsonArray['data'] = $results;
		}
		echo json_encode($JsonArray);
		@mysqli_close($con);
		exit;
	}
	//发货内容是否存在
	function isBeanExist($orderId, $con = null)
	{
		$ini = new ini();
		$shouldClose = false;
		if ($con === null) {
			$con = @mysqli_connect($ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);
			mysqli_query($con,"set names 'UTF8' ");
			mysqli_select_db($con,$ini->mySqlDataBase);
			$shouldClose = true;
		}
		$sql    = "SELECT * FROM  `beans` WHERE `orderId` =?";
		$stmt = $con->prepare($sql);
		if ($stmt) 
		{
			$stmt->bind_param("s", $orderId);
			$stmt->execute();
			$stmt->store_result();
			$exists = $stmt->num_rows > 0;
			$stmt->close();
			if ($shouldClose) {
				@mysqli_close($con);
			}
			return $exists;
		}
		else
		{
			if ($shouldClose) {
				@mysqli_close($con);
			}
			return false;
		}
	}
	//更新发货内容
	function updateBean()
	{
		$input = json_decode(file_get_contents('php://input'), true);
		if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
			$this->echo_error('JSON格式错误');
		}
		if (isset($input['beans']) && is_array($input['beans'])) {
			$this->syncOrderBeans($input);
		}
		$con = $this->connectDb();
		try {
			$this->updateBeanRecord($con, $this->beanSqlArray($input), trim(strval($this->arrayValue($input, 'orderId', ''))));
		} catch (Exception $e) {
			@mysqli_close($con);
			$this->echo_error($e->getMessage());
		}
		$JsonArray = array(
			'success' => true
		);
		echo json_encode($JsonArray);
		@mysqli_close($con);
		exit;
	}
	//获取发货内容
	function getBeans()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
			$this->echo_error('JSON格式错误');
		}
		$con = @mysqli_connect($ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);	
		if (!$con)
		{
			$this->echo_error('连接数据库发生错误：“'. mysqli_connect_error()."。”");
			@mysqli_close($con);
			exit;
		} 
		mysqli_query($con,"set names 'UTF8' ");
		$db_selected = mysqli_select_db($con,$ini->mySqlDataBase);
		if (!$db_selected) {
			$message = mysqli_error($con);
			@mysqli_close($con);
			$this->echo_error('选择数据库发生错误：“'.$message.'。”');
		}
		//获取数据库列表
		$beans = array();
		//php
		//查询条件
		$orderId = isset($input["orderId"]) ? trim(strval($input["orderId"])) : "";
		if ($orderId === "") {
			@mysqli_close($con);
			$this->echo_error('empty params');
		}
		//echo $start_time;

		$sql    = "SELECT * FROM  `beans` WHERE `parentOrderId`= ? OR `orderId` = ? ORDER BY id DESC";
		$stmt = $con->prepare($sql);
		if ($stmt) {
			// 绑定参数
			$stmt->bind_param("ss", $orderId, $orderId);

			// 执行查询
			$stmt->execute();

			// 获取结果
			$result = $stmt->get_result();
			//$result = mysqli_query($con,$sql);
			if (mysqli_num_rows($result)>0)
			{	
				while($row = mysqli_fetch_array($result))
				{			
					$tempList = array(
						'customer'=>$row["customer"],
						'parentOrderId'=>$row["parentOrderId"],
						'orderId'=>$row["orderId"],
						'parentPlatId'=>$row["parentPlatId"],
						'platId'=>$row["platId"],						
						'tradeStatus'=>$row["tradeStatus"],
						'shopName'=>$row["shopName"],
						'orderType'=>$row["orderType"],
						'sku'=>$row["sku"],
						'picUrl'=>$row["picUrl"],
						'total'=>$row["total"],
						'weightActual'=>$row["weightActual"],
						'deliveryTime'=>$row["deliveryTime"]//,
						//'rowData'=>$row["rowData"]
					);
					array_push($beans,$tempList);
				}
			}
			//输出状态和结果
			$JsonArray = array(
				'success' => true, 
				'data' => $beans
			);  
			echo json_encode($JsonArray);
			// 关闭语句
			$stmt->close();
			@mysqli_close($con);
			exit;
		}
		else{
			$message = $con->error;
			@mysqli_close($con);
			$this->echo_error($message ? $message : 'error');
		}

	}
	//删除发货内容
	function delBean()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		if(!isset($input["orderId"]) && !isset($input["parentOrderId"])){
			$this->echo_error('empty params');
		}	
		$con = @mysqli_connect($ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);
		if (!$con)
		{
			$this->echo_error('连接数据库发生错误：“'. mysqli_connect_error()."。”");
		}
		mysqli_query($con,"set names 'UTF8' ");
		mysqli_select_db($con,$ini->mySqlDataBase);
		if (isset($input["parentOrderId"]) && trim($input["parentOrderId"]) !== "") {
			$orderId = trim($input['parentOrderId']);
			$sql_del = "DELETE FROM `beans` WHERE `parentOrderId`= ? OR `orderId` = ?";
		} else {
			$orderId = trim($input['orderId']);
			$sql_del = "DELETE FROM `beans` WHERE `orderId`= ?";
		}
		if ($orderId === '') {
			$this->echo_error('empty params');
		}
		$stmt = $con->prepare($sql_del);
		if ($stmt) {
			if (isset($input["parentOrderId"]) && trim($input["parentOrderId"]) !== "") {
				$stmt->bind_param("ss", $orderId, $orderId);
			} else {
				$stmt->bind_param("s", $orderId);
			}
			$stmt->execute();
			if ($stmt->errno) {
				$message = $stmt->error;
				$stmt->close();
				@mysqli_close($con);
				$this->echo_error($message);
			}
			$deletedBeans = $stmt->affected_rows;
			$stmt->close();
			$JsonArray = array(
				'success' => true,
				'deletedBeans' => $deletedBeans
			);
			echo json_encode($JsonArray);
			@mysqli_close($con);
			exit;
		} else {
			$this->echo_error('删除发货内容失败');
		}
	}


	//ArraytoSQLstr
	function ArraytoSQLstr($arra)
	{
			$tempstr='';
			$num=1;
			foreach ($arra as $key=>$value)
			{
				 //echo $key;
				 if ($num==1)
				 { 
				 $tempstr=$key." = '".$value."' ";
				 }
				 else
				 {$tempstr=$tempstr.", ".$key." = '".$value."'";}; 
				 $num++; 
				}
			return $tempstr;
		}
	//ArraytoSQLaddstr
	function ArraytoSQLaddstr($arra)
	{
		$names='';
		$values='';
		$num=1;
		foreach ($arra as $key=>$value)
		{
			 //echo $key;
			 if ($num==1)
			 { 
			 $names=$key;
			 $values="'".$value."'";
			 }
			 else
			 { 
			 $names=$names.",".$key;
			 $values=$values.",'".$value."'";
			 }
			 $num++; 
			} 
		return "(".$names.") VALUES (".$values.")";
	}
	function echo_error($message) {
    	echo json_encode(['success'=>false, 'txt'=>$message]);
    	exit;
	}
	function normalizeBeanPayload($input) {
		if ($this->isListArray($input)) {
			return array('beans' => $input, 'isBatch' => true);
		}
		if (isset($input['beans']) && is_array($input['beans'])) {
			return array('beans' => $input['beans'], 'isBatch' => true);
		}
		if (isset($input['data']) && is_array($input['data']) && $this->isListArray($input['data'])) {
			return array('beans' => $input['data'], 'isBatch' => true);
		}
		return array('beans' => array($input), 'isBatch' => false);
	}
	function isListArray($array) {
		if (!is_array($array)) {
			return false;
		}
		$index = 0;
		foreach ($array as $key => $value) {
			if ($key !== $index) {
				return false;
			}
			$index++;
		}
		return true;
	}
	function connectDb() {
		$ini = new ini();
		$con = @mysqli_connect($ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);
		if (!$con)
		{
			$this->echo_error('连接数据库发生错误：“'. mysqli_connect_error()."。”");
		}
		if (!mysqli_select_db($con,$ini->mySqlDataBase)) {
			$message = mysqli_error($con);
			@mysqli_close($con);
			$this->echo_error('选择数据库发生错误：“'.$message.'。”');
		}
		mysqli_query($con,"SET NAMES 'UTF8'");
		return $con;
	}
	function saveBeanRecord($con, $input, $index = 0, &$existingBeans = null) {
		$orderId = trim(strval($this->arrayValue($input, 'orderId', '')));
		if ($orderId === '') {
			throw new Exception('第'.($index + 1).'条发货内容缺少orderId');
		}
		$SQLArray = $this->beanSqlArray($input);
		$existingBean = is_array($existingBeans) ? (isset($existingBeans[$orderId]) ? $existingBeans[$orderId] : null) : $this->loadExistingBean($con, $orderId);
		$exists = $existingBean !== null;
		if ($exists) {
			$this->updateExistingBeanRecord($con, $SQLArray, $orderId, $existingBean);
			$action = 'updated';
		} else {
			$this->insertBeanRecord($con, $SQLArray);
			if (is_array($existingBeans)) {
				$existingBeans[$orderId] = array('sku' => $SQLArray['sku']);
			}
			$action = 'inserted';
		}
		return array(
			'index' => $index,
			'orderId' => $orderId,
			'action' => $action
		);
	}
	function syncOrderBeans($input) {
		$parentOrderId = trim(strval($this->arrayValue($input, 'parentOrderId', '')));
		$beans = isset($input['beans']) && is_array($input['beans']) ? $input['beans'] : array();
		if ($parentOrderId === '' && count($beans) > 0 && is_array($beans[0])) {
			$parentOrderId = trim(strval($this->arrayValue($beans[0], 'parentOrderId', '')));
		}
		if ($parentOrderId === '') {
			$this->echo_error('empty params');
		}

		$con = $this->connectDb();
		mysqli_begin_transaction($con);
		try {
			$inserted = 0;
			$updated = 0;
			$existingBeans = $this->loadBeansByParentOrderId($con, $parentOrderId);
			$submittedOrderIds = array();
			foreach ($beans as $index => $bean) {
				if (!is_array($bean)) {
					throw new Exception('第'.($index + 1).'条发货内容格式错误');
				}
				if (trim(strval($this->arrayValue($bean, 'parentOrderId', ''))) === '') {
					$bean['parentOrderId'] = $parentOrderId;
				}
				$orderId = trim(strval($this->arrayValue($bean, 'orderId', '')));
				if ($orderId === '') {
					throw new Exception('第'.($index + 1).'条发货内容缺少orderId');
				}
				if (isset($submittedOrderIds[$orderId])) {
					throw new Exception('第'.($index + 1).'条发货内容orderId重复');
				}
				$SQLArray = $this->beanSqlArray($bean);
				if (isset($existingBeans[$orderId])) {
					$this->updateBeanRecord($con, $SQLArray, $orderId);
					$updated++;
				} else {
					$this->insertBeanRecord($con, $SQLArray);
					$inserted++;
				}
				$submittedOrderIds[$orderId] = true;
			}
			$deletedBeans = $this->deleteBeansNotInOrderIds($con, $parentOrderId, array_keys($submittedOrderIds));
			mysqli_commit($con);
		} catch (Exception $e) {
			mysqli_rollback($con);
			@mysqli_close($con);
			$this->echo_error($e->getMessage());
		}

		echo json_encode(array(
			'success' => true,
			'parentOrderId' => $parentOrderId,
			'deletedBeans' => $deletedBeans,
			'inserted' => $inserted,
			'updated' => $updated,
			'total' => count($beans)
		));
		@mysqli_close($con);
		exit;
	}
	function loadBeansByParentOrderId($con, $parentOrderId) {
		if ($parentOrderId === '') {
			throw new Exception('empty params');
		}
		$stmt = $con->prepare("SELECT `orderId` FROM `beans` WHERE `parentOrderId`= ? OR `orderId` = ?");
		if (!$stmt) {
			throw new Exception('查询已有发货内容失败：'.$con->error);
		}
		$stmt->bind_param("ss", $parentOrderId, $parentOrderId);
		$stmt->execute();
		if ($stmt->errno) {
			$message = $stmt->error;
			$stmt->close();
			throw new Exception($message);
		}
		$result = $stmt->get_result();
		$existing = array();
		while ($row = mysqli_fetch_assoc($result)) {
			$orderId = isset($row['orderId']) ? trim(strval($row['orderId'])) : '';
			if ($orderId !== '') {
				$existing[$orderId] = true;
			}
		}
		$stmt->close();
		return $existing;
	}
	function deleteBeansNotInOrderIds($con, $parentOrderId, $orderIds) {
		if ($parentOrderId === '') {
			throw new Exception('empty params');
		}
		if (!count($orderIds)) {
			$stmt = $con->prepare("DELETE FROM `beans` WHERE `parentOrderId`= ? OR `orderId` = ?");
			if (!$stmt) {
				throw new Exception('删除发货内容失败：'.$con->error);
			}
			$stmt->bind_param("ss", $parentOrderId, $parentOrderId);
		} else {
			$placeholders = implode(',', array_fill(0, count($orderIds), '?'));
			$stmt = $con->prepare("DELETE FROM `beans` WHERE (`parentOrderId`= ? OR `orderId` = ?) AND `orderId` NOT IN (".$placeholders.")");
			if (!$stmt) {
				throw new Exception('删除发货内容失败：'.$con->error);
			}
			$values = array_merge(array($parentOrderId, $parentOrderId), $orderIds);
			$this->bindDynamicParams($stmt, str_repeat('s', count($values)), $values);
		}
		$stmt->execute();
		if ($stmt->errno) {
			$message = $stmt->error;
			$stmt->close();
			throw new Exception($message);
		}
		$deletedBeans = $stmt->affected_rows;
		$stmt->close();
		return $deletedBeans;
	}
	function beanSqlArray($input) {
		return array(
			'customer'=>$this->arrayValue($input, 'customer', ''),
			'parentOrderId'=>$this->arrayValue($input, 'parentOrderId', ''),
			'orderId'=>$this->arrayValue($input, 'orderId', ''),
			'parentPlatId'=>$this->arrayValue($input, 'parentPlatId', ''),
			'platId'=>$this->arrayValue($input, 'platId', ''),
			'orderType'=>$this->arrayValue($input, 'orderType', ''),
			'tradeStatus'=>$this->arrayValue($input, 'tradeStatus', ''),
			'shopName'=>$this->arrayValue($input, 'shopName', ''),
			'sku'=>$this->arrayValue($input, 'sku', ''),
			'picUrl'=>$this->arrayValue($input, 'picUrl', ''),
			'total'=>$this->arrayValue($input, 'total', ''),
			'weightActual'=>$this->arrayValue($input, 'weightActual', ''),
			'deliveryTime'=>$this->arrayValue($input, 'deliveryTime', null)
		);
	}
	function insertBeanRecord($con, $SQLArray) {
		$columns = array_keys($SQLArray);
		$columnSql = '`'.implode('`,`', $columns).'`';
		$placeholders = implode(',', array_fill(0, count($columns), '?'));
		$stmt = $con->prepare("INSERT INTO `beans` (".$columnSql.") VALUES (".$placeholders.")");
		if (!$stmt) {
			throw new Exception('新增发货内容失败：'.$con->error);
		}
		$values = array_values($SQLArray);
		$this->bindDynamicParams($stmt, str_repeat('s', count($values)), $values);
		$stmt->execute();
		if ($stmt->errno) {
			$message = $stmt->error;
			$stmt->close();
			throw new Exception($message);
		}
		$stmt->close();
	}
	function updateBeanRecord($con, $SQLArray, $orderId) {
		if ($orderId === '') {
			throw new Exception('发货内容缺少orderId');
		}
		$sets = array();
		foreach (array_keys($SQLArray) as $column) {
			$sets[] = '`'.$column.'` = ?';
		}
		$stmt = $con->prepare("UPDATE `beans` SET ".implode(',', $sets)." WHERE `orderId` = ?");
		if (!$stmt) {
			throw new Exception('更新发货内容失败：'.$con->error);
		}
		$values = array_values($SQLArray);
		$values[] = $orderId;
		$this->bindDynamicParams($stmt, str_repeat('s', count($values)), $values);
		$stmt->execute();
		if ($stmt->errno) {
			$message = $stmt->error;
			$stmt->close();
			throw new Exception($message);
		}
		$stmt->close();
	}
	function updateExistingBeanRecord($con, $SQLArray, $orderId, $existingBean) {
		$existingSku = isset($existingBean['sku']) ? trim(strval($existingBean['sku'])) : '';
		if ($existingSku !== '') {
			$this->updateBeanTradeStatusRecord($con, $SQLArray, $orderId);
			return;
		}
		$this->updateBeanSkuAndTradeStatusRecord($con, $SQLArray, $orderId);
	}
	function updateBeanTradeStatusRecord($con, $SQLArray, $orderId) {
		if ($orderId === '') {
			throw new Exception('发货内容缺少orderId');
		}
		$stmt = $con->prepare("UPDATE `beans` SET `tradeStatus` = ? WHERE `orderId` = ?");
		if (!$stmt) {
			throw new Exception('更新发货内容状态失败：'.$con->error);
		}
		$tradeStatus = isset($SQLArray['tradeStatus']) ? $SQLArray['tradeStatus'] : '';
		$stmt->bind_param("ss", $tradeStatus, $orderId);
		$stmt->execute();
		if ($stmt->errno) {
			$message = $stmt->error;
			$stmt->close();
			throw new Exception($message);
		}
		$stmt->close();
	}
	function updateBeanSkuAndTradeStatusRecord($con, $SQLArray, $orderId) {
		if ($orderId === '') {
			throw new Exception('发货内容缺少orderId');
		}
		$stmt = $con->prepare("UPDATE `beans` SET `sku` = ?, `tradeStatus` = ? WHERE `orderId` = ?");
		if (!$stmt) {
			throw new Exception('更新发货内容失败：'.$con->error);
		}
		$sku = isset($SQLArray['sku']) ? $SQLArray['sku'] : '';
		$tradeStatus = isset($SQLArray['tradeStatus']) ? $SQLArray['tradeStatus'] : '';
		$stmt->bind_param("sss", $sku, $tradeStatus, $orderId);
		$stmt->execute();
		if ($stmt->errno) {
			$message = $stmt->error;
			$stmt->close();
			throw new Exception($message);
		}
		$stmt->close();
	}
	function loadExistingBean($con, $orderId) {
		$stmt = $con->prepare("SELECT `sku` FROM `beans` WHERE `orderId` = ? LIMIT 1");
		if (!$stmt) {
			throw new Exception('查询已有发货内容失败：'.$con->error);
		}
		$stmt->bind_param("s", $orderId);
		$stmt->execute();
		if ($stmt->errno) {
			$message = $stmt->error;
			$stmt->close();
			throw new Exception($message);
		}
		$result = $stmt->get_result();
		$row = mysqli_fetch_assoc($result);
		$stmt->close();
		return $row ? array('sku' => isset($row['sku']) ? $row['sku'] : '') : null;
	}
	function loadExistingBeans($con, $beans) {
		$orderIds = array();
		foreach ($beans as $bean) {
			if (!is_array($bean)) {
				continue;
			}
			$orderId = trim(strval($this->arrayValue($bean, 'orderId', '')));
			if ($orderId !== '') {
				$orderIds[$orderId] = true;
			}
		}
		$existing = array();
		$ids = array_keys($orderIds);
		foreach (array_chunk($ids, 500) as $chunk) {
			if (!count($chunk)) {
				continue;
			}
			$placeholders = implode(',', array_fill(0, count($chunk), '?'));
			$stmt = $con->prepare("SELECT `orderId`, `sku` FROM `beans` WHERE `orderId` IN (".$placeholders.")");
			if (!$stmt) {
				throw new Exception('查询已有发货内容失败：'.$con->error);
			}
			$this->bindDynamicParams($stmt, str_repeat('s', count($chunk)), $chunk);
			$stmt->execute();
			if ($stmt->errno) {
				$message = $stmt->error;
				$stmt->close();
				throw new Exception($message);
			}
			$result = $stmt->get_result();
			while ($row = mysqli_fetch_assoc($result)) {
				$existing[$row['orderId']] = array(
					'sku' => isset($row['sku']) ? $row['sku'] : ''
				);
			}
			$stmt->close();
		}
		return $existing;
	}
	function bindDynamicParams($stmt, $types, &$values) {
		array_unshift($values, $types);
		call_user_func_array(array($stmt, 'bind_param'), $this->refValues($values));
	}
	function refValues(&$arr) {
		$refs = array();
		foreach ($arr as $key => $value) {
			$refs[$key] = &$arr[$key];
		}
		return $refs;
	}
	function arrayValue($array, $key, $default = '') {
		return isset($array[$key]) ? $array[$key] : $default;
	}
}
?>
