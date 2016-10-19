<?php
// head.i.php for bartonphillips.com

$pageHeadText = <<<EOF
<head>
  <!-- Example head.i.php file -->
  <title>{$arg['title']}</title>
  <!-- METAs -->
  <meta name=viewport content="width=device-width, initial-scale=1">
  <meta charset='utf-8'/>
  <meta name="copyright" content="$this->copyright">
  <meta name="Author"
    content="Barton L. Phillips, mailto:bartonphillips@gmail.com"/>
  <meta name="description"
    content="{$arg['desc']}"/>
  <meta name="keywords"
    content="Barton Phillips, Applitec Inc., Programming, Tips and tricks, blog"/>
{$arg['link']}
{$arg['extra']}
{$arg['script']}
{$arg['css']}
</head>
EOF;
