<?php
/* DEPRECIATED. Use dbMysqli.class.php instead. DEPRECIATED */

/**
 * Database Class
 *
 * General MySql Database Class. 
 * @package Database
 * @author Barton Phillips <barton@bartonphillips.com>
 * @version 1.0
 * @link http://www.bartonphillips.com
 * @copyright Copyright (c) 2010, Barton Phillips
 * @license http://opensource.org/licenses/gpl-3.0.html GPL Version 3
 */

 // Define some of the mysql error codes
define(MYSQL_ER_DUP_ENTRY, 1062); // duplicate key: msg=Duplicate entry '%s' for key %d 
define(MYSQL_ER_NO_SUCH_TABLE, 1146); // table does not exist: meg=Table '%s.%s' doesn't exist 

/**
 * @package Database
 */

class dbMysql extends dbAbstract {
  /**
   * MySql Database Link Identifier
   * @var resource $db
   */
  
  public $db = 0;

  protected $host, $user, $password, $database;
  private $result;
  
  /**
   * Constructor
   * @param string $host host name like "localhost:3306" etc.
   * @param string $user user name for database
   * @param string $password user's password for database
   * @param string $database name of the database
   *
   * as a side effect opens the database, that is connects and selects the database
   */

  public function __construct($host, $user, $password, $database) {
    if(!$host || !$user || !$password || !$database) {
      $this->nodb = true;
      return;
    }
    $this->host = $host;
    $this->user = $user;
    $this->password = $password;
    $this->database = $database;
    $this->opendb();
  }
  
  /**
   * Connects and selects the database
   * @return resource MySQL link identifier
   * On Error outputs message and exits.
   */
  
  protected function opendb() {
    // Only do one open
    
    if($this->db) {
      return $this->db;
    }

    $db = @mysql_connect($this->host, $this->user, $this->password, true);

    if(!$db) {
      throw new SqlException(__METHOD__ . ": Can't connect to database", $this);
    }

    $this->db = $db; // set this right away so if we get an error below $this->db is valid

    if(!@mysql_select_db($this->database, $db)) {
      throw new SqlException(__METHOD__ . " Can't select database", $this);
    }
    return $db;
  }

  /**
   * Query database table
   * @param string $query SQL statement.
   * @param bool retarray default false. If true then returns an array with result, num_rows
   * @return mixed result-set for select etc, true/false for insert etc.
   * On error calls SqlError() and exits.
   */

  public function query($query, $retarray=false) {
    $db = $this->opendb();

    $result = mysql_query($query, $db);

    if(!$result) {
      //echo "error=" . mysql_error($db) . "<br>error code=" . mysql_errno($db) . "<br>";
      throw new SqlException($query, $this);
    }

    $this->result = $result;
    
    if($retarray) {
      if(!preg_match("/^(?:select|show)/i", $query)) {
        //echo "NOT SELECT<br>";
        $numrows = mysql_affected_rows($db);
      } else {
        //echo "SELECT: query=$query, result=$result<br>";
        $numrows = mysql_num_rows($result);
      }
      return array($result, $numrows, result=>$result, numrows=>$numrows);
    } else {
      return $result;
    }
  }

  /**
   * queryfetch()
   * Dose a query and then fetches the associated rows
   * Does a fetch_assoc() and places each row array into an array.
   * @param string, the query
   * @return array, the rows
   */
  
  public function queryfetch($query, $returnarray=false) {
    list($result, $numrows) = $this->query($query, true);
    
    if(!$result) {
      throw new SqlException($query, $this);
    }

    while($row = mysql_fetch_assoc($result)) {
      $rows[] = $row;
    }
    return ($returnarray) ? array($result, $numrows, result=>$result, numrows=>$numrows) : $rows;
  }

    /**
   * fetchrow()
   * @param resource identifier returned from query.
   * @param string, type of fetch: assoc==associative array, num==numerical array, or both
   * @return array, either assoc or numeric, or both
   * NOTE: if $result is a string then it is the type and we use $this->result for result.
   */
  
