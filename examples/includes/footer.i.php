<?php
/* BLP 2022-04-09 -
   If nofooter then we don't do load this file or use the Default Footer instead just <footer></footer></body></html>.

   $b->ctrmsg = $b->ctrmsg ?? $this->ctrmsg;
   $b->msg = $b->msg ?? $this->msg;
   $b->msg1 = $b->msg1 ?? $this->msg1;
   $b->msg2 = $b->msg2 ?? $this->msg2;
   $b->address = ($b->noAddress ?? $this->noAddress) ? null : $b->address ?? $this->address; // BLP 2022-04-09 - new
   $b->copyright = $b->copyright ?? $this->copyright; // BLP 2022-04-09 - new
   if(preg_match("~^\d{4}~", $b->copyright) === 1) {
     $b->copyright = "Copyright &copy; $b->copyright";
   }
   $b->aboutwebsite = $b->aboutwebsite ?? (file_exists('aboutwebsite.php') ? "<h2><a target='_blank' href='aboutwebsite.php'>About This Site</a></h2>" : null); // BLP 2022-04-09 - new
   $b->emailAddress = ($b->noEmailAddress ?? $this->noEmailAddress) ? (($b->emailAddress ?? $this->EMAILADDRESS) ? "<a href='mailto:$this->EMAILADDRESS'>$this->EMAILADDRESS</a>" : null); // BLP 2022-04-09 - new
   if(($b->noCounter ?? $this->noCounter) !== true) {
     $counterWigget = $this->getCounterWigget($b->ctrmsg); // ctrmsg may be null which is OK
   }
   if(($b->noLastmod ?? $this->noLastmod) !== true) {
     $lastmod = "Last Modified: " . date("M j, Y H:i", getlastmod());
   }
   if(($b->noGeo ?? $this->noGeo) !== true) {
    $geo = "<script src='https://bartonphillips.net/js/geo.js'></script>";
   }

   Currently $b->script has no $this value associated.
*/
   
return <<<EOF
<!-- Example Footer -->
<footer>
{$b->aboutwebsite}
<div id="address">
<address>
{$b->copyright}
{$b->address}
{$b->emailAddress}
</address>
</div>
{$b->msg}
{$b->msg1}
{$counterWigget}
{$lastmod}
{$b->msg2}
</footer>
$geo
{$b->script}
{$b->inlineScript}
</body>
</html>
EOF;
