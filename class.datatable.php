<?php
class Datatable{
  private $data;
  private $count;
  private $index;
  
  function __construct($result){
    $this->data = $result;
    $this->count = count($result);
    $this->index = -1;
  }
  
  function Row($index){
      if ($index < 0 || $index >= $this->count)
          throw new Exception("Row index ($index) is out of bounds");
      return $this->data[$index];
  }
  
  function Count(){
    return $this->count;
  }

  function First(){
    if($this->count){
      $this->index = 0;
      return true;
    } else {
      $this->index = -1;
      return false;
    }
  }

  function Next(){
    if($this->index < $this->count - 1){
      $this->index++;
      return true;
    } else {
      $this->index = $this->count;
      return false;
    }
  }

  function Previous(){
    if($this->index > 0){
      $this->index--;
      return true;
    } else {
      $this->index = -1;
      return false;
    }
  }

  function Last(){
    if($this->count){
      $this->index = $this->count - 1;
      return true;
    } else {
      $this->index = 0;
      return false;
    }
  }

  function __get($member){
    if ($this->index < 0 || $this->index >= $this->count)
      throw new Exception("Row index ($this->index) is out of bounds");

    $row = $this->data[$this->index];
    if (!array_key_exists($member, $row))
      throw new Exception("Column ($member) does not exist");
    else
      return $row[$member];
  }

  function __call($method, $param){
    if($method == "Field"){
      switch (count($param)) {
	      case 0:
          throw new Exception("Method Field() does not accept 0 arguments");
	      case 1:
          $index = $this->index;
          $col = $param[0];
          break;
	      default:
          $index = $param[0];
          $col = $param[1];
          break;
        }

        $row = $this->Row($index);
        if (!array_key_exists($col, $row))
          throw new Exception("Column ($col) does not exist");
        else
          return $row[$col];
      }
  }
}
?>
