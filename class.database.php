<?php
require_once 'class.datatable.php';
class Database{	
  private $conn;
  private $stmt;
  
  private $type = 'pgsql';
  private $server = 'localhost';
  private $usr = 'userdb';
  private $pass = 'pasworddb';
  private $db = 'db';
  
  private $isConnected = false;
  
  function __construct() {
	  $config = array(
      'servertype'=>$this->type,
      'serverhost'=>$this->server,
      'database'=>$this->db,
      'username'=>$this->usr,
      'password'=>$this->pass
    );
	  $this->setPDO($config);
  }
	
  function setPDO($config) {
    try{
      $connString = sprintf(
        '%s:host=%s;dbname=%s',
		    $config['servertype'],
		    $config['serverhost'],
		    $config['database']
      );
			
      $this->conn = new PDO($connString,$config['username'],$config['password']);
      $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
      $this->isConnected = true;
	  } catch(PDOException $ex) {
      $err = $ex->getMessage();
      $this->isConnected = false;
      throw new Exception($err);
	  }
  }
	
  public function closeConnection(){
    $this->isConnected = false;
    $this->conn = null;
  }
    
  public function isConnected(){
    return $this->isConnected;
  }
	
  public function beginTransaction(){
    $this->conn->setAttribute(PDO::ATTR_AUTOCOMMIT,0);
    $this->conn->beginTransaction();
  }

  public function commitTransaction(){
    $this->conn->commit();
    $this->conn->setAttribute(PDO::ATTR_AUTOCOMMIT,1);
  }

  public function rollbackTransaction(){
    $this->conn->rollBack();
    $this->conn->setAttribute(PDO::ATTR_AUTOCOMMIT,1);
  }
	
  public function executeQuery($sql,$param = array()){
    try{
      if($this->conn){
        $this->stmt = $this->conn->prepare($sql);
      	$result = $this->stmt->execute($param);
        if (!$result) { return; }
      	if (!$this->stmt->columnCount()){
          if($result <= 0) {
        		return 0;
          } else {
            return $this->stmt->rowCount();
          }
    	  } else {
          $data = array();
          while ($row = $this->stmt->fetch(PDO::FETCH_BOTH)){ 
        		$data[] = $row;
          }
          return new Datatable($data);
    	  }
      } else {
      	throw new Exception('Invalid Connection');
      }
    } catch (PDOException $ex) {
      throw new Exception($ex->getMessage());
    }
  }
	
  public function rowCount(){
    return $this->stmt->rowCount();
  }
    
  public function getResult($strTable,$strFilter = '1=1',$arrFilterValue = array(),$strOrder = ''){
    if($this->type == 'pgsql'){
      $strTable = '"'.$strTable.'"';
    }
  	//Initial Query
    $strQuery = 'SELECT * FROM %s WHERE %s ';
    $strQuery = sprintf($strQuery,$strTable,$strFilter);
  	//Add Order By
	  if($strOrder != ''){
      $strQuery .= 'ORDER BY %s ';
      $strQuery = sprintf($strQuery,$strOrder);
  	}
    //execute
  	return $this->executeQuery($strQuery,$arrFilterValue);
  }
    
  public function getResultSingle($strTable,$strFilter = '1=1',$arrFilterValue = array()){
    if($this->type == 'pgsql'){
      $strTable = '"'.$strTable.'"';
    }
  	//Initial Query
    $strQuery = 'SELECT * FROM %s WHERE %s LIMIT 1';
    $strQuery = sprintf($strQuery,$strTable,$strFilter);
    //execute
  	return $this->executeQuery($strQuery,$arrFilterValue);
  }
    
  public function getResultPaging($strTable,$strFilter = '1=1',$arrFilterValue = array(),$strOrder = '',$intPage = 1,$intRowCountPerPage = 10){
  	$tmpTable = 'TMP';
    if($this->type == 'pgsql'){
      $strTable = str_replace('"', '', $strTable);
      $strTable = '"'.$strTable.'"';
      $tmpTable = '"TMP"';
    }
    //Initial Query
  	$strQuery = 'SELECT * FROM (SELECT * FROM %s WHERE %s ';
    $strQuery = sprintf($strQuery,$strTable,$strFilter);
  	//Add Order By
	  if($strOrder != ''){
      $strQuery .= 'ORDER BY %s ';
      $strQuery = sprintf($strQuery,$strOrder);
  	}
	  $strQuery .= ') AS ' . $tmpTable . ' ';
	  //Add Paging
	  $offset = ($intPage-1) * $intRowCountPerPage;
	  $strQuery .= 'LIMIT ' .$intRowCountPerPage.' OFFSET ' .$offset;
	  
	  return $this->executeQuery($strQuery,$arrFilterValue);
  }
	
