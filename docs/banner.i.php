<?php
ob_start()
?>
<pre>
&lt;?php
if(!class_exists('dbPdo')) header("location://https://YOUR_DOMAIN/NotAuthorized.php?site=YOUR_DOMAIN&page=banner.i.php");

return &lt;&lt;&lt;EOF
&lt;header&gt;
  &lt;!-- YOUR_DOMAIN/includes/banner.i.php --&gt;
  &lt;a href="$b->logoAnchor">$b->image1&lt;/a&gt;
  $b->image2
  $b->mainTitle
  &lt;noscript&gt;
    &lt;p style='color: red; background-color: #FFE4E1; padding: 10px'>
      $b->image3
      Your browser either does not support &lt;b&gt;JavaScripts&lt;/b&gt; or you have JavaScripts disabled, in either case your browsing
      experience will be significantly impaired. If your browser supports JavaScripts but you have it disabled consider enabaling
      JavaScripts conditionally if your browser supports that. Sorry for the inconvienence.&lt;/p&gt;
    &lt;p&gt;The rest of this page will not be displayed.&lt;/p&gt;
    &lt;style&gt;#content { display: none; }&lt;/style&gt;
  &lt;/noscript&gt;
&lt;/header&gt;
&lt;div id="content"&gt; &lt;!-- See footer.i.php for ending </div>. --&gt;
EOF;
</pre>
<?php
ob_end_flush();