  public function fetchrow($result=null, $type="both") {
    if(is_string($result)) {
      $type = $result;
      $result = $this->result;
    } elseif(!$result) {
      $result = $this->result;
    }
    
    if(!$result) throw new SqlException(__METHOD__ . ": result is null", $this);

    switch($type) {
      case "assoc": // associative array
        return mysql_fetch_assoc($result);
      case "num":  // numerical array
        return mysql_fetch_row($result);
      case "both":
        return mysql_fetch_array($result);
    }
  }

  /**
   * getLastInsertId()
   *
   */

  public function getLastInsertId() {
    $db = $this->opendb();
    return mysql_insert_id($db);
  }

  /**
   * getNumRows()
   */

  public function getNumRows($result=null) {
    if(!$result) $result = $this->result;
    return mysql_num_rows($result);
  }
  
  /**
   * Get the Database Resource Link Identifier
   * @return resource link identifier
   */
  
  public function getDb() {
    return $this->db;
  }

  /**
   * Make Tbody Row
   *
   * Make a table row given the query and a template of the row
   * Call back function looks like: callback(&$row, &$rowdesc) it can modify $row and $rowdesc and
   * returns true if we should skip (continue) or false to process.
   * @param string $query the mysql query 
   * @param string $rowdesc the tbody row description
   * @param function $callback an optional callback function like callback(&$row, &$rowdesc)
   * @param resouce &$retresult an optional return of resource id if $retresult === true
   * @param string|array $delimiter
   * @return string tbody or false if mysql_num_rows() == 0. Should check return === false for no rows.
   */

  public function maketbodyrows($query, $rowdesc, $callback=null, &$retresult=false, $delim=false) {
    // $rowdesc is the <tr>...</tr> for this row
    // <tr><td>fieldname</td>...</tr>

    list($result, $num) = $this->query($query, true);
    if(!$num) {
      return false;
    }
      
    if($retresult !== false) $retresult = $result;

    // Set up delimiters

    $rdelimlft = $rdelimrit = "";
    
    if(!$delim) {
      $sdelimlft = $rdelimlft = ">";
      $sdelimrit = $rdelimrit = "<";
    } else {
      if(is_array($delim)) {
        $sdelimlft = $delim[0];
        $sdelimrit = $delim[1];
      } else {
        $sdelimlft = $sdelimrit = $delim;
      }
    }

    $table = ""; // return tbody
    
    while($row = mysql_fetch_assoc($result)) {
      // If $callback then do the callback function. If the callback function returns true (continue) skip row.

      $desc = $rowdesc;
      
      if($callback) {
        // Callback function can modify $row and/or $desc if the callback function has them passed by reference
        // NOTE that $desc does not have the keys replaced with the values yet!!
        if($callback($row, $desc)) {
          continue;
        }
      }

      // Replace the key in the $desc with the value.
      
      foreach($row as $k=>$v) {
        $desc = preg_replace("/{$sdelimlft}{$k}{$sdelimrit}/", "{$rdelimlft}{$v}{$rdelimrit}", $desc);
      }
      $table .= "$desc\n";
    }
    return $table; // on success return the tbody rows
  }

  /**
   * makeresultrows
   * Like maketbodyrows() but with different argument symantics
   * The $rowdesc can have a wild card like this: '<tr><td>*</td></tr>'. Then make the $extra[delim] be
   *   array("<td>", "</td>"); 
   * Can also have a header like '<table><thead>%<th>*</th>%</thead>'. The header delimiter is alway %.
   * In both cases the fields from the query will replace the '*'.
   * Make the query fields what you want in the header using the 'as' keywork.
   * @param string $query
   * @param string $rowdesc
   * @param array $extra : $extra[delim] is an array|string with the delimiter, default is '>', '<'.
   *                       $extra[return] if true the return value is an ARRAY else just a string with the rows
   *                       $extra[callback] is a callback function: calback(&$row, &$desc);
   *                       $extra[callback2] callback after $desc has the fields replaced with $row values.
   *                       $extra[header] a header template. Delimiter is % around for example '%<th>*</th>%'
   * @return string|array
   *         if $extra[return] === true then returned is an
   *            array({the row string}, {result}, {num}, {header},
   *                   rows=>{row string}, result=>{result}, num=>{number of rows etc}, header=>{header})
   *         else a string with the rows
   */
  
