<?php
// composer-route.php

// You should have installed AltoRouter in this (examples) directory
require_once('vendor/autoload.php');

require_once("../../../autoload.php");
require_once('.sitemap.php');

// For error messages

Error::setNoEmailErrs(true);
Error::setDevelopment(true);

$S = new SiteClass($siteinfo);

// Instantiate twig with the filesystem loader. The templates are in this ('examples')
// directory.

$twig = new Twig_Environment(new Twig_Loader_Filesystem('.'));

// Instantiate AltoRouter

$router = new AltoRouter();

// Create the routes for '/', 'home', 'edit', 'update', 'post' and 'reset'
// First the method, the route, the function

$router->map('GET','/', 'home'); // 4th arg 'name' not used.
$router->map('GET','/home', 'home'); // another name for /
$router->map('GET','/edit/[i:id]', 'edit');
$router->map('POST','/update/[i:id]', 'update_post');
$router->map('POST','/post', 'update_post');
$router->map('POST','/reset', 'resetdb');

// Which route did we get?

$match = $router->match();

// $match array has 'target', 'params', 'name'. We don't use 'name'.
// 'target' is the function name
// 'params' is a numeric array of arguments, in our case $match['params']['id'] is what we
// want. The function will get the id as $params['id'].

if($match && is_callable( $match['target'])) {
	call_user_func_array($match['target'], $match['params']); 
} else {
	// no route was matched
  echo "<h1>404 Not Found</h1><p>FILE <b>{$_SERVER['REQUEST_URI']}</b> NOT FOUND</p>";
	header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
}

// HOME Page

function home() {
  global $S, $twig;

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
    $row['ID'] = "<a href='/edit/".$row['ID']."'>".$row['ID']."</a>";
  }

  // We use the 'as' to give our column headers nice names otherwise they would be
  // 'fname' and 'lname'.

  $sql = "select rowid as ID, fname as 'First Name', lname as 'Last Name' from members";

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

  // Use twig to render the first template

  echo $twig->render('route-1.template',
                     array('top'=>$top, 'footer'=>$footer, 'tbl'=>$tbl)
                    );
}

// Edit Page

function edit($params) {
  global $S, $twig;

  $id = $params['id'];
  
  $S->query("select fname, lname from members where rowid=$id");
  list($fname, $lname) = $S->fetchrow('num');

  $h->title = "update";
  $h->banner = "<h1>Update Record</h1>";
  list($top, $footer) = $S->getPageTopBottom($h);

  // Use twit to render the second template
  
  echo $twig->render('route-2.template',
                     array('top'=>$top, 'footer'=>$footer,
                           'fname'=>$fname, 'lname'=>$lname, 'id'=>$id
                          )
                    );
}

// UPDATE or POST. Does the action and then calls home().

function update_post($params) {
  global $S;

  $id = $params['id'];
  $fname = $_POST['fname'];
  $lname = $_POST['lname'];

  if(!isset($id)) {
    $sql = "insert into members (rowid, fname, lname) values(null,'$fname', '$lname')";
  } else {
    $sql = "update members set fname='$fname', lname='$lname' where rowid=$id";
  }
  $S->query($sql);

  home();
}

// RESET database to initial values. Does the action and then calls home().

function resetdb() {
  global $S;

  // Here we have a complex single liner with three seperate sql statements.
  // we drop the table. Create the table again fresh, and then insert the inital rows.
  
  $sql = "drop table members;".
         "create table members (fname text, lname text);".
         "insert into members (fname, lname) values('Big', 'Joe'),('Little', 'Joe'),".
         "('Barton','Phillips'),('Someone','Else');";

  $S->query($sql);
  
  home();
}

