<?php
require_once '../ini.php';
class ParentProduct
{
	function __construct()
    {
        //$this->crypto = new crypto($key, $iv);
        //$this->ini    = new ini();

    }

	//添加产品
	function addProduct()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		$SQLArray = array( 
			'pid'=>$input["pid"],
			'productName'=>$input["productName"]
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
		mysqli_query($con,"insert into Products " .$this->ArraytoSQLaddstr($SQLArray)) or die($this->echo_error(mysqli_error()));
		$JsonArray = array(
			'success' => true
		);
		echo json_encode($JsonArray);
		@mysqli_close($con);
		exit;
	}
	//更新产品
	function updateProduct()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		$id = $input["id"];
		$con = @mysqli_connect($ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);
		mysqli_select_db($con,$ini->mySqlDataBase); 
		mysqli_query($con,"SET NAMES 'UTF8'");	
		$SQLArray = array(
			'pid'=>$input["pid"],
			'productName'=>$input["productName"]
		);
		mysqli_query($con,"UPDATE `Products` set ". $this->ArraytoSQLstr($SQLArray) ." where `id`='$id'") or die($this->echo_error(mysqli_error()));
	}
	//获取产品
	function getProducts()
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

		$sql    = "SELECT * FROM  `Products` ORDER BY id DESC";
		$stmt = $con->prepare($sql);
		if ($stmt) {
			// 绑定参数
			$stmt->bind_param("ss", $start_time, $end_time);

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
						'id'=>$input["id"],
						'pid'=>$input["pid"],
						'productName'=>$input["productName"]
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
	//删除产品
	function delProduct()
	{
		$ini = new ini();
		$input = json_decode(file_get_contents('php://input'), true);
		if(!isset($input["id"])){
			$this->echo_error('empty params');
		}	
		$con = @mysqli_connect($ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);
		$id = $input['id'];
		mysqli_query($con,"set names 'UTF8' ");
		$db_selected = mysqli_select_db($con,$ini->mySqlDataBase);
		$sql_del = "DELETE FROM Products WHERE `id`= ?";
		$stmt = $con->prepare($sql_del);
		if ($stmt) {
			// 绑定参数
			$stmt->bind_param("ss", $id);

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
