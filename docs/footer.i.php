<?php
ob_start();
?>
<pre>
// bottom area

if(!class_exists('dbPdo')) header("location://https://YOUR_DOMAIN/NotAuthorized.php?site=YOUR_DOMAIN&page=footer.i.php");

return &lt;&lt;&lt;EOF
&lt;footer&gt;
  &lt;!-- YOUR_DOMAIN/includes/footer.i.php --&gt;
  $f-&gt;aboutwebsite
  &lt;div id="address"&gt;
    &lt;address&gt;
      $f-&gt;copyright
      $f-&gt;address
      $f-&gt;emailAddress
    &lt;/address&gt;
  &lt;/div&gt;
  $f-&gt;msg
  $f-&gt;msg1
  $f-&gt;counterWigget
  $f-&gt;lastmod
  $f-&gt;msg2
&lt;/footer&gt;
&lt;/div&gt; &lt;!-- Ending for &lt;div id="content". See banner.i.php --&gt;
$f-&gt;geo
$f-&gt;extra
$f-&gt;script
$f-&gt;inlineScript
&lt;/body&gt;
&lt;/html&gt;
EOF;
</pre>
<?php
ob_end_flush();
