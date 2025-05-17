<?php
// ChatCPT BLP 2025-05-11

define("DBTABLE_CLASS_VERSION", "1.0.2dbTables-pdo");  

// Make database tables given either a SiteClass or Database class object.

class dbTables {
  private $db;

  /**
   * @param class $db. Can be either SiteClass, Database or dbPdo class.
   */
  
  public function __construct(&$db) { // BLP 2023-01-31 - make $db a reference
    $this->db = $db;
    $db->dbTables = $this->getVersion(); // BLP 2023-01-31 - add the version to $db
  }

  public static function getVersion() {
    return DBTABLE_CLASS_VERSION;
  }

  /**
   * getrows
   * Simple row extraction with a callback function.
   * @param string $query
   * @param callback function default to null
   * @return string|null. If no data found returns null, else the body rows of a table.
   * On error throws an exception from sql().
   * The callback function argument is the row passed by reference (&$row).
   * The row has a <tr> start and <td>...</td> values followed by a </tr>.
   * The &$row is a side effect of the callback and becomes the new $row.
   **/

  public function getrows($query, $callback = null):string|null {
    $rows = null;

    $num = $this->db->sql($query);

    // If no date return null
    
    if($num === 0) {
      return $rows; // NULL
    }

    // Loop through all the rows.
    
    while($row = $this->db->fetchrow('num')) {
      $tmp = '';

      // Loop through the $row array
      
      foreach($row as $v) {
        // $v is the row item surround it with <td>...</td>
        
        $tmp .= "<td>$v</td>";
      }
      // Do the call back if not null. The function gets $tmp as a referrence.
      
      if($callback) $callback($tmp);

      // If the callback was called $tmp has been modified
      
      $rows .= "<tr>$tmp</tr>";
    }
    // return the full body of a table. There is not <tbody>...</tbody> just <tr> lines.
    
    return $rows;
  }

  /**
   * Make rows
   *
   * This is new logic.
   * The `<thead>` will have the `$query` field names as the `<th>` values. Use the `as`,
   * e.g. `id as 'ID addr'.
   * The `$extra['rowdesc'] defaults to `<tr>` but you could add a class or style.
   *
   * @param string $query
   * @param array $extra Default to an empty array.
   *   $extra[rowdesc] is a string like `<tr .*?>`. The default is `<tr>`.
   *   $extra[callback] is a callback function: `calback(&$row, &$desc)`;
   * @return array
   *   The first three are numeric and the last two are associative arrays.
   *   `{the row string}, {the num-rows}, {the statement}, {'rows'=>$rows}, {'aliases'=>$aliases}`
   */
  public function makerows(string $query, array $extra=[]): array|false {
    $num = $this->db->sql($query);
    if(!$num) return false;

    $result = $this->db->getResult();

    $rowdesc = $extra['rowdesc'] ?? "<tr>";
    $prefix = $extra['prefix'] ?? '';
    $callback = $extra['callback'];

    $rows = "";
    $firstTime = true;
    $aliases = [];

    while($row = $this->db->fetchrow($result, 'assoc')) {
      // Build <td> cells from updated $row
      $cells = [];
      foreach($row as $alias=>$val) {
        if($firstTime) {
          $aliases[] = $alias;
        }

        $newRowKey = $aliasClass = str_replace(" ", '_', $alias); // Sanitize spaces

        $class = $prefix . $aliasClass;
        $cells[$aliasClass] = "<td class='$class'>";

        $newRow[$newRowKey] = $val;
      }

      $row = $newRow;

      // Call callback before rendering

      if(is_callable($callback)) {
        $callback($cells, $row, $rowdesc); // Usually by reference in caller.
        echo "callback rowdesc=".escapeltgt($rowdesc)."<br>";
      } else {
        echo "rowdesc=".escapeltgt($rowdesc)."<br>";
      }
      $tmp = '';

      foreach($row as $key=>$val) {
        //echo "value=".escapeltgt($cells[$key])."<br>";
        // The cell item always looks like "<td class='{value}'>"
        //           (<)(td/th)( class=')({value}'>)
        //            1   2      3       4
        preg_match("~(.)(.*?)( class=')(.*?)'~", $cells[$key], $m);
        //                                <    td/th  " class='" info+  rest
        //                                1      2      3        4       5
        $tmp .= preg_replace_callback("~($m[1])($m[2])($m[3])($m[4].*?)(.*?)>~", function($m) use ($val) {

          //       '<'   'td/th' "' class='" 
          //        |      |       |      'class''extra''val' <     'th/td'
          //        \/     \/      \/    \/       \/    \/    \/    \/
          $line = "{$m[1]}{$m[2]}{$m[3]}{$m[4]}{$m[5]}>$val{$m[1]}/{$m[2]}>";
          //echo "line=". escapeltgt($line) . "<br>";
          return $line; // tmp is the new "<td ...>$val</td>
        }, $cells[$key]);
      }

      //vardump("After regex tmp", $tmp);

      $rows .= "$rowdesc{$tmp}</tr>";

      //vardump("rows", $rows);

      $firstTime = false;
    }

    return [
            $rows,
            $num,
            $result,
            'rows' => $rows,
            'aliases' => $aliases,
           ];
  }

