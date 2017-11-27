<?php


require_once(UTIL_DIR . '/trait/EmptyTrait.php');
require_once(UTIL_DIR . '/Logger.php');

class DbUtil {

	use EmptyTrait;
	private $logger;
	
	public function __construct() {
		$this->logger = new Logger();
	}
	
	/**
	 * PDOでDB接続
	 * @param unknown $dsn
	 * @param unknown $user
	 * @param unknown $password
	 * @throws Exception
	 * @return PDO
	 */
	public function connect($dsn, $user, $password) {
		if ($this->isEmpty($dsn) || $this->isEmpty($user) || $this->isEmpty($password)) {
			throw new Exception('Connection profile is not enough');
		}
		
		try{
			$dbh = new PDO($dsn, $user, $password);
			return $dbh;
		}catch (PDOException $e){
			print('Error:'.$e->getMessage());
			die();
		}
	}

	/**
	 * DSN文字列の作成
	 * @param unknown $host
	 * @param unknown $dbName
	 * @param number $port
	 * @return string
	 */
	public function createDsn($host, $dbName, $port = 5432) {
		$dsn = 'pgsql:dbname='.$dbName.';host='.$host.';port='.$port;
		return $dsn;
	}
	
	/**
	 * QUERYを実施して行を配列で返す
	 * @param PDO $dbh
	 * @param unknown $sql
	 * @return mixed[]
	 */
	public function getRows(PDO $dbh, $sql) {
		$stmt = $dbh->query($sql);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $rows;
	}
	
	/**
	 * 更新SQLを実施する
	 * @param PDO $dbh
	 * @param unknown $prepareSql
	 * @param unknown $params
	 */
	public function execUpdate(PDO $dbh, $prepareSql, $params) {
		try {
			$stmt = $dbh->prepare($prepareSql);
			$result = $stmt->execute($params);
			return $result;
		}catch (Exception $e){
			//print('Error:'.$e->getMessage());
			$this->logger->debugLog($e->getMessage());
			die();
		}
	}
	
	
	
}