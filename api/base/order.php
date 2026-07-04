<?php
class Order
{
	function __construct()
    {
        //$this->crypto = new crypto($key, $iv);
        //$this->ini    = new ini();

    }
	//添加订单
	function addOrder()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		$orderId = $input["orderId"];
		if ($this->isOrderExist($orderId))
		{
			$this->updateOrder();
		}
		else
		{
			$SQLArray = array( 
				'customer'=>$input["customer"],
				'orderId'=>$input["orderId"],
				'platId'=>$input["platId"],
				'orderType'=>$input["orderType"],
				'tradeStatus'=>$input["tradeStatus"],
				'shopName'=>$input["shopName"],
				'receiverName'=>$input["receiverName"],
				'receiverProvince'=>$input["receiverProvince"],
				'receiverAddress'=>$input["receiverAddress"],
				'receiverMobile'=>$input["receiverMobile"],
				'remark'=>$input["remark"],
				'waybillNumber'=>$input["waybillNumber"],
				'waybillCom'=>$input["waybillCom"],
				'waybillTemplate'=>$input["waybillTemplate"],
				'deliveryTime'=>$input["deliveryTime"]
			);
			$con = @mysqli_connect($ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);
			if (!$con)
			{
				$JsonArray = array(
					'success' => false, 
					'txt' => '连接数据库发生错误：“'. mysqli_error()."。”"
				);  
				echo json_encode($JsonArray);
				@mysqli_close($con);
				exit;
			}
			mysqli_select_db($con,$ini->mySqlDataBase); 	
			mysqli_query($con,"SET NAMES 'UTF8'");
			mysqli_query($con,"insert into orders " .$this->ArraytoSQLaddstr($SQLArray)) or die($this->echo_error(mysqli_error()));
			$JsonArray = array(
				'success' => true
			);
			echo json_encode($JsonArray);
			@mysqli_close($con);
			exit;
		}

	}
	//订单是否存在
	function isOrderExist($orderId)
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		$con = @mysqli_connect($ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);
		mysqli_query($con,"set names 'UTF8' ");
		$db_selected = mysqli_select_db($con,$ini->mySqlDataBase);
		$sql    = "SELECT * FROM  `orders` WHERE `orderId` =?";
		$stmt = $con->prepare($sql);
		if ($stmt) 
		{
			$stmt->bind_param("s", $orderId);
			$stmt->execute();
			$result = $stmt->get_result();
			if (mysqli_num_rows($result)>0)
			{
				return true;
			}
			else{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
	//更新订单
	function updateOrder()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		$orderId = $input["orderId"];
		$con = @mysqli_connect($ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);
		mysqli_query($con,"SET NAMES 'UTF8'");
		mysqli_select_db($con,$ini->mySqlDataBase);
		$SQLArray = array(
			'customer'=>$input["customer"],
			'orderId'=>$input["orderId"],
			'platId'=>$input["platId"],
			'orderType'=>$input["orderType"],
			'tradeStatus'=>$input["tradeStatus"],
			'shopName'=>$input["shopName"],
			'receiverName'=>$input["receiverName"],
			'receiverProvince'=>$input["receiverProvince"],
			'receiverAddress'=>$input["receiverAddress"],
			'receiverMobile'=>$input["receiverMobile"],
			'remark'=>$input["remark"],
			'waybillNumber'=>$input["waybillNumber"],
			'waybillCom'=>$input["waybillCom"],
			'waybillTemplate'=>$input["waybillTemplate"],
			'deliveryTime'=>$input["deliveryTime"]
		);
		mysqli_query($con,"UPDATE `orders` set ". $this->ArraytoSQLstr($SQLArray) ." where `orderId`='$orderId'") or die($this->echo_error(mysqli_error()));
		$JsonArray = array(
			'success' => true
		);
		echo json_encode($JsonArray);
		@mysqli_close($con);
		exit;
	}
	//获取订单
	function getOrders()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		$con = @mysqli_connect($ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);	
		if (!$con)
		{
			$this->echo_error('连接数据库发生错误：“'. mysqli_error()."。”");
			@mysqli_close($con);
			exit;
		} 
		mysqli_query($con,"set names 'UTF8' ");
		$db_selected = mysqli_select_db($con,$ini->mySqlDataBase);
		//获取数据库列表
		$orders = array();
		//php
		//查询条件
		$start_time = $input["startTime"];//date('Y-m-d H:i:s',time()- 1*24*60*60);
		$end_time   = $input["endTime"];//date('Y-m-d H:i:s',time()+ 1*24*60*60);
		$page = isset($input["page"]) ? intval($input["page"]) : 1;
		$pageSize = isset($input["pageSize"]) ? intval($input["pageSize"]) : 50;
		$page = $page > 0 ? $page : 1;
		$pageSize = $pageSize > 0 ? $pageSize : 50;
		$pageSize = $pageSize > 500 ? 500 : $pageSize;
		$offset = ($page - 1) * $pageSize;
		$merchant = isset($input["merchant"]) ? trim($input["merchant"]) : "";
		$status = isset($input["status"]) ? trim($input["status"]) : "";
		$keywordType = isset($input["keywordType"]) ? trim($input["keywordType"]) : "";
		$keyword = isset($input["keyword"]) ? trim($input["keyword"]) : "";
		$whereSql = "`deliveryTime`>= ? and `deliveryTime`<= ?";
		$params = array($start_time, $end_time);
		$types = "ss";
		if ($merchant !== "" && $merchant !== "all") {
			$whereSql .= " and `customer` = ?";
			$params[] = $merchant;
			$types .= "s";
		}
		if ($status !== "" && $status !== "all") {
			if ($status === "ready") {
				$whereSql .= " and (`tradeStatus` = ? or `tradeStatus` = ? or `tradeStatus` = ?)";
				$params[] = "2";
				$params[] = "已发货";
				$params[] = "SENT";
				$types .= "sss";
			} else {
				$whereSql .= " and `tradeStatus` = ?";
				$params[] = $status;
				$types .= "s";
			}
		}
		if ($keyword !== "") {
			$keywordLike = "%" . $keyword . "%";
			if ($keywordType === "express") {
				$whereSql .= " and `waybillNumber` LIKE ?";
				$params[] = $keywordLike;
				$types .= "s";
			} else if ($keywordType === "mobile") {
				$whereSql .= " and `receiverMobile` LIKE ?";
				$params[] = $keywordLike;
				$types .= "s";
			} else {
				$whereSql .= " and (`orderId` LIKE ? or `platId` LIKE ?)";
				$params[] = $keywordLike;
				$params[] = $keywordLike;
				$types .= "ss";
			}
		}
		//echo $start_time;

		$total = 0;
		$countSql = "SELECT COUNT(*) as total FROM `orders` WHERE " . $whereSql;
		$countStmt = $con->prepare($countSql);
		if ($countStmt) {
			$countParams = $params;
			array_unshift($countParams, $types);
			call_user_func_array(array($countStmt, "bind_param"), $this->refValues($countParams));
			$countStmt->execute();
			$countResult = $countStmt->get_result();
			if ($countRow = mysqli_fetch_array($countResult)) {
				$total = intval($countRow["total"]);
			}
			$countStmt->close();
		}

		$sql    = "SELECT * FROM  `orders` WHERE " . $whereSql . " ORDER BY id DESC LIMIT ? OFFSET ?";
		$stmt = $con->prepare($sql);
		if ($stmt) {
			// 绑定参数
			$queryParams = $params;
			$queryParams[] = $pageSize;
			$queryParams[] = $offset;
			array_unshift($queryParams, $types . "ii");
			call_user_func_array(array($stmt, "bind_param"), $this->refValues($queryParams));

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
						'orderId'=>$row["orderId"],
						'platId'=>$row["platId"],
						'orderType'=>$row["orderType"],
						'tradeStatus'=>$row["tradeStatus"],
						'shopName'=>$row["shopName"],
						'receiverName'=>$row["receiverName"],
						'receiverProvince'=>$row["receiverProvince"],
						'receiverAddress'=>$row["receiverAddress"],
						'receiverMobile'=>$row["receiverMobile"],
						'remark'=>$row["remark"],
						'waybillNumber'=>$row["waybillNumber"],
						'waybillCom'=>$row["waybillCom"],
						'waybillTemplate'=>$row["waybillTemplate"],
						'deliveryTime'=>$row["deliveryTime"]
					);
					array_push($orders,$tempList);
				}
			}
			//输出状态和结果
			$JsonArray = array(
				'success' => true, 
				'data' => $orders,
				'total' => $total,
				'page' => $page,
				'pageSize' => $pageSize
			);  
			echo json_encode($JsonArray);
			// 关闭语句
			$stmt->close();
			exit;
		}
		else{
			$this->echo_error('error');
		}

	}
	//删除订单
	function delOrder()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		if(!isset($input["orderId"])){
			$this->echo_error('empty params');
		}	
		$con = @mysqli_connect($ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);
		$orderId = $input['orderId'];
		mysqli_query($con,"set names 'UTF8' ");
		$db_selected = mysqli_select_db($con,$MySqlDB_);
		$sql_del = "DELETE FROM orders WHERE `orderId`= ?";
		$stmt = $con->prepare($sql_del);
		if ($stmt) {
			// 绑定参数
			$stmt->bind_param("ss", $orderId);

			// 执行查询
			$stmt->execute();

			// 获取结果
			$result = $stmt->get_result();
			// 关闭语句
			$stmt->close();
		} else {
			$this->echo_error('error');
			exit;
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
	function refValues(&$arr) {
		$refs = array();
		foreach ($arr as $key => $value) {
			$refs[$key] = &$arr[$key];
		}
		return $refs;
	}
}
?>