  public function makeresultrows($query, $rowdesc, array $extra=array()) {
    list($result, $num) = $this->query($query, true); // $num is mysql_num_rows() result

    if(!$num) {
      return false; // Query found NO rows.
    }

    $delim = $extra['delim'];

    // Set up delimiters

    $rdelimlft = $rdelimrit = "";

    if(!$delim) {
      // Default delimiters are >< as in <td>...</td>. In this case we replace on the right side with the delimiters
      $sdelimlft = $rdelimlft = $rwilddelimlft = ">";
      $sdelimrit = $rdelimrit = $rwilddelimrit = "<";
    } else {
      // Not empty. It $delim an array?
      if(is_array($delim)) {
        // When we have a delimiter we don't replace on the right side unless we have the wild card.
        // There should be two elements to the array, The left and right delim
        $sdelimlft = $rwilddelimlft = $delim[0];
        $sdelimrit = $rwilddelimrit = $delim[1];
      } else {
        // If $delim is a string
        $sdelimlft = $sdelimrit = $rwilddelimrit = $rwilddelimlft = $delim;
      }
    }

    // In case the search delimiters have '/' in them make them safe
    // The replace delimiters don't need this fix (and should NOT get it)
    
    $sdelimlft = preg_replace("|/|", "\/", $sdelimlft); 
    $sdelimrit = preg_replace("|/|", "\/", $sdelimrit);

    $mkrow = true;

    $rows = ""; // return tbody
    
    while($row = mysql_fetch_assoc($result)) {
      if($mkrow) {
        $tmp = "";
        $keys = array_keys($row);

        foreach($keys as $k) {
          $tmp .= "%$k~";
        }

        // Is there a header?
        
        if($extra['header']) {
          $header = $extra['header'];

          // Find the header delimeters
          if(preg_match("/%(.*?)\*(.*?)%/", $header, $m)) {
            $hdelimlft = $m[1];
            $hdelimrit = $m[2];

            // remove the % delimiters
            $header = preg_replace("/%(.*?\*.*?)%/", "$1", $header);
            //echo "<br>AFTER: " .escapeltgt("header=$header || lft=$hdelimlft, rit=$hdelimrit") . "<br>\n";
          }
          
          $hdr = preg_replace("/%(.*?)~/", "{$hdelimlft}$1{$hdelimrit}", $tmp);

          //echo "<br>Tmp: " . escapeltgt($tmp) . "<br>\n";
          //echo "<br>Hdr: " .escapeltgt($hdr) . "<br>\n";
          
          $hdelimlft = preg_replace("|/|", "\/", $hdelimlft); 
          $hdelimrit = preg_replace("|/|", "\/", $hdelimrit);

          $hdr = preg_replace("/{$hdelimlft}\*{$hdelimrit}/", $hdr, $header);
          //echo "<br>Final HDR: " .escapeltgt($hdr) . "<br>\n";
        } else {
          // NO header so just use $tmp which has a list of the fields as '%filed~...'
          
          $hdr = $tmp;
        }

        /*
        echo "<br>tmp: " . escapeltgt($tmp) . "<br>\n";
        echo "<br>delims: " . escapeltgt("sdelims=$sdelimlft, $sdelimrit, rdelims=$rwilddelimlft, $rwilddelimrit") . "<br>\n";

        echo "<br>1 rowdesc: " . escapeltgt($rowdesc) . "<br>\n";
        */

        // Only if we have the wild card
        
        if(preg_match("/{$sdelimlft}\*{$sdelimrit}/", $rowdesc)) {
          $rowdesc = preg_replace("/{$sdelimlft}\*{$sdelimrit}/", $tmp, $rowdesc);

          //echo "<br>2 rowdesc: " . escapeltgt($rowdesc) . "<br>\n";
        
          $rowdesc = preg_replace("/%(.*?)~/", "{$rwilddelimlft}$1{$rwilddelimrit}", $rowdesc);
        
          //echo "<br>3 rowdesc: " . escapeltgt($rowdesc) . "<br>\n";

          $rdelimlft = $rwilddelimlft;
          $rdelimrit = $rwilddelimrit;
        }
        //echo "RDELIM: " . escapeltgt("$rdelimlft, $rdelimrit<br>\n");

        $mkrow = false;
      }
      
      $desc = $rowdesc;

      // If $callback then do the callback function. If the callback function returns true (continue) skip row.
      
      if($extra['callback']) {
        // Callback function can modify $row and/or $desc if the callback function has them passed by reference
        // NOTE that $desc does not have the keys replaced with the values yet!!
        if($extra['callback']($row, $desc)) {
          continue;
        }
      }

      // Replace the key in the $desc with the value.
      
      foreach($row as $k=>$v) {
        $desc = preg_replace("/{$sdelimlft}{$k}{$sdelimrit}/", "{$rdelimlft}{$v}{$rdelimrit}", $desc);
      }

      // callback2 can modify the $desc after the fields have been replaced
      
      if($extra['callback2']) {
        $extra['callback2']($desc);
      }

      $rows .= "$desc\n";
    }
    if($extra['return'] === true) {
      $ret = array($rows, $result, $num, $hdr, rows=>$rows, result=>$result, num=>$num, header=>$hdr);
    } else {
      $ret = $rows;
    }
    return $ret; // on success return the tbody rows
  }

