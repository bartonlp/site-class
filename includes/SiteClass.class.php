<?php
// SITE_CLASS_VERSION must change when the GitHub Release version changes.
// Note that the constructor calls the Database constructor which in turn call the
// dbMysqli constructor which does all of the heavy lifting.

// This is using PDO.

define("SITE_CLASS_VERSION", "5.0.0pdo");

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

// Make an alias for getinfo.

use \bartonlp\siteload\getinfo as load;

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
   *  $s has the values from $_site = require_once(getenv("SITELOADNAME"));
   *  which uses siteload.php to gets values from mysitemap.json.
   */
  
  public function __construct(object $s) {
    // Do the parent Database constructor which does the dbMysqli constructor.
    
    parent::__construct($s); // Turns everything in $s into $this.

    // BLP 2018-07-01 -- Add the date to the copyright notice if one exists

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
   */

  public function getPageHead():string {
    // Instantiate a stdClass so we can pass things to the headFile.
    
    $h = new stdClass;

    $dtype = $this->doctype; // note that $this->doctype could also be from mysitemap.json see the constructor.

    $h->base = $this->base ? "<base src='$this->base'>" : null;

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

    if($this->defaultCss === false || $this->defaultCss === true) { 
      $h->defaultCss = null;
    } else { // Else either add the value or the default.
      $h->defaultCss = $this->defaultCss ? "<link rel='stylesheet' href='$this->defaultCss' title='default'>" :
                       "<link rel='stylesheet' href='https://bartonphillips.net/css/blp.css' title='default'>";
    }

    // These need to have the <style> or <script> tabs added.
    
    $h->css = $this->css ? "<style>\n$this->css\n</style>" : null;
    $h->inlineScript = $this->h_inlineScript ? "<script>\n$this->h_inlineScript\n</script>" : null;
    
    // The rest, $h->link, $h->script and $h->extra need to have the full '<link' or '<script' tags
    // in the variables.

    $h->script = $this->h_script;
    $h->link = $this->link;
    $h->extra = $this->extra;
    
    $preheadcomment = $this->preheadcomment; // Must be a real html comment ie <!-- ... -->
    $lang = $this->lang ?? 'en';
    $htmlextra = $this->htmlextra; // Must be full html
    
    // If nojquery is true then don't add $trackerStr at all.

    if($this->nojquery !== true) {
      $jQuery = <<<EOF
  <!-- jQuery BLP 2022-12-21 - Latest version -->
  <script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>
  <script src="https://code.jquery.com/jquery-migrate-3.4.0.min.js" integrity="sha256-mBCu5+bVfYzOqpYyK4jm30ZxAZRomuErKEFJFIyrwvM=" crossorigin="anonymous"></script>
  <script>jQuery.migrateMute = false; jQuery.migrateTrace = false;</script>
EOF;

      // BLP 2023-02-20 - trackerLocationJs needs to be part of $this for whatisloaded.class.php.

      $this->trackerLocationJs = $this->trackerLocationJs ?? "https://bartonlp.com/otherpages/js/tracker.js";

      // BLP 2023-08-09 - tracker.php and beacon.php MUST be symlinked into the parents
      // directory!

      $trackerLocation = $this->trackerLocation ?? "https://bartonlp.com/otherpages/tracker.php"; // BLP 2023-08-09 - a symlink
      $beaconLocation = $this->beaconLocation ?? "https://bartonlp.com/otherpages/beacon.php"; // BLP 2023-08-09 - a symlink

      $logoImgLocation = $this->logoImgLocation ?? "https://bartonphillips.net"; // BLP 2023-08-08 -
      $headerImg2Location = $this->headerImg2Location ?? $logoImgLocation ?? "https://bartonphillips.net"; // BLP 2023-08-10 -

      // The trackerImg... can start with http or https. If so use the full url.

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

      if($this->noTrack !== true && $this->nodb !== true) {
        // 'load' is an alias for getinfo() in siteload.php. See the top of this
        // program for the 'use' alias.
        // I use $mysitemap in tracker.php to be able to not have symlinks in all of my domains.

        try {
          $mysitemap = load::$mysitemap;
        } catch(Throwable $e) {
          $mysitemap = "mysitemap.json";
        }
        
        // If not noTrack or nbdb add the tracker.js location.
        
        $trackerStr = "  <script data-lastid='$this->LAST_ID' src='$this->trackerLocationJs'></script>\n";
      }

      // Now fill in the rest of $trackerStr.
      // If noTrack or nodb then many of the items will be empty.
      
      $trackerStr .=<<<EOF
  <script>
    var thesite = "$this->siteName",
    theip = "$this->ip",
    thepage = "$this->self",
    trackerUrl = "$trackerLocation",
    beaconUrl = "$beaconLocation",
    noCssLastId = "$this->noCssLastId",
    desktopImg = "$desktopImg", 
    phoneImg = "$phoneImg"; 
    desktopImg2 = "$desktopImg2";
    phoneImg2 = "$phoneImg2", 
    mysitemap = "$mysitemap" 
  </script>
EOF;
    }
    
    $html = '<html lang="' . $lang . '" ' . $htmlextra . ">"; // stuff like manafest etc.

    // What if headFile is null? Use the Default Head.

    if(!is_null($this->headFile)) {
      if(($p = require_once($this->headFile)) != 1) {
        $pageHeadText = "{$html}\n$p";
      } else {
        throw new SqlException(__CLASS__ . " " . __LINE__ .": $this->siteName, getPageHead() headFile '$this->headFile' returned 1", $this);
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
$jQuery
$trackerStr
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
    }

    // Default header has < /> elements. If not XHTML we remove the /> at the end!
    $pageHead = <<<EOF
{$preheadcomment}{$dtype}
$pageHeadText
EOF;

    return $pageHead;
  }
  
  /**
   * getPageBanner()
   * Get Page Banner
   * BLP 2022-01-30 -- New logic
   * @return string banner
   * NOTE: The body tag is done HERE!
   */

  public function getPageBanner():string {
    $h = new stdClass;

    // BLP 2022-04-09 - These need to be checked here.
    
    $bodytag = $this->bodytag ?? "<body>";
    $mainTitle = $this->banner ?? $this->mainTitle;

    // If we have nodb or noTrack then there will be no tracker.js or tracker.php
    // so we can't set the images at all.

    if($this->nodb !== true && $this->noTrack !== true) {
      $trackerLocation = $this->trackerLocation ?? "https://bartonlp.com/otherpages/tracker.php";

      // We start out with the <img id='headerImage2'> having the NO SCRIPT logo, because this will
      // be changed by tracker.js if the user has Javascript.

      $image2 = "<img id='headerImage2' alt='headerImage2' src='$trackerLocation?page=normal&amp;id=$this->LAST_ID&amp;image=/images/noscript.svg' alt='NO SCRIPT'>";

      $image3 = "<img id='noscript' alt='noscriptImage' src='$trackerLocation?page=noscript&amp;id=$this->LAST_ID'>";
    }

    $h->logoAnchor = $this->logoAnchor ?? "https://www.$this->siteDomain";
    
    if(!is_null($this->bannerFile)) {
      $pageBannerText = require($this->bannerFile);
    } else {
      // a default banner
      $pageBannerText =<<<EOF
<!-- Default Header/Banner -->
<header>
<div id='pagetitle'>
$mainTitle
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
$bodytag
$pageBannerText

EOF;
  }

  /**
   * getPageFooter()
   * Get Page Footer
   * @return string
   */
  
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

    $b = new stdClass;

    $b->ctrmsg = $this->ctrmsg;
    $b->msg = $this->msg;
    $b->msg1 = $this->msg1;
    $b->msg2 = $this->msg2;
    
    $b->address = $this->noAddress ? null : ($this->address . "<br>");
    $noCopyright = $this->noCopyright;
    $b->copyright = $noCopyright ? null : ($this->copyright . "<br>");
    if(preg_match("~^\d{4}~", $b->copyright) === 1) {
      $b->copyright = "Copyright &copy; $b->copyright";
    }
    
    $b->aboutwebsite = $this->aboutwebsite ??
                       "<h2><a target='_blank' href='https://bartonlp.com/otherpages/aboutwebsite.php?site=$this->siteName&domain=$this->siteDomain'>About This Site</a></h2>";
    
    $b->emailAddress = $this->noEmailAddress ? null : ($this->emailAddress ?? $this->EMAILADDRESS);
    $b->emailAddress = $this->emailAddress ? "<a href='mailto:$this->emailAddress'>$this->emailAddress</a>" : null;

    // Set the $b values from the b_ values
    
    $b->inlineScript = $this->b_inlineScript ? "<script>\n$this->b_inlineScript\n</script>" : null;
    $b->script = $this->b_script;

    // counterWigget is available to the footerFile to use if wanted.
    
    if($this->noCounter !== true) {
      $counterWigget = $this->getCounterWigget($this->ctrmsg); // ctrmsg may be null which is OK
    }

    // Lastmod is also available to footerFile to use if wanted.

    if($this->noLastmod !== true) {
      $lastmod = "Last Modified: " . date("M j, Y H:i", getlastmod());
    }

    // Add noGeo

    if($this->noGeo !== true) {
      $geo = $this->gioLocation ?? "https://bartonphillips.net/js";
      
      $geo = "<script src='$geo/geo.js'></script>";
    }

    // We can put the footerFile into $S or use it from mysitemap.json
    // If either is set to 'false' then use the default footer, else use $this->footerFile unless
    // it is false.
    
    if($this->footerFile !== false && $this->footerFile !== null) {
      $pageFooterText = require($this->footerFile);
    } else {
      $pageFooterText = <<<EOF
<!-- Default Footer -->
<footer>
$b->aboutwebsite
$counterWigget
$lastmod
$b->script
$b->inlineScript
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
    // However, the defaultCss is bartonphillipsnet/css/blp.css it includes hitcounter.css which
    // sets the following values.
    // #hitCounter, #hitCountertbl, #hitCountertr and #hitCounterth.
    // See bartonphillipsnet/css/hitcounter.css for all the info.
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
