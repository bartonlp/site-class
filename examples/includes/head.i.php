<?php
/* BLP 2022-04-09 - These arre all set via $h or $this
   $h->base = $h->base ?? $this->base; // BLP 2022-01-28 -- new
   $h->title = $h->title ?? $this->title ?? ltrim($this->self, '/'); // BLP 2022-01-04 -- change from siteName to self
   $h->desc = $h->desc ?? $this->desc ?? $h->title; // BLP 2022-02-07 -- $h or $this->desc or $h->title from above
   $h->keywords = $h->keywords ?? $this->keywords ?? $h->desc; // BLP 2022-02-07 -- $h->desc will always be something
   $h->favicon = $h->favicon ?? $this->favicon ?? 'https://bartonphillips.net/images/favicon.ico';
   $h->defaultCss = $h->defaultCss ?? $this->defaultCss ?? 'https://bartonphillips.net/css/blp.css';
   $h->preheadcomment = $h->preheadcomment ?? $this->preheadcomment;
   $h->lang = $h->lang ?? $this->lang ?? 'en';
   $h->htmlextra = $h->htmlextra ?? $this->htmlextra;
   $h->headFile = $h->headFile ?? $this->headFile;
   $h->bannerFile = $h->bannerFile ?? $this->bannerFile;
   $h->footerFile = $h->footerFile ?? $this->footerFile;
   $h->copyright = $h->copyright ?? $this->copyright; // BLP 2022-04-09 - new
   $h->author = $h->author ?? $this->author; // BLP 2022-04-09 - new
   $h->nojquery = $h->nojquery ?? $this->nojquery; // BLP 2022-04-09 - new
   $h->noTrack = $h->noTrack ?? $this->noTrack;
   $h->nodb = $h->nodb ?? $this->nodb;
   $h->bodytag ?? $this->bodytag ?? "<body>";
   $h->banner ?? $this->mainTitle;

   Currently $h->meta, $h->link, $h->extra, $h->script, $h->inlineScrip and $h->css have no $this values.
*/

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
$h->favicon
$h->defaultCss
$h->link
$jQuery
$trackerStr
$h->extra
$h->script
$h->inlineScript
$h->css
</head>
EOF;