  public function insertRow($arrParams){
    $arrParams['Table'] = str_replace('"', '', $arrParams['Table']);
    if($this->type == 'pgsql'){
      $arrParams['Table'] = '"'. $arrParams['Table'] . '"';
    }
    //Initial Query
  	$strQuery = 'INSERT INTO '. $arrParams['Table']. ' (';
		
	  //Add Column Insert
  	$items = $arrParams['Items'];
  	$idx = 0;
  	$count = count($items);
  	foreach($items as $item){
      $colom1 = '"'.$item['Column'].'",';
      $colom2 = '"'.$item['Column'].'") ';
      
      if($this->type != 'pgsql'){
        $colom1 = str_replace('"','',$colom1);
        $colom2 = str_replace('"','',$colom2);
      }
      
      $strQuery .= ($idx!=$count-1)?$colom1:$colom2;
      $idx++;
	  }
		
    //Add Value
    $idx = 0;
    $strQuery .= 'VALUES (';
    foreach($items as $item){
      $value1 = '?,';
      $value2 = '?) ';
      $strQuery .= ($idx!=$count-1) ? $value1 : $value2;
      $idx++;
    }
    	
    //Add Parameter Value
    $bindValue = array();
    foreach($items as $item){
      $bindValue[] = $item['Value'];
    }
    	
    return $this->executeQuery($strQuery,$bindValue);
  }
	
  public function updateRow($arrParams){
    $arrParams['Table'] = str_replace('"', '', $arrParams['Table']);
    if($this->type == 'pgsql'){
      $arrParams['Table'] = '"'. $arrParams['Table'] . '"';
    }
    $strQuery = 'UPDATE '. $arrParams['Table']. ' SET ';
  	$idx = 0;
	  $count = count($arrParams['Items']);
	  foreach($arrParams['Items'] as $item){
      $colom1 = '"'.$item['Column'].'" = ?,';
      $colom2 = '"'.$item['Column'].'" = ? ';
      
      if($this->type != 'pgsql'){
          $colom1 = str_replace('"','',$colom1);
          $colom2 = str_replace('"','',$colom2);
      }
      
      $strQuery .= ($idx!=$count-1) ? $colom1 : $colom2;
      $idx++;
	  }
	  $strQuery .= 'WHERE 1=1 ';
	  $idx = 0;
	  $count = count($arrParams['Where']);
	  if($count > 0 ){
      $strQuery .= 'AND ';
  	}
	  foreach($arrParams['Where'] as $clause){
      $clause1 = '("'. $clause['Column'] .'" '. $clause['SqlOperator'] .' ?) '. $clause['AndOr']. ' ';
      $clause2 = '("'. $clause['Column'] .'" '. $clause['SqlOperator'] .' ?) ';
      
      if($this->type != 'pgsql'){
        $clause1 = str_replace('"','',$clause1);
        $clause2 = str_replace('"','',$clause2);
      }
    
      $strQuery .= ($idx!=$count-1) ? $clause1 : $clause2;
      $idx++;
  	}
	
	  //Add Parameter Value
	  $bindValue = array();
	  foreach($arrParams['Items'] as $item){
      $bindValue[] = $item['Value'];
    }
  	foreach($arrParams['Where'] as $clause){
      $bindValue[] = $clause['Value'];
  	}
      
  	return $this->executeQuery($strQuery,$bindValue);
  }
	
  public function deleteRow($arrParams){
    $arrParams['Table'] = str_replace('"', '', $arrParams['Table']);
    if($this->type == 'pgsql'){
      $arrParams['Table'] = '"'. $arrParams['Table'] . '"';
    }
    $strQuery = 'DELETE FROM '. $arrParams['Table']. ' WHERE 1=1 ';
  	$idx = 0;
	  $count = count($arrParams['Where']);
	  if($count > 0 ){
      $strQuery .= 'AND ';
  	}
	  foreach($arrParams['Where'] as $clause){
      $clause1 = '("'. $clause['Column'] .'" '. $clause['SqlOperator'] .' ?) '. $clause['AndOr']. ' ';
      $clause2 = '("'. $clause['Column'] .'" '. $clause['SqlOperator'] .' ?) ';
      
      if($this->type != 'pgsql'){
        $clause1 = str_replace('"','',$clause1);
        $clause2 = str_replace('"','',$clause2);
      }
    
      $strQuery .= ($idx!=$count-1) ? $clause1 : $clause2;
      $idx++;
  	}
	  $bindValue = array();
    foreach($arrParams['Where'] as $clause){
      $bindValue[] = $clause['Value'];
    }
      
	  return $this->executeQuery($strQuery,$bindValue);
  }
}
?>
