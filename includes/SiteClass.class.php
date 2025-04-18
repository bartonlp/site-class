<?php
// SITE_CLASS_VERSION must change when the GitHub Release version changes.
// Note that the constructor calls the Database constructor which in turn call the
// dbPdoconstructor which does all of the heavy lifting.

// This is using PDO.

define("SITE_CLASS_VERSION", "5.2.0pdo"); // BLP 2025-04-08 - changed getPageBanner() to use $b->names. Changed getPageFooter() to use $f->names.
                                          // Added theagent='$this->agent' to $trackerStr. This is
                                          // for the bots3 table which uses ip, agent, page as its
                                          // key.
                                          // Added more comments and removed old BLP labels.
                                          // The $this->b_... and $this->h_..., which are usually set by
                                          // $S->b_... and $S->h_..., remain the same in all of my
                                          // files.

// One class for all my sites
/**
 * SiteClass
 *
 * @package SiteClass
 * @author Barton Phillips <barton@bartonphillips.com>
 * @link http://www.bartonphillips.com
 * @copyright Copyright (c) 2022, Barton Phillips
 * @license  MIT
 */

/**
 * @package SiteClass
 * This class can be extended to handle special issues and add methods.
 */

/*
BLP 2024-12-17 - this has been removed from siteload.php
use \bartonlp\siteload\getinfo as load;
*/

class SiteClass extends Database {
  // Give these default values incase they are not mentioned in mysitemap.json.
  // Note they could still be null from mysitemap.json!

  public $count = true;
  public $doctype = "<!DOCTYPE html>";

  /**
   * Constructor
   *
   * @param object $s. If this is not an object we get an error!
   *  The $s is almost always from mysitemap.json.
   *  Once in a while they can be changed by the program instantiating the class.
   *  'count' is default true.
   *  $s has the values from $_site = require_once getenv("SITELOADNAME");
   *  which uses siteload.php to gets values from mysitemap.json.
   */
  
  public function __construct(object $s) {
    // Do the parent Database constructor which does the dbPdo constructor.
    
    parent::__construct($s); // Turns everything in $s into $this.

    // Add the date to the copyright notice if one exists

    if($this->copyright) {
      $this->copyright = date("Y") . " $this->copyright";
    }
  } // End of constructor.

  /**
   * getVersion()
   * @return string version number
   */

  public static function getVersion():string {
    return SITE_CLASS_VERSION;
  }

  /**
   * getHitCount()
   */

  public function getHitCount():int {
    return $this->hitCount;
  }

  /**
   * getDoctype()
   * Returns the CURRENT DocType used by this program
   */

  public function getDoctype():string {
    return $this->doctype;
  }

  /**
   * getPageTopBottom()
   * This is the MOST used method. All of the other getPage... methods usually get called from here.
   * Get Page Top (<head> and <header> ie banner) and Footer
   * @return array top, footer
   */

  public function getPageTopBottom():array {
    // Do getPageTop and getPageFooter

    $top = $this->getPageTop();

    $footer = $this->footer ?? $this->getPageFooter(); // We could have a different pageFooter.

    // return the array which we usually get via '[$top, $footer] = $S->getPageTopBottom()'

    return [$top, $footer];
  }

  /**
   * getPageTop()
   * Get Page Top
   * Gets both the page <head> and <header> sections
   * @return string with the <head>  and <header> (ie banner) sections
   */
  
  public function getPageTop():string {
    // Get the page <head> section

    $head = $this->getPageHead();

    // Get the page's banner section (<header>...</header>)
    
    $banner = $this->getPageBanner();

    return "$head\n$banner";
  }

  /**
   * getPageHead()
   * Get the page <head></head> stuff including the doctype and the beginning <body> tag.
   * @return string $pageHead
   * Uses $h. However the old values are still available for old includes.
   */

  // BLP 2025-02-15 - Add nonce.
  
