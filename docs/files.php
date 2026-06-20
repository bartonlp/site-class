<?php
$_site = require_once getenv("SITELOADNAME");
$S = new SiteClass($_site);

$S->banner = "<h1>Additional Files by SiteClass</h1>";
$S->msg2 = "<br>Contact me <a href='mailto:bartonphillips@gmail.com'>bartonphillips@gmail.com</a>";
$S->css =<<<EOF
@media (max-width: 700px) {
  pre {
    font-size: 12px; 
    white-space: pre-wrap;
    overflow-wrap: break-word; /* wrap only when needed */
  }
}
EOF;

[$top, $bottom] = $S->getPageTopBottom();

echo <<<EOF
$top
<hr>
<h2>The 'mysitemap.json' File</h2>
<pre>
/*
 * SPECIAL NOTE: Normal json will not allow comments.
 * The siteload.php program removes the comments and then
 * passes the result to json_decode(), so after the comments are
 * revomve the file MUST be legal parsable json.
 *
 * This is mysitemap.json for a standare system.
 * You can have 'doSiteClass' or 'webServer'.
 * "doSiteClass": true,
 * //"webServer": true,
 * ...
 * "dbinfo": {
 *   "host": "localhost",
 *   "user": "barton", 
 *   "database": "bartonphillips",
 *   "engine": "mysql",
 * },
 * ...
 * "headFile": "/var/www/bartonphillips.com/includes/head.i.php",
 * "bannerFile": "/var/www/bartonphillips.com/includes/banner.i.php",
 * "footerFile": "/var/www/bartonphillips.com/includes/footer.i.php",
 */
{
  "doSiteClass": true, // This should be true so we are using full SiteClass!
  //"webServer": true,
  "siteDomain": "bartonphillips.com",
  "siteName": "bartonphillips.com", 
  "mainTitle": "&lt;h1&gt;Barton Phillips Home Page&lt;/h1&gt;",
  "title": "this title",
  "path": "/var/www/bartonphillips.com",
  "className": "SiteClass", 
  "copyright": "Barton L. Phillips",
  "author": "Barton L. Phillips, mailto:bartonphillips@gmail.com", 
  "address": "New Bern, North Carolina", 
  "favicon": "https://bartonphillips.net/images/favicon.ico",
  "canonical": "https://www.bartonphillips.com",
  "masterdb": "barton",
  "dbinfo": {
    "host": "localhost",
    "user": "barton", 
    "database": "barton", //"bartonphillips",
    "engine": "mysql",
    //"engine": "sqlite", // Don't use it except experment.
    "DUMMY": null // DUMMY at end with no comma    
  },
  "errorMode": {
    "development": true,
    "noEmail": true,
    "DUMMY": null // DUMMY at end with no comma
  },
  //"memberTable": "members",
  "headFile": "/var/www/bartonphillips.com/includes/head.i.php",
  "bannerFile": "/var/www/bartonphillips.com/includes/banner.i.php",
  "footerFile": "/var/www/bartonphillips.com/includes/footer.i.php",
  "trackerImg1": "/images/blp-image.png",
  //"trackerImgPhone": "/images/8080cpu.jpg",
  "trackerImg2": "/images/146624.png",
  "EMAILADDRESS": "bartonphillips@gmail.com",
  "EMAILRETURN": "bartonphillips@gmail.com",
  "EMAILFROM": "webmaster@bartonphillips.com"
}
</pre>
<p>You can add or remove items. See SiteClass for getPageTopBottom(), getPageHead(), getPageBanner()
  and getPageFooter(). The 'mysitemap.json is put in your directory along with the 'includes' along
  with the
  <a href="head.i.php">Head File</a> (head.i.php),
  <a href="banner.i.php">Banner File</a> (banner.i.php),
  <a href="footer.i.php">Footer File</a> (footer.i.php)</p>
<hr>
<p>
  <a href="siteclass.php">SiteClass Methods</a><br>  
  <a href="files.php">Additional Files</a><br>
  <a href="index.php">Main SiteClass</a></p>
<hr>
$bottom
EOF;

