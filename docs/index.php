<?php
$_site = require_once getenv("SITELOADNAME");
$S = new SiteClass($_site);

$S->banner =<<<EOF

<h1 id="siteclass">SiteClass</h1>
<p>SiteClass class mini framework for my websites.</p>
EOF;

$S->title = "SiteClass";
$S->trackerImg1 = null;

$S->css =<<<EOF
header {
  padding: 25px 20px 40px 20px;
  margin: 0;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  width: 100%;
  text-align: center;
  background: url(../docs/images/background.png) #4276b6;
  box-shadow: 1px 0px 2px rgba(0, 0, 0, 0.75);
  z-index: 99;
  -webkit-font-smoothing: antialiased;
  min-height: 76px;
}
header h1 {
  font: 40px/48px;
  color: #f3f3f3;
  text-shadow: 0px 2px 0px #235796;
  margin: 0px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
header p {
  color: #d8d8d8;
  text-shadow: rgba(0, 0, 0, 0.2) 0 1px 0;
  font-size: 18px;
  margin: 0px;
}

#banner {
  z-index: 100;
  left: 0;
  right: 0%;
  height: 50px;
  margin-right: 0px; /*-382px;*/
  position: fixed;
  top: 115px;
  background: #ffcc00;
  border: 1px solid #f0b500;
  box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.25);
  border-radius: 0px 2px 2px 0px;
  padding-right: 10px;
}

#banner .button {
  border: 1px solid #dba500;
  background: linear-gradient(#ffe788, #ffce38);
  border-radius: 2px;
  box-shadow: inset 0px 1px 0px rgba(255, 255, 255, 0.4), 0px 1px 1px rgba(0, 0, 0, 0.1);
  background-color: #FFE788;
  margin-left: 5px;
  padding: 10px 12px;
  margin-top: 6px;
  line-height: 14px;
  font-size: 14px;
  color: #333;
  font-weight: bold;
  display: inline-block;
  text-align: center;
}
#banner .button:hover {
  background: linear-gradient(#ffe788, #ffe788);
  background-color: #ffeca0;
}

#banner .fork {
  position: fixed;
  left: 50%;
  margin-left: -325px;
  padding: 10px 12px;
  margin-top: 6px;
  line-height: 14px;
  font-size: 14px;
  background-color: #FFE788;
}
#banner .downloads {
  float: right;
  margin: 0 45px 0 0;
}
#banner .downloads span {
  float: left;
  line-height: 52px;
  font-size: 90%;
  color: #9d7f0d;
  text-transform: uppercase;
  text-shadow: rgba(255, 255, 255, 0.2) 0 1px 0;
}
#banner ul {
  height: 40px;
  padding: 0;
  float: left;
  margin-top: 4px;
}

#banner ul li { /*.button {*/
  display: inline;
  background-color: #FFE788;
  color: #9d7f0d;
  font-size: 16px;
}

#banner a {
  all: unset;
  cursor: pointer; /* optional */
  padding: 2px 6px;
}

#banner a:hover {
  background-color: #f0b500;
  padding: 2px 6px;
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
  width: 1168px;
  padding: 30px 30px 50px 30px;
  margin: 190px auto 20px;
  position: relative;
  background: #fbfbfb;
  border-radius: 3px;
  border: 1px solid #cbcbcb;
  box-shadow: 0px 1px 2px rgba(0, 0, 0, 0.09), inset 0px 0px 2px 2px rgba(255, 255, 255, 0.5), inset 0 0 5px 5px rgba(255, 255, 255, 0.4);
}

@media print, screen and (max-width: 1168px) {
  body {
    word-wrap: break-word;
  }

  header {
    padding: 20px 20px;
    margin: 0;
  }
  header h1 {
    font-size: 32px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  header p {
    display: none;
  }

  #banner {
    top: 80px;
  }
  #banner .fork {
    float: left;
    display: inline-block;
    margin-left: 0px;
    position: fixed;
    left: 20px;
  }

  section {
    margin-top: 130px;
    margin-bottom: 0px;
    width: auto;
  }

  header ul, header p.view {
    position: static;
  }
}

