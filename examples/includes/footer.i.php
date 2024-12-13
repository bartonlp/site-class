<?php
// Example footer   
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
{$lastmod}
{$b->msg2}
</footer>
{$b->script}
{$b->inlineScript}
</body>
</html>
EOF;
