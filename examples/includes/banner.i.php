<?php
/* BLP 2022-04-09 -
   if nodb or noTrack then the $image* file are all null. 
   $image1, $image2, $image3 and $mainTitle are set by SiteClass.

   There is no $h->siteDomain currenlty.
*/

return <<<EOF
<!-- Example Banner -->
<header>
  <a href="https://www.$this->siteDomain">
$image1</a>
$image2
$mainTitle
<noscript>
<p style='color: red; background-color: #FFE4E1; padding: 10px'>
$image3
Your browser either does not support <b>JavaScripts</b> or you have JavaScripts disabled, in either case your browsing
experience will be significantly impaired. If your browser supports JavaScripts but you have it disabled consider enabaling
JavaScripts conditionally if your browser supports that. Sorry for the inconvienence.</p>
</noscript>
</header>
EOF;
