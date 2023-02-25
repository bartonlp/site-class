<?php
/*
  This file will run all the database functions. It will not see you.
*/
// This gets the siteload.php from the includes directory.

$_site = require_once(getenv("SITELOADNAME"));

// Get the information from the mysitemap.json in the directory above this one.

$SITE = print_r($_site, true);

// Instantiate the SiteClass from the className in the json file.

$S = new $_site->className($_site);

// Get the info in $S

$CLASS = print_r($S, true);

// Try a bogus ip
$ip = "123.123.123.123";
$isme = $S->isMyIp($ip) ? 'true' : 'false'; // This should be false
$me = $S->isMe() ? 'true' : 'false'; // This should be true if you have inserted your ip address into the myip table.

// These are the value in the myip table (plus my server address).
$myip = print_r($S->myIp, true);

$S->title = "Example2"; // The <title>
$S->banner = "<h1>Example2</h1>"; // This is the banner.
// Add some css.
$S->css =<<<EOF
pre { font-size: 8px; }
EOF;

$bot1 = $S->isBot('I am a bot') ? "true" : "false"; // This should be true
$bot2 = $S->isBot($S->agent) ? "true" : "false"; // This should be false unless your are one.

[$top, $footer] = $S->getPageTopBottom();

echo <<<EOF
$top
<h4>This example does not set \$_site->isMeFalse to true. Therefore not everything is counted or tracked.</h4>
<p>\$S->isBot('I am a bot'): $bot1<br>
\$S->isBot('$S->agent'): $bot2<br>
\$S->isMyIp('$ip')=$isme<br>\$S->isMe()=$me</p>
<pre>myIp: $myip</pre>
<pre>\$_site: $SITE</pre>
<pre>\$S: $CLASS</pre>
<hr>
<a href="example1.php">Example1</a><br>
<a href="example2.php">Example2</a><br>
<a href="example3.php">Example3</a><br>
<a href="example4.php">Example4</a><br>
<a href="hi.php">Hi</a><br>
<a href="phpinfo.php">PHPINFO</a>
$footer
EOF;