@media print, screen and (max-width: 480px) {
  header {
    position: relative;
    padding: 5px 0px;
    min-height: 0px;
  }
  header h1 {
    font-size: 24px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  section {
    margin-top: 5px;
  }

  #banner {
    display: none;
  }

  header ul {
    display: none;
  }
}
@media print {
  body {
    padding: 0.4in;
    font-size: 12pt;
    color: #444;
  }
}
@media print, screen and (max-height: 680px) {
  footer {
    text-align: center;
    margin: 20px auto;
    position: relative;
    left: auto;
    bottom: auto;
    width: auto;
  }
}
@media print, screen and (max-height: 480px) {
  footer {
    text-align: center;
    margin: 20px auto;
    position: relative;
    left: auto;
    bottom: auto;
    width: auto;
  }
}
EOF;

[$top, $bottom] = $S->getPageTopBottom();

echo <<<EOF
$top
<div id="banner">
<span id="logo"></span>
<a href="https://github.com/bartonlp/site-class" class="fork"><strong>View On GitHub</strong></a>
<div class="downloads">
  <span>Downloads:</span>
  <ul>
    <li><a href="https://github.com/bartonlp/site-class/zipball/main">ZIP</a></li>
    <li><a href="https://github.com/bartonlp/site-class/tarball/main">TAR</a></li>
  </ul>
</div>

</div>
<section>
<h1 id="siteclass-version-504pdo">SiteClass Version 7+ Psr-4 and PDO</h1>
<p><strong>SiteClass</strong> is a PHP mini framework for my websites.</p>
<h2>Interested In <strong>SiteClass</strong>? Read On.</h2>
<p>This project has several parts that can function standalone or combined.</p>
<ul>
<li>SiteClass.class.php : tools for making creating a site a little easier. The class provides methods to help with headers, banners, footers and more.</li>
<li>Database.class.php : provides a wrapper for different database (MySql, Sqlite3) engines.</li>
<li>dbPdo.class.php : provides a wrapper for the MySqli and Sqlite3 and has been rigorously tested.</li>
<li>ErrorClass.class.php : Error and Exception classes</li>
</ul>
<h2 id="install">Install</h2>
<p>There are several ways to install this project.</p>
<h3 id="download-the-zip-file">Download The ZIP File</h3>
<p>Download the ZIP file from GitHub. Expand it and move the <em>includes</em> directory somewhere.
  On a system with Apache2, I usually put the <em>includes</em> directory in the <em>/var/www</em>
  directory that Apache creates. Apache also usually creates <em>/var/www/html</em> and makes this
  the default DocumentRoot. I put the <em>includes</em> directory just outside of the DocumentRoot.
  In my servers I have <em>/var/www</em> and then have my virtual hosts off that directory.
  That way the <em>includes</em> directory is easily available to all of my virtual hosts.</p>

<h3 id="use-composer">Use Composer</h3>
<p>If you have Apache installed, it has created <em>/var/www</em>. You should create your project
  in that directory.
  Apache also creates <em>/var/www/html</em> by default, and if you do not change that,
  you should create your project within <em>/var/www/html</em>.
  Or if you want to make a separate Apache virtual host with a registered domain name you can
  create your new project in <em>/var/www/YOUR_DOMAIN_NAME</em>.</p>
<p>If you do not already have a <em>composer.json</em> file just cut and past the following:</p>
<pre class="sourceCode"><code>echo &quot;
{
    &quot;require&quot;: {
        &quot;bartonlp/site-class&quot;: &quot;dev-main&quot;
    }
}&quot; &gt; composer.json
</code></pre>
<p>Then run</p>
<pre class="sourceCode"><code>composer install
</code></pre>
<p><strong>OR</strong> you can just run</p>
<pre class="sourceCode"><code>composer require bartonlp/site-class:dev-main
</code></pre>
<p>which will create the <em>composer.json</em> for you and load the package like <em>composer install</em> above.<br />
In your PHP file add</p>
<pre class="sourceCode"><code>
</code></pre>

<p>where <em>$PATH_TO_VENDOR</em> is the path to the <em>vendor</em> directory that composer creates.</p>
<p>There is more documentation in the <em>/docs</em> directory.</p>
<h2 id="further-documentation">Further Documentation</h2>
<ul>
<li><a href="siteclass.html">SiteClass and Database Methods</a></li>
<li><a href="files.html">Additional Files User by SiteClass</a></li>
<li><a href="head.i.html">Head File</a></li>
<li><a href="banner.i.html">Banner File</a></li>
<li><a href="footer.i.html">Footer File</a></li>
</ul>
<h2 id="contact-me">Contact me</h2>
<p>Barton Phillips : <a href="mailto:bartonphillips@gmail.com">bartonphillips@gmail.com</a><br />
Copyright © {$S->copyright}<br />
Last modified June 15, {$S->copyright}</p>
</section>
$bottom
EOF;
