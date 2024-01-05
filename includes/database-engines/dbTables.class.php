<?php
/* WELL TESTED and MAINTAINED */
// BLP 2023-01-31 - added $db->dbTables and made $db a reference.

define("DBTABLE_CLASS_VERSION", "1.0.1dbTables-pdo"); // BLP 2023-01-31 - 

// Make database tables given either a SiteClass or Database class object.

class dbTables {
  private $db;

  /**
   * @param object|class $db. Can be either SiteClass or Database class.
   */
  
  public function __construct(&$db) { // BLP 2023-01-31 - make $db a reference
    $this->db = $db;
    $db->dbTables = $this->getVersion(); // BLP 2023-01-31 - add the version to $db
  }

  public static function getVersion() {
    return DBTABLE_CLASS_VERSION;
  }
  
  /**
   * makeresultrows
   * Like maketbodyrows() but with different argument symantics. USE THIS INSTEAD of maketbodyrows()
   * The $rowdesc can have a wild card like this: '<tr><td>*</td></tr>'. Then make the $extra[delim] be
   *   array("<td>", "</td>");
   * Can also have a header like '<table><thead>%<th>*</th>%</thead>'. The header delimiter is always %.
   * In both cases the fields from the query will replace the '*'.
   * Make the query fields what you want in the header using the 'as' keywork.
   * @param string $query
   * @param string $rowdesc
   * @param array $extra : $extra[delim] is an array|string with the delimiter,
   *                       $extra[return] if true the return value is an ARRAY else just a string with the rows
   *                       $extra[callback] is a callback function: calback(&$row, &$desc);
   *                       $extra[callback2] callback after $desc has the fields replaced with $row values.
   *                       $extra[header] a header template. Delimiter is % around for example '%<th>*</th>%'
   * @return string|array
   *         if $extra[return] === true then returned is an
   *            array({the row string}, {result}, {num}, {header},
   *                   rows=>{row string}, result=>{result}, num=>{number of rows}, header=>{header})
   *         else a string with the rows
   */
  
  public function makeresultrows(string $query, string $rowdesc, array $extra=array()):mixed {
    $num = $this->db->sql($query); 

    if(!$num) {
      return false; // Query found NO rows.
    }

    // A call back could do a select so we need to keep this local!!
    
    $result = $this->db->getResult(); 

    $delim = $extra['delim'];

    // Set up delimiters

    $rdelimlft = $rdelimrit = "";

    if(!$delim) {
      // Default delimiters are >< as in <td>...</td>. In this case we replace on the right side with the delimiters
      $sdelimlft = $rdelimlft = $rwilddelimlft = ">";
      $sdelimrit = $rdelimrit = $rwilddelimrit = "<";
    } else {
      // Not empty. Is $delim an array?
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

    // USE local $result!!!
    
    while($row = $this->db->fetchrow($result, 'assoc')) {
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
        $v = $v ?? ''; // BLP 2022-02-02 -- for php 8.1. If null trows a depreciated error.
        if(preg_match("~\\\\0~i", $v, $m)) {
          $v = preg_replace("~\\\\0~i", '~~0', $v);
        }
        $desc = preg_replace("/{$sdelimlft}{$k}{$sdelimrit}/", "{$rdelimlft}{$v}{$rdelimrit}", $desc);
      }
      if(preg_match("|~~0|", $desc)) {
        $desc = preg_replace("|~~0|", "\\\\0", $desc);
        //echo "DESC: $desc\n\n";
      }
      // callback2 can modify the $desc after the fields have been replaced
      
      if($extra['callback2']) {
        $extra['callback2']($desc);
      }

      $rows .= "$desc";
    }
    if($extra['return'] === true) {
      $ret = array($rows, $this->result, $num, $hdr, 'rows'=>$rows,
                   'result'=>$this->result, 'num'=>$num, 'header'=>$hdr);
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
   *   $extra[callback2] callback after $desc has the fields replaced with $row values.
   *   $extra['footer'] a footer string 
   * @return array [{string table}, {result}, {num}, {hdr}, table=>{string}, result=>{result}, num=>{num rows}, header=>{hdr}]
   * or === false
   */

  public function maketable(string $query, array $extra=null):array|bool {
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
                                 ['return'=>true, 'callback'=>$callback,
                                  'callback2'=>$callback2, 'header'=>$table, 'delim'=>$delim]);

    if($tbl === false) {
      return false;
    }

    extract($tbl); // $rows, $result, $num, $header

    $ftr = $extra['footer'] ? "<tfoot>\n{$extra['footer']}\n</tfoot>\n" : null;

    $ret = <<<EOF
$header
<tbody>
$rows$ftr</tbody>
</table>

EOF;

    // return both a numeric and assoc array.
    // if all you want is the table you can do $T->maketable(...)[0];
    return [$ret, $result, $num, $header, 'table'=>$ret, 'result'=>$result, 'num'=>$num, 'header'=>$header];
  }
}