  public function getPageHead():string {
    // Instantiate a stdClass so we can pass things to the headFile.
    
    $h = new stdClass;

    $h->base = $this->base ? "<base href='$this->base'>" : null;

    // All meta tags

    $h->title = $this->title ? "<title>$this->title</title>" : null;
    $h->desc = $this->desc ? "<meta name='description' content='$this->desc'>" : null;
    $h->keywords = $this->keywords ? "<meta name='keywords' content='$this->keywords'>" : null;
    $h->copyright = $this->copyright ? "<meta name='copyright' content='$this->copyright'>" : null;
    $h->author = $this->author ? "<meta name='author' content='$this->author'>" : null;
    $h->charset = $this->charset ? "<meta charset='$this->charset'>" : "<meta charset='utf-8'>";
    $h->viewport = $this->viewport ? "<meta name='viewport' content='$this->viewport'>" :
                   "<meta name='viewport' content='width=device-width, initial-scale=1'>";
    $h->canonical = $this->canonical ? "<link rel='canonical' href='$this->canonical'>" : null;
    $h->meta = $this->meta; // This needs a fully filled out <meta ...>. The whole thing.
    
    // link tags
    
    $h->favicon = $this->favicon ? "<link rel='shortcut icon' href='$this->favicon'>" :
                  "<link rel='shortcut icon' href='https://bartonphillips.net/images/favicon.ico'>";

    // The default css should be a URL (relative or absolute) or if true or false it is set to null
    
    if($this->defaultCss === false || $this->defaultCss === true) { 
      $h->defaultCss = null;
    } else { // Else either add the value or the default.
      $h->defaultCss = $this->defaultCss ? "<link rel='stylesheet' href='$this->defaultCss' title='default'>" :
                       "<link rel='stylesheet' href='https://bartonphillips.net/css/blp.css' title='default'>";
    }

    // These need to have the <style> or <script> tabs added.
    
    $h->css = $this->css ? "<style>\n$this->css\n</style>" : null;
    $h->inlineScript = $this->h_inlineScript ? "<script nonce='$this->nonce'>\n$this->h_inlineScript\n</script>" : null;
    
    // The rest, $h->link, $h->script and $h->extra need to have the full '<link' or '<script' tags
    // in the variables.

    $h->script = $this->h_script;
    $h->link = $this->link;
    $h->extra = $this->extra;
    
    $preheadcomment = $this->preheadcomment; // Must be a real html comment ie <!-- ... -->

    $lang = $this->lang ?? 'en';

    $htmlextra = $this->htmlextra; // Must be full html
    
    // If nojquery is true then don't add $trackerStr at all.
    // So here !== true means that it is really false or null.
    
    if($this->nojquery !== true) {
      // Add the jQuery info and the JavaScript var items.
      
      $jQuery = <<<EOF
  <!-- BLP 2024-12-31 - Latest version 3.7.1 and migrate 3.5.2 -->
  <script nonce="$this->nonce" src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script nonce="$this->nonce" src="https://code.jquery.com/jquery-migrate-3.5.2.min.js" integrity="sha256-ocUeptHNod0gW2X1Z+ol3ONVAGWzIJXUmIs+4nUeDLI=" crossorigin="anonymous"></script>
  <script nonce="$this->nonce">jQuery.migrateMute = false; jQuery.migrateTrace = false;</script>
EOF;

      $this->trackerLocationJs = $this->trackerLocationJs ?? "https://bartonlp.com/otherpages/js/tracker.js";

      // add the location of the logging.js and logging.php files.
      
      $this->interactionLocationJs = $this->interactionLocationJs ?? "https://bartonlp.com/otherpages/js/logging.js"; // BLP 2025-03-26 - 
      $this->interactionLocationPhp = $this->interactionLocationPhp ?? "https://bartonlp.com/otherpages/logging.php";  // BLP 2025-03-26 -
      
      // tracker.php and beacon.php MUST be symlinked in bartonlp.com/otherpages
      // to the SiteClass 'includes' directory. This is because /var/www/site-class does not have
      // its own and therefore we can't get to it directly. https://bartonlp has its own
      // domain and we can get to it.

      $trackerLocation = $this->trackerLocation ?? "https://bartonlp.com/otherpages/tracker.php"; // BLP 2023-08-09 - a symlink
      $beaconLocation = $this->beaconLocation ?? "https://bartonlp.com/otherpages/beacon.php"; // BLP 2023-08-09 - a symlink

      $logoImgLocation = $this->logoImgLocation ?? "https://bartonphillips.net";
      $headerImg2Location = $this->headerImg2Location ?? $logoImgLocation;

      // The trackerImg... can start with http or https. If so use the full url.
      // NOTE: these Must be either an absolute URL or a relative URL not a filesystem link!

      if(strpos($this->trackerImg1, "http") === 0) {
        $desktopImg = $this->trackerImg1;
      } else {
        $desktopImg = $this->trackerImg1 ? "$logoImgLocation$this->trackerImg1" : null; // BLP 2023-08-08 -
      }
      if(strpos($this->trackerImgPhone, "http") === 0) {
        $phoneImg = $this->trackerImgPhone;
      } else {
        $phoneImg = $this->trackerImgPhone ? "$logoImgLocation$this->trackerImgPhone" : null; // BLP 2023-08-08 - 
      }
      if(strpos($this->trackerImg2, "http") === 0 ) {
        $desktopImg2 = $this->trackerImg2;
      } else {
        $desktopImg2 = $this->trackerImg2 ? "$headerImg2Location$this->trackerImg2" : null; // BLP 2023-08-10 -
      }
      if(strpos($this->trackerImgPhone2, "http") === 0) {
        $phoneImg2 = $this->trackerImgPhone2;
      } else {
        $phoneImg2 = $this->trackerImgPhone2 ? "$headerImg2Location$this->trackerImgPhone2" : null; // BLP 2023-08-10 - 
      }

      // Should we track and do we actually want a database ?
      
      if($this->noTrack !== true && $this->nodb !== true) {
        $mysitemap = $this->mysitemap;

        // If we are 'tracking' users add tracker.js and logging.js
        
        $trackerStr = "<script nonce='$this->nonce' src='$this->trackerLocationJs'></script>\n"; 

        if($this->nointeraction !== true)
          $trackerStr .= "<script nonce='$this->nonce' src='$this->interactionLocationJs'></script>\n";
      } else {
        // Either or both noTrack and nodb were set.
        // This is the code we use instead of tracker.js if noTrack or nodb are true.

        $trackerStr =<<<EOF
<script nonce='$this->nonce'>
/* Minimal tracker.js logic if noTrack */

'use strict';

const TRACKERJS_VERSION = "default_tracker.js_from_site_class_getPageHead";

// The very first thing we do is get the lastId from the script tag.

const lastId = $("script[data-lastid]").attr("data-lastid");
console.log("navigator.userAgentData: ", navigator.userAgentData);

jQuery(document).ready(function($) {
  if(noCssLastId !== '1') {
    $("script[data-lastid]").before('<link rel="stylesheet" href="csstest-' + lastId + '.css" title="blp test">');
  }
  
  let picture = '';

  if(!phoneImg) {
    picture += "<img id='logo' src=" + desktopImg + " alt='desktopImage'>";
  } else if(!desktopImg) {
    picture += "<img id='logo' src=" + phoneImg + " alt='phoneImage'>";
  } else { // We have a phone and desktop image.
    picture = "<picture id='logo'>";
    picture += "<source srcset=" + phoneImg + " media='((hover: none) and (pointer: coarse))' alt='phoneImage'>";
    picture += "<source srcset=" + desktopImg + " media='((hover: hover) and (pointer: fine))' alt='desktopImage'>";
    picture += "<img src=" + phoneImg + " alt='phoneImage'>";
    picture += "</picture>";
  }

  if(phoneImg || desktopImg) {
    $("header a:first-of-type").first().html(picture);
  }
  
  $("#headerImage2").remove();

  picture = '';
  
  if(!phoneImg2) {
    picture += "<img id='headerImage2' src=" + desktopImg2 + " alt='desktopImage2'>";
  } else if(!desktopImg2) {
    picture += "<img id='headerImage2' src=" + phoneImg2 + " alt='phoneImage2'>";
  } else {
    picture = "<picture id='headerImage2'>";
    picture += "<source srcset=" + phoneImg2 + " media='((hover: none) and (pointer: coarse))' alt='phoneImage2'>";
    picture += "<source srcset=" + desktopImg2 + " media='((hover: hover) and (pointer: fine))' alt='desktopImage2'>";
    picture += "<img src=" + phoneImg2 + " alt='phoneImage'>";
    picture += "</picture>";
  } 

  if(phoneImg2 || desktopImg2) {
    $("header a:first-of-type").after(picture);
  }
  
  console.log("VARIABLES -- thesite: " + thesite + ", theip: " + theip + ", thepage: " + thepage + 
              ", phoneImg: " + phoneImg + ", desktopImg: " + desktopImg +
              ", phoneImg2: " + phoneImg2 + ", desktopImg2: " + desktopImg2);
});
</script>
EOF;
      } // End of logic is noTrack or nodb is true.

      // Now fill in the rest of $trackerStr.
      // If noTrack or nodb then many of the items will be empty.

      $page = basename($this->self); // BLP 2025-04-12 - get rid of the slash.
      
      $xtmp = <<<EOF
  <script nonce="$this->nonce">
  var thesite = "$this->siteName",
      theagent = "$this->agent", // BLP 2025-04-08 - 
      theip = "$this->ip",
      thepage = "$page",
      trackerUrl = "$trackerLocation",
      beaconUrl = "$beaconLocation",
      noCssLastId = "$this->noCssLastId",
      desktopImg = "$desktopImg", 
      phoneImg = "$phoneImg"; 
      desktopImg2 = "$desktopImg2";
      phoneImg2 = "$phoneImg2", 
      mysitemap = "$mysitemap",
      lastId = "$this->LAST_ID", // BLP 2025-03-25 -
      loggingphp = "$this->interactionLocationPhp" // BLP 2025-03-26 - 
  </script>
EOF;
      $trackerStr = "$xtmp\n$trackerStr";
    } // End of $this->nojquery !=== true. That is we want jQuery.

    // Add language and things like a manafest etc. to the <html> tag.
    
    $html = '<html lang="' . $lang . '" ' . $htmlextra . ">"; // stuff like manafest etc.

    $h->jQuery = $jQuery;
    $h->trackerStr = $trackerStr;
    
    // What if headFile is null? Use the Default Head.

    if(!is_null($this->headFile)) {
      if(($p = require($this->headFile)) !== 1) {
        $pageHeadText = "{$html}\n$p";
      } else {
        throw new Exception(__CLASS__ . " " . __LINE__ .": $this->siteName, getPageHead() headFile '$this->headFile' returned 1");
      }
    } else {
      // Make a default <head>
      
      $pageHeadText =<<<EOF
$html
<!-- Default Head -->
<head>
$h->title
  <!-- METAs -->
  <meta charset="utf-8"/>
  <meta name="description" content="{$this->desc}"/>
  <!-- local link -->
$this->link
$h->jQuery
$h->trackerStr
  <!-- extra -->
$h->extra
  <!-- remote script -->
$h->script
  <!-- inline script -->
$h->inlineScript
  <!-- local css -->
$h->css
</head>
EOF;
    } // End default head.

    // Add tthe preheadcomment and doctype and the pageHeadText
    
    $pageHead = <<<EOF
{$preheadcomment}{$this->doctype}
$pageHeadText
EOF;

    return $pageHead;
  }
  
