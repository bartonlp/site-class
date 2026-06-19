<?php
$_site = require_once getenv("SITELOADNAME");
$S = new SiteClass($_site);
$S->banner = "<h1>Footer</h1>";
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
// bottom area

if(!class_exists('dbPdo')) {
  header("location://https://YOUR_DOMAIN/NotAuthorized.php?site=YOUR_DOMAIN&page=footer.i.php");
}

return &lt;&lt;&lt;EOF
&lt;footer&gt;
  &lt;!-- YOUR_DOMAIN/includes/footer.i.php --&gt;
  \$f-&gt;aboutwebsite
  &lt;div id="address"&gt;
    &lt;address&gt;
      \$f-&gt;copyright
      \$f-&gt;address
      \$f-&gt;emailAddress
    &lt;/address&gt;
  &lt;/div&gt;
  \$f-&gt;msg
  \$f-&gt;msg1
  \$f-&gt;counterWigget
  \$f-&gt;lastmod
  \$f-&gt;msg2
&lt;/footer&gt;
&lt;/div&gt; &lt;!-- Ending for &lt;div id="content". See banner.i.php --&gt;
\$f-&gt;geo
\$f-&gt;extra
\$f-&gt;script
\$f-&gt;inlineScript
&lt;/body&gt;
&lt;/html&gt;
&#69;OF;
</pre>
<hr>
$bottom
EOF;
