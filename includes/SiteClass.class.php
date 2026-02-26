<?php
// php must change when the GitHub Release version changes.
// Note that the constructor calls the Database constructor which in turn call the
// dbPdoconstructor which does all of the heavy lifting.

namespace bartonlp\SiteClass;
   
/**
 * @file SiteClass.class.php
 * @package SiteClass
 */
define("SITE_CLASS_VERSION", "6.0.1pdo");

// One class for all my sites
/**
 * SiteClass. Top of the SiteClass framework.
 *
 * This is the top of the framework.
 * SiteClass extends Database.
 * Database extends dbPdo.
 * dbPdo extends PDO. This is the standard PHP PDO class.
 *
 * @package SiteClass
 * @author Barton Phillips <barton@bartonphillips.com>
 * @link http://www.bartonphillips.com
 * @copyright Copyright (c) 2025, Barton Phillips
 * @license MIT
 * @see https://github.com/bartonlp/site-class My GitHub repository
 */
class SiteClass extends Database {
  // Give these default values incase they are not mentioned in mysitemap.json.
  // Note they could still be null from mysitemap.json!

  /**
   * Whether to display the hit counter widget.
   *
   * If true, the hit count will be shown in the counter widget.
   * @var bool 
   */
  public $count = true;

  /**
   * The document type declaration (e.g., `<!DOCTYPE html>`).
   *
   * Starts with a default value unless overridden.
   *
   * @var string
   */
  public $doctype = "<!DOCTYPE html>";

  /**
   * SiteClass constructor.
   *
   * The object passed in is usually from mysitemap.json, which contains all
   * important configuration settings.
   *
   * @param object $s Configuration object from mysitemap.json
   * @see https://bartonlp.org/docs/mysitemap.json for full details
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
   * Get the version of SiteClass
   *
   * @return string The version from the define at the start of SiteClass
   */
  public static function getVersion():string {
    return SITE_CLASS_VERSION;
  }

  /**
   * getHitCount() Gets the number of times someone visited our page
   *
   * @return int
   */
  public function getHitCount():int {
    return $this->hitCount;
  }

  /**
   * Get the `<!DOCTYPE ...>` 
   *
   * @return string
   */
  public function getDoctype():string {
    return $this->doctype;
  }

  /**
   * Get the top and bottom of the page
   *
   * Get the top of the page i.e. `<head>` information,
   * and the bottom of the page i.e. JavaScrip just before `</body>`
   * The method is usually used like this: `[$top, $bottom] = $S->getPageTopBottom();`.
   * When the page is rendered with an `echo <<<EOF`, the first line is $top,
   * and the last line is $bottom followed by EOF;
   * The content is usually placed between these two variables.
   *
   * @return array{0: string, 1: string} [$top, $bottom]
   */
  public function getPageTopBottom():array {
    // Do getPageTop and getPageFooter

    $top = $this->getPageTop();

    $bottom = $this->footer ?? $this->getPageFooter(); // We could have a different pageFooter.

    // return the array which we usually get via '[$top, $footer] = $S->getPageTopBottom()'

    return [$top, $bottom];
  }

  /**
   * Get the top of the page
   *
   * Gets all of the `<head>` info and the `<body>` tag, and usually the `<div id="content">`.
   * It also gets the banner information.
   * The ending `</div>` is supplied in the $bottom variable. 
   *
   * @return string
   * @see https://bartonlp.org/docs/head.i.php
   * @see https://bartonlp.org/banner.i.php
   */
  public function getPageTop():string {
    // Get the page <head> section

    $head = $this->getPageHead();

    // Get the page's banner section (<header>...</header>)
    
    $banner = $this->getPageBanner();

    return "$head\n$banner";
  }

