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
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		//echo var_dump($input);
		$orderId = $input["orderId"];
		if ($this->isBeanExist($orderId))
		{
			$this->updateBean();
		}
		else
		{
			$SQLArray = array( 
				'customer'=>$input["customer"],
				'parentOrderId'=>$input["parentOrderId"],
				'orderId'=>$input["orderId"],
				'parentPlatId'=>$input["parentPlatId"],
				'platId'=>$input["platId"],
				'orderType'=>$input["orderType"],
				'tradeStatus'=>$input["tradeStatus"],
				'shopName'=>$input["shopName"],
				'sku'=>$input["sku"],
				'picUrl'=>$input["picUrl"],
				'total'=>$input["total"],
				'weightActual'=>$input["weightActual"],
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
			mysqli_query($con,"insert into beans " .$this->ArraytoSQLaddstr($SQLArray)) or die($this->echo_error(mysqli_error()));
			$JsonArray = array(
				'success' => true
			);
			echo json_encode($JsonArray);
			@mysqli_close($con);
			exit;
		}

	}
	//发货内容是否存在
	function isBeanExist($orderId)
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		$con = @mysqli_connect($ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);
		mysqli_query($con,"set names 'UTF8' ");
		$db_selected = mysqli_select_db($con,$ini->mySqlDataBase);
		$sql    = "SELECT * FROM  `beans` WHERE `orderId` =?";
		$stmt = $con->prepare($sql);
		if ($stmt) 
		{
			$stmt->bind_param("s", $orderId);
			$stmt->execute();
			$result = $stmt->get_result();
			//echo var_dump($result );
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
	//更新发货内容
	function updateBean()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		$orderId = $input["orderId"];
		$con = @mysqli_connect($ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);
		mysqli_query($con,"SET NAMES 'UTF8'");	
		mysqli_select_db($con,$ini->mySqlDataBase);
		$SQLArray = array(
			'customer'=>$input["customer"],
			'parentOrderId'=>$input["parentOrderId"],
			'orderId'=>$input["orderId"],
			'parentPlatId'=>$input["parentPlatId"],
			'platId'=>$input["platId"],
			'orderType'=>$input["orderType"],
			'tradeStatus'=>$input["tradeStatus"],
			'shopName'=>$input["shopName"],
			'sku'=>$input["sku"],
			'picUrl'=>$input["picUrl"],
			'total'=>$input["total"],
			'weightActual'=>$input["weightActual"],
			'deliveryTime'=>$input["deliveryTime"]
		);
		mysqli_query($con,"UPDATE `beans` set ". $this->ArraytoSQLstr($SQLArray) ." where `orderId`='$orderId'") or die($this->echo_error(mysqli_error()));
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
		$beans = array();
		//php
		//查询条件
		$orderId = $input["orderId"];
		//echo $start_time;

		$sql    = "SELECT * FROM  `beans` WHERE `orderId`= ? ORDER BY id DESC";
		$stmt = $con->prepare($sql);
		if ($stmt) {
			// 绑定参数
			$stmt->bind_param("s", $orderId);

			// 执行查询
			$stmt->execute();

			// 获取结果
			$result = $stmt->get_result();
			//$result = mysqli_query($con,$sql);
			if (mysqli_num_rows($result)<=0)
			{
				$this->echo_error($sql);
				@mysqli_close($con);
				exit;
			}
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
			exit;
		}
		else{
			$this->echo_error('error');
		}

	}
	//删除发货内容
	function delBean()
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
		$sql_del = "DELETE FROM beans WHERE `orderId`= ?";
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
}
?>