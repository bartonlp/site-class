<?php
$_site = require_once getenv("SITELOADNAME");
$S = new SiteClass($_site);
$S->banner = "<h1>Header</h1>";
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
<pre>
&lt;?php

define('HEAD_I_VERSION', "head.i.php-1.0.1");

if(!class_exists('dbPdo')) {
  header("location://https://YOUR_DOMAIN/NotAuthorized.php?site=YOUR_DOMAIN&page=/head.i.php");
}

return &lt;&lt;&lt;EOF
&lt;head&gt;
  &lt;!-- YOUR_SITE/includes/head.i.php --&gt;
  \$h-&gt;title
  \$h-&gt;base
  \$h-&gt;viewport
  \$h-&gt;charset
  \$h-&gt;copyright
  \$h-&gt;author
  \$h-&gt;desc
  \$h-&gt;keywords
  \$h-&gt;meta
  \$h-&gt;canonical
  \$h-&gt;favicon
  \$h-&gt;defaultCss
  \$h-&gt;cssLink
  \$h-&gt;link
  \$h-&gt;jQuery
  \$h-&gt;trackerStr
  \$h-&gt;extra
  \$h-&gt;script
  \$h-&gt;inlineScript
  \$h-&gt;css
&lt;/head&gt;
&#69;OF;
</pre>
<hr>
$bottom
EOF;

