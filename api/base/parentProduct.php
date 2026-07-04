<?php
require_once '../ini.php';
class ParentProduct
{
	function __construct()
    {
        //$this->crypto = new crypto($key, $iv);
        //$this->ini    = new ini();

    }

	//添加父类产品
	function addParentProduct()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		$SQLArray = array( 
			'product_name'=>$input["product_name"],
			'price'=>$input["price"],
			'm_price'=>$input["m_price"],
			'far_price'=>$input["far_price"],
			'weight'=>$input["weight"],
			'start_time'=>$input["start_time"],
			'end_time'=>$input["end_time"]
		);
		$con = @mysqli_connect( $ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);
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
		mysqli_query($con,"insert into ParentProducts " .$this->ArraytoSQLaddstr($SQLArray)) or die($this->echo_error(mysqli_error()));
		$JsonArray = array(
			'success' => true
		);
		echo json_encode($JsonArray);
		@mysqli_close($con);
		exit;
	}
	//更新父类产品
	function updateParentProduct()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		$ppid = $input["ppid"];
		$con = @mysqli_connect($ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);
		mysqli_select_db($con,$ini->mySqlDataBase); 
		mysqli_query($con,"SET NAMES 'UTF8'");	
		$SQLArray = array(
			'product_name'=>$input["product_name"],
			'price'=>$input["price"],
			'm_price'=>$input["m_price"],
			'far_price'=>$input["far_price"],
			'weight'=>$input["weight"],
			'start_time'=>$input["start_time"],
			'end_time'=>$input["end_time"]
		);
		mysqli_query($con,"UPDATE `ParentProducts` set ". $this->ArraytoSQLstr($SQLArray) ." where `id`='$ppid'") or die($this->echo_error(mysqli_error()));
	}
	//获取父类产品
	function getParentProducts()
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
		//mysqli_select_db($MySqlDB_, $con);
		mysqli_query($con,"set names 'UTF8' ");
		$db_selected = mysqli_select_db($con,$ini->mySqlDataBase);
		//获取数据库列表
		$Orders = array();
		//php
		//查询条件
		$start_time = date('Y-m-d H:i:s',time()- 1*24*60*60);
		$end_time   = date('Y-m-d H:i:s',time()+ 1*24*60*60);
		//echo $start_time;

		$sql    = "SELECT * FROM  `parentProducts` ORDER BY id DESC";
		$stmt = $con->prepare($sql);
		if ($stmt) {
			// 执行查询
			$stmt->execute();

			// 获取结果
			$result = $stmt->get_result();
			//$result = mysqli_query($con,$sql);
			if (!$result)
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
						'id'=>$row["id"],
						'product_name'=>$row["product_name"],
						'price'=>$row["price"],
						'm_price'=>$this->rowValue($row, "m_price", $this->rowValue($row, "mPrice", "")),
						'mPrice'=>$this->rowValue($row, "mPrice", $this->rowValue($row, "m_price", "")),
						'far_price'=>$this->rowValue($row, "far_price", $this->rowValue($row, "remotePrice", "")),
						'remotePrice'=>$this->rowValue($row, "remotePrice", $this->rowValue($row, "far_price", "")),
						'packPrice'=>$this->rowValue($row, "packPrice", ""),
						'expressPayer'=>$this->rowValue($row, "expressPayer", ""),
						'weight'=>$row["weight"],
						'start_time'=>$this->rowValue($row, "start_time", $this->rowValue($row, "startTime", "")),
						'startTime'=>$this->rowValue($row, "startTime", $this->rowValue($row, "start_time", "")),
						'end_time'=>$this->rowValue($row, "end_time", $this->rowValue($row, "endTime", "")),
						'endTime'=>$this->rowValue($row, "endTime", $this->rowValue($row, "end_time", ""))
					);
					array_push($Orders,$tempList);
				}
			}
			//输出状态和结果
			$JsonArray = array(
				'success' => true, 
				'data' => $Orders
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
	//删除父类产品
	function delParentProduct()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		if(!isset($input["ppid"])){
			$this->echo_error('empty params');
		}	
		$con = @mysqli_connect($ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);
		$ppid = $input['ppid'];
		mysqli_query($con,"set names 'UTF8' ");
		$db_selected = mysqli_select_db($con,$ini->mySqlDataBase);
		$sql_del = "DELETE FROM ParentProducts WHERE `id`= ?";
		$stmt = $con->prepare($sql_del);
		if ($stmt) {
			// 绑定参数
			$stmt->bind_param("ss", $ppid);

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
	function rowValue($row, $key, $default = "") {
		return array_key_exists($key, $row) ? $row[$key] : $default;
	}
}
?>