  /**
   * Get the `<head>` content
   *
   * This method does a lot of work. It looks at all of the items that come from mysitemap.json.
   * It looks to see if a value is set and if not provides defaults if applicable.
   * It creates the $h standard class that is passed to the head.i.php file,
   * which is (usually) in the includes directory of the document root of the website.
   *
   * @return string
   * @throws \Exception If require headfile returns 1.
   * @see https://bartonlp.org/docs/head.i.php.
   */
  public function getPageHead():string {
    // Instantiate a stdClass so we can pass things to the headFile.
    
    $h = new \stdClass;

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

    $h->favicon = $this->cacheBuster($this->favicon ?? SITECLASS_DEFAULT_NAME."/images/favicon.ico");
    $h->favicon = "<link rel='shortcut icon' href='$h->favicon'>";

    // Get the defaultCss if available. It hust be a string or false or null.

    if($this->defaultCss === false) { 
      $h->defaultCss = null;
    } else {
      $h->defaultCss = $this->cacheBuster($this->defaultCss ?? SITECLASS_DEFAULT_NAME."/css/blp.css");
      $h->defaultCss = "<link rel='stylesheet' href='$h->defaultCss' title='default'>";
    }

    // The css and inlineScript need to have the <style> or <script> tabs added.
    
    $h->css = $this->css ? "<style>\n$this->css\n</style>" : null;
    $h->inlineScript = $this->h_inlineScript ? "<script nonce='$this->nonce'>\n$this->h_inlineScript\n</script>" : null;

    // If I have $this->cssLink then create the external link
    // The cssLink can be an string or an array of strings.
    // Like $S->cssLink = ['one.css', 'two.css']; or $S->css = 'one.css';
    
    if($this->cssLink) {
      $cssLinks = is_array($this->cssLink) ? $this->cssLink : [$this->cssLink];
      $h->cssLink = '';

      foreach($cssLinks as $link) {
        $link = $this->cacheBuster($link);
        $h->cssLink .= "<link rel='stylesheet' href='$link'>\n";
      }
    }

    // The rest, $h->link, $h->script and $h->extra need to have the full '<link' or '<script' tags
    // in the variables.

    // These three probably do not need cacheBusters because they usually come from an external
    // source. 'extra' isn't used much any more and is usually inline code.
    $h->script = $this->h_script ? $this->cacheBustAssetTags($this->h_script) : null;
    $h->extra = $this->h_extra ? $this->cacheBustAssetTags($this->h_extra) : null;
    $h->link = $this->link ? $this->cacheBustAssetTags($this->link) : null;
    
    $preheadcomment = $this->preheadcomment; // Must be a real html comment ie <!-- ... -->

    $lang = $this->lang ?? 'en';

    $htmlextra = $this->htmlextra; // Must be full html
    
    // If nojquery is true then don't add $trackerStr at all.
    // So here !== true means that it is really false or null.
    
    if($this->nojquery !== true) {
      // Add the jQuery info and the JavaScript var items.
      // BLP 2025-04-26 - Add id to jQuery so I can locate it in tracker.js and add the csstest.
      
      $jQuery = <<<EOF
  <!-- BLP 2024-12-31 - Latest version 3.7.1 and migrate 3.5.2 -->
  <script nonce="$this->nonce" id='jQuery' src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script nonce="$this->nonce" src="https://code.jquery.com/jquery-migrate-3.5.2.min.js" integrity="sha256-ocUeptHNod0gW2X1Z+ol3ONVAGWzIJXUmIs+4nUeDLI=" crossorigin="anonymous"></script>
  <script nonce="$this->nonce">jQuery.migrateMute = false; jQuery.migrateTrace = false;</script>
EOF;

      $this->trackerLocationJs = $this->trackerLocationJs ?? SITECLASS_OTHERPAGES."/js/tracker.js";
      $this->trackerLocationJs = $this->cacheBuster($this->trackerLocationJs);
      
      // add the location of the logging.js and logging.php files.
      
      $this->interactionLocationJs = $this->interactionLocationJs ?? SITECLASS_OTHERPAGES."/js/logging.js";
      $this->interactionLocationJs = $this->cacheBuster($this->interactionLocationJs);
      // Add a cache buster

      $this->interactionLocationPhp = $this->interactionLocationPhp ?? SITECLASS_OTHERPAGES."/logging.php";
      
      // tracker.php and beacon.php MUST be symlinked in bartonlp.com/otherpages
      // to the SiteClass 'includes' directory. This is because /var/www/site-class does not have
      // its own and therefore we can't get to it directly. https://bartonlp has its own
      // domain and we can get to it.

      $trackerLocation = $this->trackerLocation ?? SITECLASS_OTHERPAGES."/tracker.php"; // a symlink
      $beaconLocation = $this->beaconLocation ?? SITECLASS_OTHERPAGES."/beacon.php"; // a symlink

      $logoImgLocation = $this->logoImgLocation ?? SITECLASS_DEFAULT_NAME;
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
      if(strpos($this->trackerImg2, "http") === 0) {
        $desktopImg2 = $this->trackerImg2;
      } else {
        $desktopImg2 = $this->trackerImg2 ? "$headerImg2Location$this->trackerImg2" : null; // BLP 2023-08-10 -
      }
      if(strpos($this->trackerImgPhone2, "http") === 0) {
        $phoneImg2 = $this->trackerImgPhone2;
      } else {
        $phoneImg2 = $this->trackerImgPhone2 ? "$headerImg2Location$this->trackerImgPhone2" : null; // BLP 2023-08-10 - 
      }

      // Should we track and have doSiteClass true?
      
      if($this->noTrack !== true && $this->doSiteClass === true && $this->dbinfo->engine == 'mysql') {
        $mysitemap = $this->mysitemap;

        // If we are 'tracking' users add tracker.js and logging.js
        
        $trackerStr = "<script nonce='$this->nonce' src='$this->trackerLocationJs'></script>\n"; 

        // Now fill in the rest of $trackerStr.

        $page = basename($this->self); 
      
        $xtmp = <<<EOF
  <script nonce="$this->nonce">
  let thesite      = "$this->siteName",
      theagent     = "$this->agent", 
      theip        = "$this->ip",
      thepage      = "$page",
      trackerUrl   = "$trackerLocation",
      beaconUrl    = "$beaconLocation",
      noCssLastId  = "$this->noCssLastId",
      desktopImg   = "$desktopImg", 
      phoneImg     = "$phoneImg"; 
      desktopImg2  = "$desktopImg2";
      phoneImg2    = "$phoneImg2", 
      mysitemap    = "$mysitemap",
      lastId       = "$this->LAST_ID",
      loggingphp   = "$this->interactionLocationPhp";
  </script>
EOF;
        $trackerStr = "$xtmp\n$trackerStr";

        if($this->nointeraction !== true) {
          $trackerStr .= "<script nonce='$this->nonce' src='$this->interactionLocationJs'></script>\n";
        }
      } else {
        // doSiteClass is false or not there.
        // We can have noTrack true if we want the simple to use logagent.
        // This is the code we use instead of tracker.js.

        $trackerLocation = $trackerLocationJs = " ";
        $beaconLocatin = " ";
        
        $trackerStr =<<<EOF
<script nonce='$this->nonce'>
/* Minimal tracker.js logic if noTrack */

'use strict';

const TRACKERJS_VERSION = "default_tracker.js_from_site_class_getPageHead";

console.log("navigator.userAgentData: ", navigator.userAgentData);

jQuery(document).ready(function($) {
  let desktopImg        = "$desktopImg",
      phoneImg          = "$phoneImg",
      desktopImg2       = "$desktopImg2",
      phoneImg2         = "$phoneImg2";
  
  let picture = '';

  if(!phoneImg) {
    picture = "<img id='logo' src=" + desktopImg + " alt='desktopImage'>";
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
    picture = "<img id='headerImage2' src=" + desktopImg2 + " alt='desktopImage2'>";
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
  
  console.log("VARIABLES -- phoneImg: " + phoneImg + ", desktopImg: " + desktopImg +
              ", phoneImg2: " + phoneImg2 + ", desktopImg2: " + desktopImg2);
});
</script>
EOF;
      } // End of logic No doSiteClass
    } // End of $this->nojquery !=== true. That is we want jQuery.

    // Add language and things like a manafest etc. to the <html> tag.
    
    $html = '<html lang="' . $lang . '" ' . $htmlextra . ">"; // stuff like manafest etc.

    $h->jQuery = $jQuery;
    $h->trackerStr = $trackerStr;
    
    // What if headFile is null? Use the Default Head.

    if(!is_null($this->headFile)) {
      if(($p = require($this->headFile)) !== 1) {
        // $p has the contents of the header file.
        $pageHeadText = "{$html}\n$p";
      } else {
        // require returned 1 which is wrong!!
        
        throw new Exception(__CLASS__ . " " . __LINE__ .
                            ": $this->siteName, getPageHead() headFile '$this->headFile' returned 1");
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

    // Add the preheadcomment and doctype and the pageHeadText
    
    $pageHead = <<<EOF
{$preheadcomment}{$this->doctype}
$pageHeadText
EOF;

    return $pageHead;
  }
  
  /**
   * Get the banner information.
   *
   * The information goes into the `<header>` section of the page.
   * It usually has header images etc.
   *
   * @return string
   * @see https://bartonlp.org/docs/banner.i.php
   */
  public function getPageBanner():string {
    $b = new \stdClass; // b is for banner

    $b->bodytag = $this->bodytag ?? "<body>";
    $b->mainTitle = $this->banner ?? $this->mainTitle;

    // If noTrack then there will be no tracker.js or tracker.php
    // so we can't set the images at all.

    if($this->noTrack !== true) {
      $trackerLocation = $this->trackerLocation ?? SITECLASS_OTHERPAGES."/tracker.php";

      $b->image1 = "<!-- Image1 is provided by tracker.js -->\n";

      // We start out with the <img id='headerImage2'> having the NO SCRIPT logo, because this will
      // be changed by tracker.js if the user has Javascript.

      $mypage = $this->doSiteClass ? "page=normal&amp;" : null;
      $myscript = $this->doSiteClass ? "page=noscript&amp;" : null;
      
      $b->image2 = "<img id='headerImage2' alt='headerImage2' src='$trackerLocation?$mypage".
                   "id=$this->LAST_ID&amp;image=/images/noscript.svg&amp;".
                   "mysitemap=$this->mysitemap' alt='NO SCRIPT'>";

      $b->image3 = "<img id='noscript' alt='noscriptImage' src='$trackerLocation?$myscript".
                   "id=$this->LAST_ID&amp;mysitemap=$this->mysitemap'>";
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
   * Get the page footer
   *
   * Gets the bottom (footer) of the page. This (usually) has the ending `</div>` for the
   * `<div id='content'>` in the top of the page.
   *
   * @return string
   * @see https://bartonlp.org/docs/footer.i.php
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

    $f = new \stdClass; // $f is for footer

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

    // Need to check each one of the scripts to see if it needs a cacheBuster.
    
    $f->script = $this->b_script ? $this->cacheBustAssetTags($this->b_script) : null;
    $f->extra = $this->b_extra ? $this->cacheBustAssetTags($this->b_extra) : null;
    
    // counterWigget is available to the footerFile to use if wanted.
    
    if($this->noCounter !== true) {
      $counterWigget = $this->getCounterWigget($this->ctrmsg); // ctrmsg may be null which is OK
    }

    // Lastmod is also available to footerFile to use if wanted.

    if($this->noLastmod !== true) {
      $lastmod = "Last Modified: " . date("M j, Y H:i", getlastmod());
    }

    // Add noGeo

    if($this->noGeo !== true && $this->noTrack !== true) {
      $geo = $this->geoLocation ?? SITECLASS_DEFAULT_NAME."/js";

      $geo = $this->cacheBuster("$geo/geo.js");
      $geo = "<script src='$geo'></script>";
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
   * Get the footer counter widget
   *
   * Uses the hit count from the barton.counter table realcnt field.
   
   * @return string
   * @see \Database::counter() method, does page counting.
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
   * Get the class name
   *
   * The class name is returned
   * @return string
   */
  public function __toString(): string {
    return __CLASS__;
  }

  /**
   * Generate a cache-busting URL based on the file's modification time.
   * Only supports files hosted by this server and mapped to known domains.
   *
   * @param string $file Relative or absolute URL (must resolve under /var/www)
   * @return string URL with ?v=timestamp, or on error the original filename.
   */
  public function cacheBuster(string $file): ?string {
    $parsed = parse_url($file);
    $path = $parsed['path'] ?? null;

    if(!$path) return $file;

    // Try full physical path based on DOCUMENT_ROOT
    if(str_starts_with($file, 'https://')) {
      $fullpath = "/var/www/" . str_replace("https://", '', $file);
    } else {
      $fullpath = $_SERVER['DOCUMENT_ROOT'] . $path;
    }
    
    if(!file_exists($fullpath)) {
      // If that doesn't work, maybe our docroot is inside a subdir (like /rivertownerentals)
      $cwd = getcwd(); // e.g. /var/www/newbern-nc.info/rivertownerentals
      if(!str_starts_with($path, '/')) $path = "/$path";
      $fullpath = $cwd . $path;

      if(!file_exists($fullpath)) {
        //error_log("cacheBuster: ip=$this->ipfile, site=$this->siteName, page=$this->self, file=$file not found $fullpath");
        return $file; // gracefully fallback
      }

      // Remove the start of $fullpath and only use the what comes after .com
      // This will be the $fullpath that we found.
      
      $file = preg_replace("~/var/www/.*?\.com~", '', $fullpath);

      $version = filemtime($fullpath);
      return "{$file}?v={$version}";
    }

    // If DOCUMENT_ROOT + path worked, use original file
    $version = filemtime($fullpath);
    return "{$file}?v={$version}";
  }

  /**
   * Take apart the h_script and b_script and do cacheBusting.
   *
   * The string can be:
   * `<script ... src='Url or path' ...></script>`
   * and
   * `<script>...</script>`
   * So take each string and put it into an array.
   * Note: The string could be `<script ... src='...' ...></script><script ...`
   * Remove the relative or fully qualified URL and pass it to `cacheBuster()`.
   * Take the return value from cacheBuster and put it back into the original 'src='
   * location. Do not process `<script>...</script>`. These should not be touched.
   * Convert the array back into a string and return it.
   * @param string $script
   * @return string Fixed up entries.
   */
  public function cacheBustAssetTags(string $html): string {
    return preg_replace_callback(
                                 '~<(?<tag>\w+)\b[^>]*(?<attr>\bsrc|\bhref)=["\'](?<url>[^"\']+)["\'][^>]*>(?:</\1>)?~i',
                                 function($match) {
      $tag = $match['tag'];
      $attr = $match['attr'];
      $url = $match['url'];
      $originalTag = $match[0];

      // Only process certain tags and attributes
      $allowedTags = ['script', 'link', 'img', 'source', 'video', 'audio'];
      if(!in_array(strtolower($tag), $allowedTags)) return $originalTag;

      $busted = $this->cacheBuster($url);
      return str_replace($url, $busted, $originalTag);
    }, $html);
  }
} // End of Class
