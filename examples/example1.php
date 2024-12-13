<?php
// Run this example with 'php -S localhost:3000' from directory that has your cloned repositry. You can change the port if needed.
// Then in your browser run 'localhost:3000/example1.php.
// Your CLI version of PHP should have pdo_mysql, pdo_sqlite and pdo. You can check for these with 'php -ini'.
// If you don't see them use 'sudo apt install <php version>-pdo <php version>-pdo_mysql'.
// <php version> will be something like 'php8.3'.
// The framework uses jQuery which is automatically loaded by SiteClass.
// If you have cloned this repository from GitHub.com/bartonlp/site-class then put it into
// you /var/www/ directory and use autoload.php.

/* Special code for running the examples */
// You must add your home directory, that is the full path to the location where you cloned the
// repository!

if(!($home = $_GET['home'])) {
  $home = ''; // ADD YOUR HOME DIRECTORY HERE! Or add a query string.
}
if(empty($home)) {
  echo "<h1>You Must Fill in <b>\$home</b>!</h1><p>Open the example file and fill in the <b>\$home</b> variable.<br>".
      "You can also add a query: <b>?home=&lt;your home directtory&gt;</b>.<br>".
      "For example, the query string would be <b>?home=/var/www</b>, if you cloned the repository into your <b>/var/www</b> directory.</p>";
  exit();
}

/* Options for running the repository examples */
// The following will load the autoload.php file, which is probably what you cloned.
$_site = require_once "$home/site-class/includes/autoload.php";
// If you have used composer to create a vendor directory in /var/www then use this line. Uncomment
// it and comment out the line above.
//$_site = require_once "$home/vendor/bartonlp/site-class/includes/siteload.php";
/* End of special code */

// The mysitemap.json has this as "mysql" which requires you to have MySql installed.
// If you want to use MySql comment out the next line.
$_site->dbinfo->engine = "sqlite";

// Once the $_site variable has been loaded with an object the rest is pretty standare.
// Instantiate the class with the information from mysitemap.json converted into an object.

$S = new SiteClass($_site);

// Get the info in $S and display it at the end of the page.
$CLASS = print_r($S, true);

// Set up properties on $S.
$S->title = "Example 1"; // The <title>
$S->banner = "<h1>Example One</h1>"; // This is the banner.
$S->defaultCss = "css/style.css";
// Add some css. No need for <link etc>.
$S->css =<<<EOF
pre { font-size: 12px; }
EOF;
// Add java script to the <head>. No need for <script>.
// $S->h_inlineScript = '';
// Add java script after the footer. No need for <script>.
// $S->b_inlineScript = '';

// Create the top of the page and the bottom.

[$top, $footer] = $S->getPageTopBottom();

// $top now has my <head><body><header>. The <header> has images from mysitemap.json and a
// <noscript> if the user has JavaScript disabled.
// The file include/ head.i.php, banner.i.php and footer.php have a standard set of fields.
// However, you can add anything you want in place of the standard.

echo <<<EOF
$top
<hr>
<p>Home directory: <b>$home</b></p>
<p>This is a very simple example.</p>
<hr>
<a href="example1.php?home={$_GET['home']}">Example1</a><br>
<a href="example2.php?home={$_GET['home']}">Example2</a><br>
<a href="phpinfo.php">PHPINFO</a>
<hr>
<pre>This is the value of the instantiated class. \$S: $CLASS</pre>
<hr>
$footer
EOF;
