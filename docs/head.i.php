<?php

ob_start();
?>
&lt;?php
<pre>
/* Items used by SiteClass. These can be set with the SiteClass variable (usually $S)
   or with the value returned from 'require_once' for 'siteload.php' (usually $_site).
   These are both objects so $S-&gt;title = 'new title' or $_site-&gt;title = 'new title' will set the '$h-&gt;title'.
   The following can be set with either $S or $_site:
      title
      desc
      keywords
      copyright
      author
      charset
      viewport
      canonical
      meta
      favicon
      defaultCss
      css
      cssLink
      inlineScript
      script
      link
      extra
      preheadcomment
      lang
      htmlextra
      trackerLocationJs
      interactionLocationJs
      interactionLocationPhp
      trackerLocation
      beaconLocation
      logoImgLocation
      headerImg2Location
      trackerImg1
      trackerImgPhone
      trackerImg2
      trackerImgPhone2
      mysitemap

   These disable certain functions.
   For example, if you set $S-&gt;nojquery the 'jQuery' JavaScript file and the 'tracker.js' file will not be included.
      nojquery, Don't load jQuery and tracker.js.
      noGeo, Don't load the Google geolocation JavaScript logic.
      nointeraction, Don't do interaction logging via logging.php and logging.js
      noCssLastId, Don't 
      nofooter,
      noAddress,
      noCopyright,
      noEmailAddress,
      noCounter,
      noLastmod,
      nonce, this is the nonce for Content-Security-Policy. Currently only bartonphillips.com/index.php uses it.

   These must be done via $_site or they wont work as expected:
      noTrack,
      nodb,
   
   For example, $S-&gt;noTrack will not be available for the Class constructor
   so you must do $_site-&gt;noTrack before instantiating the Class.
*/

define('HEAD_I_VERSION', "head.i.php-1.0.1");

if(!class_exists('dbPdo')) header("location://https://YOUR_DOMAIN/NotAuthorized.php?site=YOUR_DOMAIN&page=/head.i.php");

return &lt;&lt;&lt;EOF
&lt;head&gt;
  &lt;!-- YOUR_SITE/includes/head.i.php --&gt;
  $h-&gt;title
  $h-&gt;base
  $h-&gt;viewport
  $h-&gt;charset
  $h-&gt;copyright
  $h-&gt;author
  $h-&gt;desc
  $h-&gt;keywords
  $h-&gt;meta
  $h-&gt;canonical
  $h-&gt;favicon
  $h-&gt;defaultCss
  $h-&gt;cssLink
  $h-&gt;link
  $h-&gt;jQuery
  $h-&gt;trackerStr
  $h-&gt;extra
  $h-&gt;script
  $h-&gt;inlineScript
  $h-&gt;css
&lt;/head&gt;
EOF;
</pre>
<?php
ob_end_flush();

