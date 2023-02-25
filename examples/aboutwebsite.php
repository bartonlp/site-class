<?php
// All sites that have an "About Web Site" link should have a symlink
// to /var/www/bartonphillipsnet/bartonlp/aboutwebsite.php and should have a modified mysitemap.json that has
// these items in the $_site array: 1) 'copyright', 2 'className', 3 'siteName' etc.
// The className is the name of the class for the site. Most sites just use 'SiteClass' but some
// will have a seperate xxxClass in their 'includes' directory in which case 'className' should be
// set to 'includes/xxxClass.php'. 'xxxClass' should be derived from 'SiteClass'.
  
$_site = require_once(__DIR__ . "/../includes/siteload.php");

$S = new $_site->className($_site);

// check for subdomain. This doesn't need to be rigorous as we will Never have a multiple
// subdomain like en.test.domain.com. At most we might have www. or mpc.

$site = $_GET['site'];
$webdomain = $_GET['domain'];

$prefix = $_SERVER['HTTPS'] == "on" ? 'https://' : 'http://';

$webdomain = $prefix . $webdomain;

$S->title = "About This Web Site and Server";
$S->banner = "<h2 class='center'>About This Web Site and Server</h2>";
$S->css = <<<EOF
img { border: 0; }
/* About this web site (aboutwebsite.php)  */
#aboutWebSite {
        margin-left: auto;
        margin-right: auto;
        margin-bottom: 2em;
        display: block;
        width: 100%;
        text-align: center;
}
#runWith {
        background-color: white;
        border: groove blue 10px;
        margin: 2em;
}
img[alt="jQuery logo"] {
        background-color: black;
        width: 215px;
}
img[alt="100% Microsoft Free"] {
        width: 100px;
}
img[alt="Powered By ...?"] {
        width: 90px;
        height: 53px;
}
img[alt="DigitalOcean"] {
        width: 200px;
        height: 60px;
        vertical-align: middle;
}
img[alt="Apache"] {
        width: 400px;
        height: 148px;
}
img[alt="PHP Powered"], img[alt="Powered by MySql"] {
        width: 150px;
        heitht: 50px;
}
img[alt="Best viewed with Mozilla or any other browser"] {
        width: 321px;
}
@media (max-width: 800px) {
        #runWith {
          width: 94%;
          margin: 0px;
        }
}
EOF;

[$top, $footer] = $S->getPageTopBottom();

echo <<<EOF
$top
<div id="aboutWebSite">
<div id="runWith">
  <p>This site's designer is Barton L. Phillips<br/>
     at <a href="https://www.bartonphillips.com">www.bartonphillips.com</a><br>
     Copyright &copy; $S->copyright<br>
     Your IP Address: $S->ip
  </p>
  
	<p>This site is hosted at
    <a href="https://www.digitalocean.com">
		  <img src="images/digitalocean.jpg"
		    alt="DigitalOcean">
		</a>
  </p>
  <p>This site is run with Linux, Apache, MySql, PHP and jQurey<br>
    <img src="images/linux-powered.gif"
      alt="Linux Powered">
  </p>
	<p>
    <a href="https://www.apache.org/">
    <img src="images/apache_logo.gif"
      alt="Apache">
    </a>
  </p>
	<p>
    <a href="https://www.mysql.com">
      <img src="images/powered_by_mysql.gif"
        alt="Powered by MySql">
    </a>
  </p>
	<p>
    <a href="https://www.php.net">
      <img src="images/php-small-white.png"
        alt="PHP Powered">
    </a>
  </p>
  <p>
    <a href="https://jquery.com/">
      <img src="images/logo_jquery_215x53.gif"
        alt="jQuery logo">
    </a>
  </p>
	<p>
    <img src="images/msfree.png"
      alt="100% Microsoft Free">
  </p>
	<p>
    <a href="https://toolbar.netcraft.com/site_report?url=$webdomain#history_table">
	    <img src="images/powered.gif"
        alt="Powered By ...?">
    </a>
	</p>
</div>
</div>
$footer
EOF;
