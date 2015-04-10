<?php
// insert-update.php

// Check if we are in development mode. If there is an 'includes' directory just above
// 'examples' then development.
  
if(file_exists("../includes")) {
  require_once("../includes/siteautoload.class.php");
} else {
  require_once("../vendor/bartonlp/site-class/includes/siteautoload.class.php");
}

Error::setNoEmailErrs(true);
Error::setDevelopment(true);

$S = new SiteClass($siteinfo);

// FORM POST 'insert' into database

if($_POST['page'] == "post") {
  $id = $_POST['id'];
  $fname = $_POST['fname'];
  $lname = $_POST['lname'];

  if(!isset($id)) {
    $sql = "insert into members (rowid, fname, lname) values(null,'$fname', '$lname')";
  } else {
    $sql = "update members set fname='$fname', lname='$lname' where rowid=$id";
  }
  $S->query($sql);
  goto START;
}

// GET 'update'

if($_GET['page'] == "update") {
  $id = $_GET['id'];
  $S->query("select fname, lname from members where rowid=$id");
  list($fname, $lname) = $S->fetchrow('num');

  $h->title = "update";
  $h->banner = "<h1>Update Record</h1>";
  list($top, $footer) = $S->getPageTopBottom($h);

  echo <<<EOF
$top
<form method="post">
First Name: <input type="text" name="fname" value="$fname"><br>
Last Name : <input type="text" name="lname" value="$lname"><br>
<input type="submit" value="Submit">
<input type="hidden" name="page" value="post">
<input type="hidden" name="id" value="$id">
</form>
$footer
EOF;
  exit();
}

START:

$T = new dbTables($S);

// Pass some info to getPageTopBottom method
$h->title = "Insert Update"; // Goes in the <title></title>
$h->banner = "<h1>Insert Update Test</h1>"; // becomes the <header> section
// Add some local css to but a border and padding on the table 
$h->css = <<<EOF
  <style>
#tbl * {
  padding: .5em;
  border: 1px solid black;
}
.odd {
  color: white;
  background-color: red;
}
.oddtr {
  background-color: green;
}
  </style>
EOF;

list($top, $footer) = $S->getPageTopBottom($h);

// create a table from the memberTable

$inc = 0;

// The maketable() callback function gets the current row and the row description.
// This table has two fields in each row, 'First Name' and 'Last Name'.
// The description looks like "<tr><td>First Name</td><td>Last Name</td></tr>"
// for the first row. The $desc still has the field keys not the final values.
// This callback uses a counter to determin which rows are odd. We take the value of the
// first field in the row ($row['First Name'] and add a span around it.
// We then change the <tr> into <tr class='oddtr'> in the description.
// Note the arguments are passed by reference rather than value so we can modify the results.

function callback(&$row, &$desc) {
  global $inc;

  if(!($inc++ % 2)) {
    $row['First Name'] = "<span class='odd'>".$row['First Name']."</span>";
    $desc = preg_replace('~<tr>~', "<tr class='oddtr'>", $desc);
  }
  $row['ID'] = "<a href='insert-update.php?page=update&id=".$row['ID']."'>".$row['ID']."</a>";
}

// We use the 'as' to give our column headers nice names otherwise they would be
// 'fname' and 'lname'.

$sql = "select rowid as ID, fname as 'First Name', lname as 'Last Name' ".
       "from {$siteinfo['memberTable']}";

// The second argument to the maketable method is an array with the following properties:
// 'callback', 'callback2', 'footer'] 'attr'.
// 'attr' is an assoc array that can has attributes for the <table> tag,
//   like 'id', 'title', 'class', 'style' etc.
// 'callback2' has the final row with the keys replaced by the column values. 'callback2'
// just has the &$desc argument.
// 'footer' is a footer string 

$f = "<tfoot><tr><th colspan='2'>Footer goes here</th></tr><tfoot>";
list($tbl) = $T->maketable($sql, array(
                                       'footer'=>$f,
                                       'callback'=>callback,
                                       'attr'=>array('id'=>'tbl')
                                      )
                          );

echo <<<EOF
$top
$tbl
<h2>Enter a first and last name to insert</h2>
<form method="post">
<input type="text" name="fname" placeholder="first name" autofocus>
<input type="text" name='lname' placeholder="last name"><br>
<input type="submit" value="Submit">
<input type="hidden" name="page" value="post">
</form>
$footer
EOF;