  /**
   * getPageBanner()
   * Get Page Banner
   * @return string banner
   * NOTE: The body tag is done HERE!
   */
  // BLP 2025-04-08 - I should use $b = new stdClass and make everything $b->name.
  
  public function getPageBanner():string {
    $b = new stdClass; // b is for banner

    $b->bodytag = $this->bodytag ?? "<body>";
    $b->mainTitle = $this->banner ?? $this->mainTitle;

    // If we have nodb or noTrack then there will be no tracker.js or tracker.php
    // so we can't set the images at all.

    if($this->nodb !== true && $this->noTrack !== true) {
      $trackerLocation = $this->trackerLocation ?? "https://bartonlp.com/otherpages/tracker.php";

      // BLP 2024-12-17 - 
      $b->image1 = "<!-- Image1 is provided by tracker.js if JavaScropt is not disabled -->\n";

      // We start out with the <img id='headerImage2'> having the NO SCRIPT logo, because this will
      // be changed by tracker.js if the user has Javascript.
      
      $b->image2 = "<!-- This is originally set to noscript.svg in SiteClass via 'image=noscriript.svg'. ".
                "If JavaScript is enabled then tracker.js add the images from mysitemap.json, 'trackerImg1 or 2 etc. -->\n".
                "<img id='headerImage2' alt='headerImage2' src='$trackerLocation?page=normal".
                "&amp;id=$this->LAST_ID&amp;image=/images/noscript.svg&amp;mysitemap=$this->mysitemap' alt='NO SCRIPT'>";

      $b->image3 = "<img id='noscript' alt='noscriptImage' src='$trackerLocation?page=noscript&amp;id=$this->LAST_ID&amp;mysitemap=$this->mysitemap'>";
    }

    $b->logoAnchor = $this->logoAnchor ?? "https://www.$this->siteDomain";
    
    if(!is_null($this->bannerFile)) {
      $b->pageBannerText = require($this->bannerFile);
    } else {
      // a default banner
      $b->pageBannerText =<<<EOF
<!-- Default Header/Banner -->
<header>
<div id='pagetitle'>
$b->mainTitle
</div>
<noscript style="color: red; border: 1px solid black; padding: 10px; font-size: large;">
<strong>Your browser either does not support JavaScripts
or you have JavaScripts disabled.</strong>
</noscript>
</header>
EOF;
    }

    // Return the Banner

    return <<<EOF
$b->bodytag
$b->pageBannerText

EOF;
  }