  /**
   * Make result rows
   *
   * This is the older refactored method.
   *
   * @param string $query
   * @param string $rowdesc
   * @param array $extra
   *   $extra[rowdesc] is a string like `<tr .*?>`. The default is `<tr>`.
   *   $extra[callback] is a callback function: calback(&$row, &$desc);
   *   $extra[callback2] callback after $desc has the fields replaced with $row values.
   * @return array
   *   {the row string}, {result}, {num}, {header},
   *       rows=>{row string}, result=>{result}, num=>{number of rows}, header=>{header})
   *     else a string with the rows, i.e. `<tr><td>...</td>...</tr>`.
   */
  public function makeresultrows(string $query, array $extra=[]): array|false {
    $num = $this->db->sql($query);

    if(!$num) return false;

    $result = $this->db->getResult();

    $rowdesc = $extra['rowdesc'] ?? "<tr>";
    $prefix = $extra['prefix'] ?? '';
    $callback = $extra['callback']; // If these are null the $callback is null.
    $callback2 = $extra['callback2'];

    $rows = "";
    $firstTime = true;

    while($row = $this->db->fetchrow($result, 'assoc')) {
      $cells = '';

      // Take the row apart
      
      foreach(array_keys($row) as $alias) {
        // For the first row we get the row keys, the names of the fields.

        if($firstTime) {
          $aliases[] = $alias; // This array is used by maketable for the header.
        }

        $alias = preg_replace("~ ~", '-', $alias);
        $cells .= "<td class='{$prefix}$alias'>*</td>";

        if(is_callable($callback)) {
          // NULL added for backward compatability.
          // In new logic I will only have &$row but older logic expected `&$row, &$desc`.
        
          $dummy = ''; // Just a dummy.
          $callback($row, $dummy);
        }

        $desc = preg_replace_callback("~<(td|th) class='(.*?)'>\*</(td|th)>~",
                                      function($m) use ($row, $prefix) {
          $class = $m[2];
          $key = str_starts_with($class, $prefix) ? substr($class, strlen($prefix)) : $class;
          return "<{$m[1]} class='{$m[2]}'>{$row[$key]}</{$m[3]}>";
        }, $cells);
      }

      $desc = "{$rowdesc}$desc</tr>";
      
      $firstTime = false;

      if(is_callable($callback2)) {
        // The receiving program expects a reference &$desc and a value for $row if needed.
        // Sometime the receiver will just have the &$desc reference with no $row.
        
        $callback2($desc, $row); 
      }

      $rows .= "$desc\n";
    }

    return [
            $rows, // numeric key = 0. The rest are 'assoc' keys.
            'rows' => $rows,
            'result' => $result,
            'num' => $num,
            'aliases' => $aliases, // This is for the header in maketable().
           ];
  }
  
  // I think I should have two arrays, 'attr' and 'features'.
  // I should add hdrdesc.
  /**
   * Make a full table
   *
   * @param string $query The table query
   * @param array $extra  Optional. 
   *   `$extra` is an optional assoc array: $extra['callback'], $extra['callback2'], $extra['footer'] and $extra['attr'].
   *   `$extra['rowdesc'] will default to `<tr>*</tr>`. You could have `<tr class='xyz'>*</tr>` etc.
   *   `$extra['hdrdlim'] either `<th> or <td> as an array.
   *   `$extra['attr']` is an assoc array that can have attributes for the `<table>` tag, like 'id', 'title', 'class', 'style' etc.
   *   `$extra['callback']` function that can modify the header after it is filled in.
   *   `$extra['callback2']` callback after $desc has the fields replaced with $row values.
   *   `$extra['footer']` a footer string or function (closure).
   * @param bool $useNew If true use the new `makerows` method. Defaults to false.
   * @return array|false `[{full_table}, {result_stmt}, {num_rows},
   *   'table'=>{full_table}, 'result'=>{result-stmt}, 'num'=>{num_rows},
   *   'header'=>{table_hdr}, 'body'=>{table_body}, 'footer=>{table_footer}]`,
   *   or false.
   *   The first three items are numeric the rest are associative.
   */
  public function maketable(string $query, array $extra=[], bool $useNew=false): array|false {
    $attr = $extra['attr'] ?? [];
    $attrString = '';
    
    foreach($attr as $k => $v) {
      $attrString .= " $k='" . htmlspecialchars($v, ENT_QUOTES) . "'";
    }
    
    $rawHeader = "<table$attrString>
<thead>
<tr>%<th>*</th>%</tr>
</thead>";

    // Create all of the rows

    $options = [
                'rowdesc' => $extra['rowdesc'],
                'prefix' => $extra['prefix'],
                'callback' => $extra['callback'],
                'callback2' => $extra['callback2'], // Only in makeresultrows().
               ];

    if($useNew) {
      // Do the new logic
      
      $result = $this->makerows($query, $options);
    } else {
      // Do the old legacy logic
      
      $result = $this->makeresultrows($query, $options);
    }
               
    if(!$result) return false;

    // The $result['aliases'] is used to create the header.
    
    $ths = "";

    $hdrdelim = $extra['hdrdelim'] ?? ['<th>', '</th>'];
    
    foreach($result['aliases'] as $alias) {
      $ths .= "{$hdrdelim[0]}$alias{$hdrdelim[1]}";
    }
    
    $header = str_replace("%{$hdrdelim[0]}*{$hdrdelim[1]}%", $ths, $rawHeader);

    // The passed in $extra['footer'] can be a string or a function (closure) or nothing.

    if(isset($extra['footer'])) {
      $content = is_callable($extra['footer']) ? $extra['footer']() : $extra['footer'];
      $ftr = "<tfoot>\n$content\n</tfoot>\n";
    } else {
      $ftr = '';
    }

    $table = "{$header}
<tbody>
{$result['rows']}{$ftr}</tbody>
</table>
";

    return [
            $table, // The first three are numeric keys (0, 1, 2). The rest are 'assoc' keys.
            $result['result'],
            $result['num'],
            'table' => $table,
            'result' => $result['result'],
            'num' => $result['num'],
            'header' => $header,
            'body' => $result['rows'],
            'footer' => $ftr,
           ];
  }
}