  /**
   * Make a full table
   *
   * @param string $query : the table query
   * @param array $extra : optional. 
   *   $extra is an optional assoc array: $extra['callback'], $extra['callback2'], $extra['footer'] and $extra['attr'].
   *   $extra['attr'] is an assoc array that can have attributes for the <table> tag, like 'id', 'title', 'class', 'style' etc.
   *   $extra['callback'] function that can modify the header after it is filled in.
   *   $extra['footer'] a footer string 
   * @return array [{string table}, {result}, {num}, {hdr}, table=>{string}, result=>{result}, num=>{num rows}, header=>{hdr}]
   * or === false
   */

  public function maketable($query, array $extra=null) {
    $table = "<table";
    if($extra['attr']) {
      $attr = $extra['attr'];
      foreach($attr as $k=>$v) {
        $table .= " $k='$v'";
      }
    }
    $table .= ">\n<thead>\n<tr>%<th>*</th>%</tr>\n</thead>";
    $rowdesc = "<tr><td>*</td></tr>";
    $delim = array("<td>", "</td>");
    $callback = $extra['callback']; // Before
    $callback2 = $extra['callback2']; // After
    
    $tbl = $this->makeresultrows($query, $rowdesc,
                                 array('return'=>true, callback=>$callback,
                                       callback2=>$callback2, header=>$table, delim=>$delim));

    if($tbl === false) {
      return false;
    }
    
    extract($tbl);

    $ftr = $extra['footer'] ? "<tfoot>\n{$extra['footer']}\n</tfoot>\n" : null;

    $ret = <<<EOF
$header
<tbody>
$rows</tbody>
$ftr
</table>

EOF;

    return array($ret, $result, $num, $header, table=>$ret, result=>$result, num=>$num, header=>$header);
  }

  // Class method to do real_escape_string.
  
  public function escape($string) {
    if(get_magic_quotes_runtime()) {
      $string = stripslashes($string);
    }
    return @mysql_real_escape_string($string);
  } 

  public function __toString() {
    return __CLASS__;
  }
} // End of Class