  /**
   * getPageFooter()
   * Get Page Footer
   * @return string
   * Uses $b, however the old values are still available for old includes.
   */
  // BLP 2025-04-08 - changed all $b=>name to $f->name. $f for footer.
  
  public function getPageFooter():string {
    // If nofooter is true just return an empty footer

    if($this->nofooter === true) {
      return <<<EOF
<footer>
</footer>
</body>
</html>
EOF;
    }

    $f = new stdClass; // $f is for footer

    $f->ctrmsg = $this->ctrmsg;
    $f->msg = $this->msg;
    $f->msg1 = $this->msg1;
    $f->msg2 = $this->msg2;
    
    $f->address = $this->noAddress ? null : ($this->address . "<br>");
    $noCopyright = $this->noCopyright;
    $f->copyright = $noCopyright ? null : ($this->copyright . "<br>");
    if(preg_match("~^\d{4}~", $f->copyright) === 1) {
      $f->copyright = "Copyright &copy; $f->copyright";
    }

    $f->aboutwebsite = $this->aboutwebsite ??
                       "<h2><a target='_blank' href='https://bartonlp.com/otherpages/aboutwebsite.php?" .
                       "site=$this->siteName&domain=$this->siteDomain'>About This Site</a></h2>";
    
    $f->emailAddress = $this->noEmailAddress ? null : ($this->emailAddress ?? $this->EMAILADDRESS);
    $f->emailAddress = $this->emailAddress ? "<a href='mailto:$this->emailAddress'>$this->emailAddress</a>" : null;

    // Set the $b values from the b_ values
    
    $f->inlineScript = $this->b_inlineScript ? "<script nonce='$this->nonce'>\n$this->b_inlineScript\n</script>" : null;
    $f->script = $this->b_script;
    $f->extra = $this->b_extra; // BLP 2025-03-26 -
    
    // counterWigget is available to the footerFile to use if wanted.
    
    if($this->noCounter !== true) {
      $counterWigget = $this->getCounterWigget($this->ctrmsg); // ctrmsg may be null which is OK
    }

    // Lastmod is also available to footerFile to use if wanted.

    if($this->noLastmod !== true) {
      $lastmod = "Last Modified: " . date("M j, Y H:i", getlastmod());
    }

    // Add noGeo

    if($this->noGeo !== true && $this->noTrack !== true && $this->nodb !== true) {
      $geo = $this->gioLocation ?? "https://bartonphillips.net/js";
      
      $geo = "<script src='$geo/geo.js'></script>";
    }

    // BLP 2025-02-21 - $counterWigget, $lastmod and $geo are still available to old footer
    // includes files. $b now is used in bartonphillips.com/includes/footer.i.php.
    $f->counterWigget = $counterWigget;
    $f->lastmod = $lastmod;
    $f->geo = $geo;
    
    // We can put the footerFile into $S or use it from mysitemap.json
    // If either is set to 'false' then use the default footer, else use $this->footerFile unless
    // it is false.
    
    if($this->footerFile !== false && $this->footerFile !== null) {
      $pageFooterText = require($this->footerFile);
    } else {
      $pageFooterText = <<<EOF
<!-- Default Footer -->
<footer>
$f->aboutwebsite
$f->counterWigget
$f->lastmod
$f->script
$f->inlineScript
</footer>
</body>
</html>
EOF;
    }

    return $pageFooterText;
  }

  /**
   * getCounterWigget()
   */

  public function getCounterWigget(?string $msg="Page Hits"):?string {
    // Counter at bottom of page
    // hitCount is updated by 'counter()' in Database.

    $hits = number_format($this->hitCount);

    // Let the appearance be up to the pages css!
    // However, the defaultCss is bartonphillips.net/css/blp.css it includes hitcounter.css which
    // sets the following values.
    // #hitCounter, #hitCountertbl, #hitCountertr and #hitCounterth.
    // See bartonphillips.net/css/hitcounter.css for all the info.
    // So to override the values enter the css AFTER the defaultCss and change the values of the ids.
    
    return <<<EOF
<div id="hitCounter">
$msg
<table id="hitCountertbl">
<tr id='hitCountertr'>
<th id='hitCounterth'>
$hits
</th>
</tr>
</table>
</div>
EOF;
  }

  /**
   * __toString();
   */

  public function __toString() {
    return __CLASS__;
  }
} // End of Class
