<?php
$_site = require_once getenv("SITELOADNAME");
$S = new SiteClass($_site);
$S->banner =<<<EOF
<h1 id="siteclass">SiteClass</h1>
<p>SiteClass class mini framework for my websites.</p>
EOF;
$S->msg2 = "<br>Contact me <a href='mailto:bartonphillips@gmail.com'>bartonphillips@gmail.com</a>";

$S->css =<<<EOF
body { padding: 0; }
header {
  width: 100%;
  text-align: center;
  background: url(../docs/images/background.png) #4276b6;
  z-index: 99;
}
header h1 {
  color: white;
  margin: -10px;
}
header p {
  color: white;
  font-size: 18px;
  margin: 0px;
  padding-bottom: 10px;
}

#banner {
  z-index: 100;
  position: relative;
  height: 50px;
  background: #ffcc00;
  padding-left: 10px;
  padding-right: 10px;
  padding-top: 8px;
}
#banner .ongithub { font-size: 20px; }
#banner .downloads {
  float: right;
  margin: -8px 45px 0 0;
}
#banner .downloads span {
  line-height: 52px;
  font-size: 90%;
  color: #9d7f0d;
  text-transform: uppercase;
}
#banner a {
  all: unset;
  cursor: pointer; /* optional */
  background-color: #FFE788;
  color: #9d7f0d;
  padding: 2px 6px;
  font-size: 16px;
}
#banner a:hover {
  background-color: #f0b500;
}
#banner #logo {
  position: absolute;
  height: 36px;
  width: 36px;
  right: 7px;
  top: 7px;
  display: block;
  background: url(../docs/images/octocat-logo.png);
}

section {
  width: 1200px;
  padding: 30px; /* 30px 50px 30px; */
  margin: 10px auto 20px;
  position: relative;
  background: #fbfbfb;
  border-radius: 5px;
  border: 2px solid #cbcbcb;
}
@media (max-width: 1200px) {
  section {
    width: 700px;
  }
}
@media (max-width: 700px) {
  section {
    width: 412px;
  }
  #banner { padding-top: 10px; }
  #banner .downloads { margin-top: -12px; }
  #banner .sourceCode {
    font-size: 10px;
  }
  #banner .ongithub { font-size: 10px; }
}
EOF;

[$top, $bottom] = $S->getPageTopBottom();

echo <<<EOF
$top
<div id="banner">
  <a class="ongithub" href="https://github.com/bartonlp/site-class"><b>View On GitHub</b></a>
<div class="downloads">
  <span>Downloads:</span>
  <span id="logo"></span>
    <a href="https://github.com/bartonlp/site-class/zipball/main">ZIP</a>&nbsp;
    <a href="https://github.com/bartonlp/site-class/tarball/main">TAR</a>
</div>

</div>
<section>
<h1 id="siteclass-version-504pdo">SiteClass Version 7+ Psr-4 and PDO</h1>
<p><b>SiteClass</b> is a PHP mini framework for my websites.</p>
<h2>Interested In <b>SiteClass</b>? Read On.</h2>
<p>This project has several parts that can function standalone or combined.</p>
<ul>
<li>SiteClass.class.php : tools for making creating a site a little easier.
The class provides methods to help with headers, banners, footers and more.</li>
<li>Database.class.php : provides a wrapper for different database (MySql, Sqlite3) engines.</li>
<li>dbPdo.class.php : provides a wrapper for the MySqli and Sqlite3 and has been rigorously tested.</li>
<li>ErrorClass.class.php : Error and Exception classes</li>
</ul>
<h2 id="install">Install</h2>
<p>There are several ways to install this project.</p>
<h3 id="download-the-zip-file">Download The ZIP File</h3>
<p>Download the ZIP file from GitHub. Expand it and move the <em>src</em> directory somewhere.
  On a system with Apache2, I usually put the <em>src</em> directory in the <em>/var/www</em>
  directory that Apache creates. Apache also usually creates <em>/var/www/html</em> and makes this
  the default <b>DocumentRoot</b>. I put the <em>src</em> directory just outside of the <b>DocumentRoot</b>.
  In my servers I have <em>/var/www</em> and then have my virtual hosts off that directory.
  That way the <em>src</em> directory is easily available to all of my virtual hosts.</p>

<h3 id="use-composer">Use Composer</h3>
<p>If you have Apache installed, it has created <em>/var/www</em>. You should create your project
  in that directory.
  Apache also creates <em>/var/www/html</em> by default, and if you do not change that,
  you should create your project within <em>/var/www/html</em>.
  Or if you want to make a separate Apache virtual host with a registered domain name you can
  create your new project in <em>/var/www/YOUR_DOMAIN_NAME</em>.</p>
<p>If you do not already have a <em>composer.json</em> file just cut and past the following:</p>
<ul>
<pre class="sourceCode"><code>echo &quot;
{
    &quot;require&quot;: {
        &quot;bartonlp/site-class&quot;: &quot;dev-main&quot;
    }
}&quot; &gt; composer.json
</code></pre>
</ul>
<p>Then run</p>
<ul>
<pre class="sourceCode"><code>composer install
</code></pre>
</ul>
<p><strong>OR</strong> you can just run</p>
<ul>
<pre class="sourceCode"><code>composer require bartonlp/site-class:dev-main
</code></pre>
</ul>
<p>which will create the <em>composer.json</em> for you and load the package like <em>composer install</em> above.</p>
<h2 id="further-documentation">Further Documentation</h2>
<ul>
<li><a href="siteclass.php">SiteClass and Database Methods</a></li>
<li><a href="files.php">Additional Files User by SiteClass</a></li>
<li><a href="head.i.php">Head File</a></li>
<li><a href="banner.i.php">Banner File</a></li>
<li><a href="footer.i.php">Footer File</a></li>
</ul>
</section>
$bottom
EOF;
