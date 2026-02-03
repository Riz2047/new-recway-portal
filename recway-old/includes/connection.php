<?php 

$maintenance = 0;

if($maintenance == 1) {
    die('<div style="width: 100%; height: 100%; display: flex; justify-content: center; flex-direction: column; align-items: center">
<img src="../assets/images/maintenance.svg" width="25%">
<h3>Website is in maintenance! Try back in a moment.</h3></div>');
}

class Database{
	// private $server   = "mysql:host=mysql685.loopia.se;dbname=orderspi_se_db_1;charset=utf8mb4";
	// private $username = "devtest@o381611";
	// private $password = "devdb@o381611*99";
	private $server   = "mysql:host=localhost;port=3307;dbname=recway_old;charset=utf8mb4";
	private $username = "root";
	private $password = "";

	private $options  = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,);
	protected $conn;

	public function open(){
		try{
			$this->conn = new PDO($this->server,$this->username,$this->password,$this->options);
			return $this->conn;
		}catch(PDOException $e){
			echo "There is some problem in connection: " . $e->getMessage();
		}
	}

	public function close(){
		$this->conn = null;
	}
}

$pdo = new Database();


?>