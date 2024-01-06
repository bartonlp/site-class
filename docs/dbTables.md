# dbTables Documentation

---

The **dbTables** makes creating tables simple.

```php
<?php
// example using dbTables

$_site = require_once(getenv("SITELOADNAME"));

$S = new $_site->className($_site);
$T = new dbTables($S);

// Pass some info to getPageTopBottom method
$h->title = "Example"; // Goes in the <title></title>
$h->banner = "<h1>Example</h1>"; // becomes the <header> section
// Add some local css to but a border and padding on the table 
$h->css = <<<EOF
main table * {
  padding: .5em;
  border: 1px solid black;
}
EOF;

[$top, $footer] = $S->getPageTopBottom($h);

// create a table from the memberTable
$sql = "select * from $S->memberTable";
list($tbl) = $T->maketable($sql);

echo <<<EOF
$top
<main>
<h3>Create a table from the members database table</h3>
<p>The members table follows:</p>
$tbl
</main>
<hr>
$footer
EOF;
```

The 'maketable' method takes several optional arguments to help setup the table. Using the options you can give your table an id or class or set any other attributes. You can also pass a 'callback' function which can modify the rows as they are selected (see the 'example-insert-update.php' file in the 'examples' directory for more information).

```bash
  /**
   * maketable()
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

  public function maketable($query, array $extra=null) {...}
```

The '$extra' argument is an associative array with the following items:

* 'callback': this contains a function to call. The function receives two reference arguments:
\&$row and \&$desc.  
`function callbackfunction(\&$row, \&$desc) {}`    
The $row has the row from the table. The '$desc' looks like  
`<tr><td>*</td></tr>`  
For example if your sql query looked like this:  
`$sql = "select test as Test from sometable";`    
Then \$row['Test'] would have the item 'test' from the table. This value can be modified in the
callback function. For example:  
`$row['Test'] = "<span class='odd'>{$row['Test']}</span>";`  
This code would change the referenced value.  
'\$desc' can be changed also:   
`$desc = preg_replace('~<tr>~', "<tr class='oddtr'>", $desc);`

* 'callback2': this is done after 'callback'. The callback2 function takes a single '&$desc' 
field. This argument is the final row description with all of the HTML in place.

* 'footer': has the information to be placed at the bottom of the table.

* 'attr': are the attributes for the table. For example if you wanted to add a 'border', an 
'id' or a 'class'.

Here is an example with all of the items:

```php
$info = $T->maketable($sql, array('callback'=>callback1, 'callback2'=>callback2,  
        'footer'=>$footer, 'attr'=>array('border'=>'1', 'class'=>'something')));
// $info[0] or $info['table'] is the table html.
```

There is a second **dbTables** method which is not used directly as much. This method is called by 'maketable'. It creates only the result rows.

```bash
  /**
   * makeresultrows
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
  
  public function makeresultrows($query, $rowdesc, array $extra=array()) {...}
```

## dbTables Methods

* constructor
* public function makeresultrows($query, $rowdesc, array $extra=array())
* public function maketable($query, array $extra=null)  
$extra is an optional assoc array: $extra['callback'], $extra['callback2'], $extra['footer'] and $extra['attr'].  
$extra['attr'] is an assoc array that can have attributes for the <table> tag, like 'id', 'title', 'class', 'style' etc.  
$extra['callback'] function that can modify the header after it is filled in.  
$extra['footer'] a footer string   
@return array [{string table}, {result}, {num}, {hdr}, table=>{string}, result=>{result}, num=>{num rows}, header=>{hdr}] or === false

---

[Examples](examplereadme.html)  
[dbTables](dbTables.html)  
[SiteClass Methods](siteclass.html)  
[Additional Files](files.html)  
[Analysis and Tracking](analysis.html)  
[Index](index.html)

## Contact Me

Barton Phillips : [bartonphillips@gmail.com](mailto://bartonphillips@gmail.com)  
Copyright &copy; 2024 Barton Phillips  
Project maintained by [bartonlp](https://github.com/bartonlp)
Last Modified January 6, 2024
