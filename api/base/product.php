<?php
require_once __DIR__ . '/../ini.php';
class Product
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
		$con = $this->connect($ini);
		$this->ensureProductColumns($con);
		$SQLArray = $this->productSqlArray($input);
		mysqli_query($con,"insert into products " .$this->ArraytoSQLaddstr($con, $SQLArray)) or die($this->echo_error(mysqli_error($con)));
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
		$id = isset($input["id"]) ? $input["id"] : (isset($input["ppid"]) ? $input["ppid"] : "");
		if ($id === "") {
			$this->echo_error('empty params');
		}
		$con = $this->connect($ini);
		$this->ensureProductColumns($con);
		$SQLArray = $this->productSqlArray($input);
		mysqli_query($con,"UPDATE `products` set ". $this->ArraytoSQLstr($con, $SQLArray) ." where `id`='".$this->escape($con, $id)."'") or die($this->echo_error(mysqli_error($con)));
		$JsonArray = array(
			'success' => true
		);
		echo json_encode($JsonArray);
		@mysqli_close($con);
		exit;
	}
	//获取产品
	function getProducts()
	{
		$ini = new ini();
		$con = $this->connect($ini);
		$this->ensureProductColumns($con);
		$products = array();

		$sql    = "SELECT * FROM  `products` ORDER BY id DESC";
		$stmt = $con->prepare($sql);
		if ($stmt) {
			$stmt->execute();
			$result = $stmt->get_result();
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
						'productName'=>$this->rowValue($row, "productName", ""),
						'merchantIds'=>$this->rowValue($row, "merchantIds", ""),
						'merchantNames'=>$this->merchantNames($con, $this->rowValue($row, "merchantIds", "")),
						'matchRule'=>$this->rowValue($row, "matchRule", ""),
						'price'=>$this->rowValue($row, "price", ""),
						'mPrice'=>$this->rowValue($row, "mPrice", $this->rowValue($row, "m_price", "")),
						'm_price'=>$this->rowValue($row, "m_price", $this->rowValue($row, "mPrice", "")),
						'remotePrice'=>$this->rowValue($row, "remotePrice", $this->rowValue($row, "far_price", "")),
						'far_price'=>$this->rowValue($row, "far_price", $this->rowValue($row, "remotePrice", "")),
						'packPrice'=>$this->rowValue($row, "packPrice", ""),
						'expressPayer'=>$this->rowValue($row, "expressPayer", ""),
						'weight'=>$this->rowValue($row, "weight", ""),
						'startTime'=>$this->rowValue($row, "startTime", $this->rowValue($row, "start_time", "")),
						'start_time'=>$this->rowValue($row, "start_time", $this->rowValue($row, "startTime", "")),
						'endTime'=>$this->rowValue($row, "endTime", $this->rowValue($row, "end_time", "")),
						'end_time'=>$this->rowValue($row, "end_time", $this->rowValue($row, "endTime", ""))
					);
					array_push($products,$tempList);
				}
			}
			$JsonArray = array(
				'success' => true,
				'data' => $products
			);
			echo json_encode($JsonArray);
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
		$id = isset($input["id"]) ? $input["id"] : (isset($input["ppid"]) ? $input["ppid"] : "");
		if($id === ""){
			$this->echo_error('empty params');
		}
		$con = $this->connect($ini);
		$sql_del = "DELETE FROM products WHERE `id`= ?";
		$stmt = $con->prepare($sql_del);
		if ($stmt) {
			$stmt->bind_param("s", $id);
			$stmt->execute();
			$stmt->close();
			$JsonArray = array(
				'success' => true
			);
			echo json_encode($JsonArray);
			exit;
		} else {
			$this->echo_error('error');
			exit;
		}
	}
	function connect($ini)
	{
		$con = @mysqli_connect($ini->mySqlServer,$ini->mySqlUser,$ini->mySqlPass);
		if (!$con)
		{
			$this->echo_error('连接数据库发生错误：“'. mysqli_error()."。”");
			@mysqli_close($con);
			exit;
		}
		mysqli_select_db($con,$ini->mySqlDataBase);
		mysqli_query($con,"SET NAMES 'UTF8'");
		return $con;
	}
	function productSqlArray($input)
	{
		return array(
			'productName'=>$this->inputValue($input, "productName", ""),
			'merchantIds'=>$this->normalizeMerchantIds($this->inputValue($input, "merchantIds", "")),
			'matchRule'=>$this->inputValue($input, "matchRule", ""),
			'price'=>$this->inputValue($input, "price", ""),
			'mPrice'=>$this->inputValue($input, "mPrice", $this->inputValue($input, "m_price", "")),
			'remotePrice'=>$this->inputValue($input, "remotePrice", $this->inputValue($input, "far_price", "")),
			'packPrice'=>$this->inputValue($input, "packPrice", ""),
			'expressPayer'=>$this->inputValue($input, "expressPayer", ""),
			'weight'=>$this->inputValue($input, "weight", ""),
			'startTime'=>$this->inputValue($input, "startTime", $this->inputValue($input, "start_time", null)),
			'endTime'=>$this->inputValue($input, "endTime", $this->inputValue($input, "end_time", null))
		);
	}
	function inputValue($input, $key, $default = "")
	{
		return isset($input[$key]) ? $input[$key] : $default;
	}
	function rowValue($row, $key, $default = "")
	{
		return array_key_exists($key, $row) ? $row[$key] : $default;
	}
	function normalizeMerchantIds($value)
	{
		if (is_array($value)) {
			$value = implode(",", $value);
		}
		$ids = array();
		foreach (explode(",", strval($value)) as $item) {
			$id = intval(trim($item));
			if ($id > 0 && !in_array($id, $ids)) {
				$ids[] = $id;
			}
		}
		return implode(",", $ids);
	}
	function merchantNames($con, $merchantIds)
	{
		$ids = $this->normalizeMerchantIds($merchantIds);
		if ($ids === "") {
			return "";
		}
		$names = array();
		$result = mysqli_query($con, "SELECT `id`, `mid`, `merchantName` FROM merchants WHERE `id` IN (".$ids.") ORDER BY FIELD(`id`, ".$ids.")");
		if ($result) {
			while ($row = mysqli_fetch_assoc($result)) {
				$code = $this->rowValue($row, "mid", "");
				$name = $this->rowValue($row, "merchantName", "");
				$names[] = $code && $name ? $code." - ".$name : ($name ? $name : $code);
			}
		}
		return implode("，", $names);
	}
	function ensureProductColumns($con)
	{
		$result = mysqli_query($con, "SHOW COLUMNS FROM `products` LIKE 'merchantIds'");
		if ($result && mysqli_num_rows($result) > 0) {
			return;
		}
		mysqli_query($con, "ALTER TABLE `products` ADD COLUMN `merchantIds` varchar(2000) DEFAULT NULL AFTER `productName`");
	}
	function escape($con, $value)
	{
		return mysqli_real_escape_string($con, $value);
	}
	function sqlValue($con, $value)
	{
		if ($value === null || $value === '') {
			return 'NULL';
		}
		return "'".$this->escape($con, $value)."'";
	}
	//ArraytoSQLstr
	function ArraytoSQLstr($con, $arra)
	{
			$tempstr='';
			$num=1;
			foreach ($arra as $key=>$value)
			{
				 if ($num==1)
				 {
				 $tempstr=$key." = ".$this->sqlValue($con, $value)." ";
				 }
				 else
				 {$tempstr=$tempstr.", ".$key." = ".$this->sqlValue($con, $value);};
				 $num++;
				}
			return $tempstr;
		}
	//ArraytoSQLaddstr
	function ArraytoSQLaddstr($con, $arra)
	{
		$names='';
		$values='';
		$num=1;
		foreach ($arra as $key=>$value)
		{
			 if ($num==1)
			 {
			 $names=$key;
			 $values=$this->sqlValue($con, $value);
			 }
			 else
			 {
			 $names=$names.",".$key;
			 $values=$values.",".$this->sqlValue($con, $value);
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
