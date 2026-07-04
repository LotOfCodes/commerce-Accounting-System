<?php
require_once '../ini.php';
class mysql
{
	private $con;
	function __construct()
	{	
		$ini_ = new ini();
		if (phpversion()>=80000 )
		{
			$this->con = mysqli_connect($ini_->mySqlServer,$ini_->mySqlUser,$ini_->mySqlPass);
		}
		else
		{
			//echo 'server:',$ini_->mySqlServer,',User:',$ini_->mySqlUser,',Pass:',$ini_->mySqlPass;
			$this->con = mysqli_connect($ini_->mySqlServer,$ini_->mySqlUser,$ini_->mySqlPass);
		}
		if (!$this->con)
		{
			$JsonArray = array(
				'code' => 'fail', 
				'txt' => '连接数据库发生错误：“'. mysqli_error()."。”"
			);  
			echo json_encode($JsonArray);
			exit;
		} 
	}
	function execute($sqlcomand)
	{
		$ini_ = new ini();
		
		$db_selected = mysqli_select_db($this->con,$ini_->mySqlDataBase);	
		mysqli_query($this->con,"set names 'UTF8'");
		$sql    = $sqlcomand;  
		//echo $sql.'<br>'.strpos($sql,"select").'<br>';
		
		if (strpos(strtolower($sql),"select")!=-1)
		{
			$result = mysqli_query($this->con,$sql);
			//$row    = mysqli_fetch_assoc($result);
			//echo "select".'<br>';
			return $result;
		}
		else
		{
			try
			{
				mysqli_query($this->con,$sql);
				@mysqli_close($this->con);
				return true;
			}
			catch(Exception $e)
			{
				@mysqli_close($this->con);
				return false;
			}
		}
		return false;
	}
	function close()
	{
		@mysqli_close($this->con);
	}
}
?>