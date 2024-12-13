<?php
return <<<EOF
<!-- Example Head -->
<head>
$h->title
$h->base
$h->viewport
$h->charset
$h->copyright
$h->author
$h->desc
$h->keywords
$h->meta
$h->canonical
$h->favicon
$h->defaultCss
$h->link
$jQuery
$h->extra
$h->script
$h->inlineScript
$h->css
</head>
EOF;
