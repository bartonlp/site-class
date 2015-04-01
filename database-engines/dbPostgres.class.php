<?php
   // Work in progress not tested yet

class dbPostgreSql extends dbAbstract{

  protected $dbType='PostgreSql';     

  public function connect($dbHost,$dbUser,$dbPass,$dbName=false){
    $this->dbhost=$dbHost;
    $this->dbuser=$dbUser;
    $this->dbpass=$dbPass;
    if ($this->check_exists('mysqli_connect')) 
      $this->link=@pg_connect($cStr=$this->connectionString());
    if(!$this->link) $this->error('Connection Error : '.$cStr);
    if($dbName){
      $this->select_db($dbName);
    }
    return $this->link;
  }

  private function connectionString(){
    list($host,$port)=explode(':',$this->dbhost);
    $str='host='.$host;
    if($port)
      $str.=' port='.$port;
    if($this->dbuser)
      $str.=' user='.$this->dbuser;
    if($this->dbname)
      $str.=' dbname='.$this->dbname;
    if ($this->dbpass)
      $str.=' password='.$this->dbpass;

    return $str;
  }

  public function select_db($dbName){
    $this->dbname=$dbName;
    @pg_select($this->link,$dbName);
  }

  public function  query($sql) {
    parent::query($sql);
    $sql=preg_replace("#LIMIT ([0-9]+),([ 0-9]+)#i", "LIMIT $2 OFFSET $1", $sql);
    return $this->lastQuery=@pg_query($this->link,$sql)  OR $this->error('Query Error : '.$this->lastSQL); 
  }

  /**
   * Return a specific field from a query.
   *
   * @param resource The query ID.
   * @param string The name of the field to return.
   * @param int The number of the row to fetch it from.
   */
  function fetch_field($query, $field, $row=false) {
    if($row === false) {
      $array = $this->fetch_assoc($query);
      return $array[$field];
    } else {
      return pg_fetch_result($query, $row, $field);
    }
  }


  function table_exists($tableName) {
    $err = $this->error_reporting;
    $this->error_reporting = 0;

    $query = $this->query("SELECT COUNT(table_name) as table_names FROM information_schema.tables WHERE table_schema = 'public' AND table_name='{$table}'");

    $exists = $this->fetch_field($query, 'table_names');
    $this->error_reporting = $err;

    if($exists > 0) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Check if a field exists in a database.
   *
   * @param string The field name.
   * @param string The table name.
   * @return boolean True when exists, false if not.
   */
  function field_exists($field, $table) {
    $query = $this->write_query("SELECT COUNT(column_name) as column_names FROM information_schema.columns WHERE table_name='{$table}' AND column_name='{$field}'");

    $exists = $this->fetch_field($query, "column_names");

    if($exists > 0) {
      return true;
    } else {
    return false;
    }
  }

  /**
   * Return the last id number of inserted data.
   *
   * @return int The id number.
   */
  function insert_id() {
    $this->lastSQL = str_replace(array("\r", "\n", "\t"), '', $this->lastSQL);
    preg_match('#INSERT INTO ([a-zA-Z0-9_\-]+)#i', $this->lastSQL, $matches);

    $table = $matches[1];

    $query = $this->query("SELECT column_name FROM information_schema.constraint_column_usage WHERE table_name = '{$table}' and constraint_name = '{$table}_pkey' LIMIT 1");
    $field = $this->fetch_field($query, 'column_name');

    if(!$field) {
      return;
    }

    $id = $this->query("SELECT currval('{$table}_{$field}_seq') AS last_value");
    return $this->fetch_field($id, 'last_value');
  }

  public function error($msg) {
    echo  $msg.' '.$this->pg_error();
  }

  private function pg_error() {
    return @pg_last_error($this->link);
  }

  public function fetch_assoc($query=false) {
    return @pg_fetch_assoc($query ? $query : $this->lastQuery);
  }

  public function fetch_row($query=false) {
    return @pg_fetch_row($query ? $query:$this->lastQuery);
  }

  public function fetch_object($query=false) {
    return @pg_fetch_object($query?$query:$this->lastQuery);
  }

  public function fetch_all($query=false) {
    $all=array();  
    while ($alls=$this->fetch_assoc()) {
      $all[]=$alls;
    }
    return $all;
  }

  function free($query=false) {
    return @pg_free_result($query?$query:$this->lastQuery);
  }

  public function close() {
    return @pg_close($this->link);
  }

  public function drop_table($tableName) {
    return $this->query('DROP TABLE '.$tableName);
  }

  public function drop_fields($tableName,$fieldNames) {
    return $this->query('ALTER TABLE '.$tableName.' drop ('.(is_array($fieldNames)?implode(',',$fieldNames) :$fieldNames).')');
  }
}
// WARNING THERE MUST BE NOTHING AFTER THE CLOSING PHP TAG.
// Really nothing not even a space!!!!
?>