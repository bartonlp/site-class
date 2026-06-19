<?php
$_site = require_once getenv("SITELOADNAME");
$S = new SiteClass($_site);

$S->banner = "<h1>SiteClass (version 7+ Psr-4 and PDO), Database and dbPdo Methods</h1>";
$S->title = "SiteClass";
$S->msg2 = "<br>Contact me <a href='mailto:bartonphillips@gmail.com'>bartonphillips@gmail.com</a>";
[$top, $bottom] = $S->getPageTopBottom();

echo <<<EOF
$top
<hr>
<h2 id="siteclass-methods">SiteClass methods:</h2>
<p>While there are a number of methods for each of the major classes there are really only a small
  handful you will use on a regular bases. The ones most used have some documentation with them.</p>
<ul>
<li>public function getPageTopBottom():array</li>
<li>public function getPageTop():string</li>
<li>public function getPageHead():string</li>
<li>public function getPageBanner():string</li>
<li>public function getPageFooter():string</li>
<li>public function getDoctype():string</li>
<li>public function __toString():string</li>
<li>There are a number of 'protected' methods and properties that can be used in a child class.</li>
</ul>
<h2>Database and dbPdo methods:</h2>
<ul>
<li>public function sql($query)<br>
  This is the workhourse of the database.
  It is used for 'select', 'update', 'insert' and basically anything you need to do
  like 'drop', 'alter' etc.</li>
<li>public function fetchrow($result=null, $type="both")<br>
  Probably the second most used method.
  If it follows the sql statment the $result is not needed.
  The only time $result is needed is if there are other queries in a 'while' loop.
  In that case you need to get the result of the query by calling the getResult()
  method before running the 'while' loop.<br>
  The $type can be 'assoc', 'num' or default 'both'. 'assoc' returns only an associative array,
  while 'num' return a numeric array.<br>
  I usually use a numeric array: 
<code>
  while([...] = $S-&gt;fetchrow(&#39;num&#39;) { ... }
</code></li>
<li>public function getLastInsertId()<br>
After an 'insert' this method returns the new row's primary key id.</li>
<li>public function getResult()<br>
Returns the result object from the last sql statement. Usually not needed.</li>
</ul>
<hr>
<p>
<a href="siteclass.php">SiteClass Methods</a><br>  
<a href="files.php">Additional Files</a><br>
<a href="index.php">Main SiteClass</a></p>
<hr>
$bottom
EOF;
