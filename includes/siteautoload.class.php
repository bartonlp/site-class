<?php
// BLP 2015-10-21 -- try to make this backward compatable. Add back the two statics etc. plus the
// new stuff.
// BLP 2015-10-14 -- Remove static $siteinfo and $dbinfo. Add $this->siteinfo. Add return of
// $this->siteinfo at end. Add public $this->getSiteInfo();
// BLP 2015-10-13 -- Add logic to use MySitemap.php as well as .sitemap.php
// BLP 2014-12-31 -- New version with namespace and class

// Auto load Classes
// Finds the .sitemap.php for a site. Starts at the lowest level and works up until we reach a
// .sitemap.php or the DOC_ROOT without finding anything.
// If success then reads the .sitemap.php to get the layout of the site and the database and
// site info for Database and SiteClass. This info is usually used by a site specific subclass
// that enharits from SiteClass and instantiates a Database if needed.
// Once the site layout is know the class autoloader uses that to load Classes.

// DOC_ROOT is set in the siteautoload.class.php and is usually $_SERVER['DOCUMENT_ROOT'] for WEB
// programs.
// DOC_ROOT is set to realpath(dirname(TOPFILE) for CLI programs.
// TOPFILE is defined in the header in the target program and is the location of this file.
// For a CLI program TOPFILE is the full UNIX path to the this file (siteautoload.class.php).
// ON THIS SITE (bartonlp.com) $_SERVER['DOCUMENT_ROOT'] is empty for CLI programs.
// ON THIS SITE $_SERVER['PHP_SELF'] is also empty for CLI programs.
// On bartonlp.com (the ISP is digitalocean.com) we use "$_SERVER['argv'][0]" for $self.

// The site structure is defined in the '.sitemap.php' file for the site or sub-site. For
// go.myphotochannel.com we have ONLY ONE '.sitemap.php'.
// The '.sitemap.php' file defines several paths used to locate class files. See the '.sitemap.php'
// file for the ISP and site for the meaning of the following:
// INCLUDES 
// DATABASE_ENGINES
// SITE_INCLUDES
// The following are defined in this file:
// DOC_ROOT: As described above
// SITE_ROOT: This is the path to the found '.sitemap.php'.
// TARGET_ROOT: This is the path to the target file (self).
//
// Two arrays are forced into the global namesapce:
//  $GLOBALS['dbinfo'] is the $dbinfo from the .sitemap.php file
//  $GLOBALS['siteinfo'] is the $siteinfo from the .sitemap.php file

namespace SiteAutoLoad;

// Make an EOL that works for html or cli

define("EOL", (PHP_SAPI !== 'cli') ? "<br>\n" : "\n");

class SiteAutoLoad {
  private $DEBUG = false;
  static protected $siteInfo = array("NOT YET VALID"); 
  static protected $dbInfo = array("NOT YET VALID"); 
  
  public function __construct($debug = '') {
    if($debug) $this->DEBUG = $debug;
    
    // Define doc_root. Via the web this will be the apache document root but
    // CLI it will be BLANK!

    if('cli' === PHP_SAPI) {
      // This is a CLI program and DOCUMENT_ROOT is blank.
      // TOPFILE for a CLI file has the full UNIX path for the siteautoload.class.php file!
      // Make DOC_ROOT be the real path of the TOPFILE directory.
      // NOTE TOPFILE may be the path via the
      // login path which would be /home/<account name>/<path to site>/siteautoload.class.php
      // The realpath would be via /var/www/...

      define('CLI', true);
      define('DOC_ROOT', realpath(TOP));
      // argv[0] has the full path
      $self = $_SERVER['argv'][0];
    } else {
      // This is a WEB program because DOC_ROOT is the apache DOCUMENT_ROOT
      // as reported by PHP.
      define('CLI', false);
      define('DOC_ROOT', $_SERVER['DOCUMENT_ROOT']);
      $self = DOC_ROOT . $_SERVER['PHP_SELF'];
    }

    if($this->DEBUG) echo "DOC_ROOT: ". DOC_ROOT . EOL;
    // Change to the directory where the target file lives.
    // For CLI this will be PHP_SELF which is the full path.
    // For web based this will be the web root, like /kremmling/index.php.
    // So we need to add the doc_root to the PHP_SELF.
    chdir(dirname($self));
    // Get the directory path for our file, that is, the directory that
    // included this file.
    // Use $self and find the absolute path on the server and capture the dirname part.
    $targetDir =  dirname(realpath($self));
    // Now count the number of '/' in the doc root and myDir. This tells us how far our
    // file is down from the doc root. We will search up the tree only that far.
    $a = substr_count(DOC_ROOT, '/');
    $b = substr_count($targetDir, '/');
    // n is the depth down from the root
    $n = $b - $a;
    if($this->DEBUG) echo "Slash count in:".EOL.
        "DOC_ROOT=$a, targetDir=$b, targetDir - DOC_ROOT=$n".
        EOL;
    
    // The file we are looking for in the dir tree
    $sitemapFile2 = ".sitemap.php";
    // BLP 2015-10-13 -- add MySitemap.php
    $sitemapFile = "MySitemap.php";
    
    // BLP 2015-10-13 -- change logic to use both sitemaps
    if($this->DEBUG) echo "sitemapFile: $sitemapFile :: " . realpath($sitemapFile) . "<br>";
    
    if(!($x = $this->findSiteMap($sitemapFile, $n))) {
      $n = $b - $a;
      if($this->DEBUG) echo "sitemapFile: $sitemapFile2 :: " . realpath($sitemapFile2) . "<br>";
      $x = $this->findSiteMap($sitemapFile2, $n);
    }
    // BLP 2015-10-13 -- End of changes
    
    // $x will be something like ../.sitemap.php or as far up as the file .sitemap.php
    // is from the directory where the target file lives.

    // Were we succcessful?
    if($x) {
      // TARGET_ROOT is the home directory of the program. The path to self.
      define('TARGET_ROOT', dirname(realpath($self)));
      // SITE_ROOT is the location of the '.sitemap.php' file 
      define('SITE_ROOT', dirname(realpath($x)));

      if($this->DEBUG) echo "TARGET_ROOT: ".TARGET_ROOT. EOL.
          "SITE_ROOT: ".SITE_ROOT.EOL.EOL;

      // Finally what we have come here for, we require the .sitemap.php file
      // that has the site configuration information.
      require($x);
      // Force $dbinfo and $siteinfo into the global name space!
      $GLOBALS['dbinfo'] = self::$dbInfo = $dbinfo;
      $GLOBALS['siteinfo'] = self::$siteInfo = $siteinfo;

      $this->_siteinfo = $siteinfo;
    } else {
      echo "Failed to Load .sitemap.php".EOL;
      echo "DOC_ROOT: " . DOC_ROOT . EOL;
      echo "b(myDir)=$b".EOL."na(DOC_ROOT)=$a".EOL."n=$n".EOL;
      echo "x=$x".EOL. "realpath of x: " . dirname(realpath($x)) . EOL;
      // Failure
      throw new \Exception("Did not find '.sitemap.php' before " . DOC_ROOT);
    }

    // Grab the helper function as soon as we know where they are. They should always be with the
    // database stuff

    require_once(DATABASE_ENGINES . "/helper-functions.php");

    // Register autoload functon

    spl_autoload_register('SiteAutoLoad\SiteAutoLoad::siteAutoLoad');
  }

  /**
   * getSiteInfo
   */

  static public function getSiteInfo() {
    return self::$siteInfo;
  }

  /**
   * getDbInfo
   */

  static public function getDbInfo() {
    return self::$dbInfo;
  }
  

  /**
   * Find the Site Map file.
   * The cwd is set to $targetDir on entry.
   * This function recurses, changing $sitemapFile to a higher directory
   * and decrementing $n by 1 until either the site map file is found or $n is
   * less than 1.
   * @param: string $sitemapFile. Name of the site map file.
   * @param: integer $n. Number of directory levels to search.
   * @return: string path+name of site map file
   */
  
  private function findSiteMap($sitemapFile, $n) {
    // Does the file exist here?

    if(file_exists($sitemapFile)) {
      return "$sitemapFile";
    } else {
      // NO. Have we searched all the way up to the doc root yet?
      if($n-- < 1) {
        return null;
      }
      // No we have not so hop up a directory level and try again.
      $sitemapFile = "../$sitemapFile";
      // Recurse
      return $this->findSiteMap($sitemapFile, $n);
    }
  }

  /**
   * Set up the Auto Loader.
   * Auto load function for classes ($x = new class;)
   * Look in all the possible locations.
   * If the class file is found require it once.
   * If not found throw exception.
   * @param: string $class. Class name.
   */
  
  private function siteAutoLoad($class) {
    // Are we using namespace? If so the namespace part refers to a directory

    $path = '';

    //echo "start class: $class\n";
    $namespace = '';
    $fileName = '';
    
    if($lastNsPos = strrpos($class, '\\')) {
      //echo "lastNsPos: $lastNsPos\n";
      $namespace = substr($class, 0, $lastNsPos);
      //echo "namespace: $namespace\n";
      
      $class = substr($class, $lastNsPos + 1);
      //echo "Actual ClassName: $class\n";
      $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) .
                   DIRECTORY_SEPARATOR . $class;
    } else {
      $fileName = $class;
    }
    //$class = str_replace('_', DIRECTORY_SEPARATOR, $fileName);
    $class = $fileName;
    if($this->DEBUG) {
      echo "class: $class, namespace: $namespace, fileName: $fileName".EOL;
      echo "Final Class: $class";
    }

    $clLower = strtolower($class);

    // Look at .siteMap.php for definitions of INCLUDES, DATABASE_ENGINES,
    // and SITE_INCLUDES.

    // First look in my cwd/includes (SITE_INCLUDES) then in my cwd.
    // Then start at the very top with INCLUDES, DATABASE_ENGINES
    // Then look for lower case of the class.
    // Finally look in the DOC_ROOT in includes etc for both class and lowercase of class.
    // NOTE: DATABASE_ENGINES have only one location which is usually in
    // INCLUDE/database_engines

    if($this->DEBUG) {
      // SITE_ROOT is the directory where the .sitemap.php file was found.
      // It is set in this file!
      echo EOL."SITE_ROOT: ".SITE_ROOT.EOL;
      // SITE_INCLUDES is set in the .sitemap.php and is usually SITE_ROOT . "/includes"
      // but AGAIN it is user setable in .sitemap.php
      echo "SITE_INCLUDES: ".SITE_INCLUDES.EOL;
      // INCLUDES is set in the .sitemap.php and is usually TOP . "/includes".
      // TOP is also set in .sitemap.php and is usually something like /var/www
      echo "INCLUDES: ".INCLUDES.EOL;
    }

    // We look for our class in the following places:
    // First look in the SITE_INCLUDES directory. This is usually the 'includes' directory
    // under the directory where the '.sitemap.php' file was found. However, remember
    // that this define is user setable in the .sitemap.php file.
    // Second look for the class in the SITE_ROOT (this is where the .sitemap.php file was
    // found). The SITE_ROOT is set in this file.
    // Third look in the non-namespaced database directory. DATABASE_ENGINES is defined
    // in the .sitemap.php file and is usually 'database-engines'.
    // Fourth we do the above with a lowercase version of $class (all three directories).
    // Fifth we look for upper and lower versions of $class in TARGET_ROOT and TARGET_ROOT
    // plus '/includes'. TARGET_ROOT is set in this file and is 'dirname(realpath($self))'
    // which is the directory where the file that required this file is located,
    // either $_SERVER['PHP_PATH'] for apache or $_SERVER['argv'][0] for CLI.
    // Finally we look in the DOC_ROOT and DOC_ROOT plus '/includes' for both upper and
    // lower case versions of $class.
    //
    // Now if the class is namespaced the $class and $clLower have the namespace which
    // is turned into a path. For example, the class 'dbAbstract' which is
    // 'namespace Database;' has the $class of 'Database/dbAbstract'.
    // The $loadMap array has just this root part.
    // The two foreach loops below add the sufix of '.class.php' for the first loop
    // and '.php' for the second loop.
    // The foreach loops look to see if the file, which is the $class plus the sufix,
    // exists in the directory. If it does it is required once.
    // If we traverse both loops without finding the class then an exception is trown.
    //
    // Here is an example filesystem layout:
    // /var/www/
    //   html/                        : the DOCUMENT_ROOT directory
    //     index.php                  : target home page
    //     .sitemap.php               : site map file
    //     includes/                  : this would normally be 'SITE_ROOT . "/includes"'
    //       Blp.class.php            : non-namespace Blp class which extends SiteClass.
    //       Blp/                     : namespaced path
    //         Blp.class.php          : namespaced version
    //   includes/                    : /var/www/includes
    //     SiteClass.class.php        : non-namespaced version 
    //     SiteClass/                 : namespaced path
    //       SiteClass.class.php      : namespaced version
    //     database-engines/          : non-namespaced versions of database engines
    //     Database/                  : namespaced version of database engines
    //
    // I am not showing all of the database php files under their directories.
    //
    // The .sitemap.php does NOT have to be in the same directory as PHP_SELF. It can be
    // in any directory above the PHP_SELF directory up to the DOC_ROOT. So for example
    // we could have this directory layout:
    // /var/www/html/subdir/index.php
    // /var/www/html/.sitemap.php
    // Given is layout TARGET_ROOT would be /var/www/html/subdir and
    // SITE_ROOT would be /var/www/html

    // Paths in comments in parens are actually set in the .sitemap.php as defines.
    
    $loadMap = array(
                     SITE_INCLUDES . "/$class",    // (/var/www/<SITE>/includes/)
                     SITE_ROOT . "/$class",        // /var/www/<SITE>/
                     INCLUDES . "/$class",         // (/var/www/includes/)
                     DATABASE_ENGINES . "/$class", // (/var/www/includes/database-engines/)
                     // Do it all again but lower case
                     SITE_INCLUDES . "/$clLower",
                     SITE_ROOT . "/$clLower",
                     INCLUDES . "/$clLower",
                     DATABASE_ENGINES . "/$clLower",
                     // Following four may be the same as SITE_INCLUDES and SITE_ROOT
                     // if the .sitemap.php file is in the TARGET_ROOT,
                     // but if it is we will never get this far down.
                     TARGET_ROOT . "/includes/$class", 
                     TARGET_ROOT . "/$class",
                     TARGET_ROOT . "/includes/$clLower", 
                     TARGET_ROOT . "/$clLower",
                     // The DOC_ROOT is $_SERVER['DOCUMENT_ROOT'] for apache or
                     // if a CLI then TOP as defined in .sitemap.php
                     DOC_ROOT . "/includes/$class", // finally look in doc root
                     DOC_ROOT . "/$class",
                     DOC_ROOT . "/includes/$clLower", // and then lowercase of class
                     DOC_ROOT . "/$clLower",
                    );

    // First look for class with the sufix '.class.php'
    
    foreach($loadMap as $file) {
      if($this->DEBUG) echo "FILE: $file.class.php".EOL;
      if(file_exists("$file.class.php")) {
        if($this->DEBUG) echo "FOUND $file.class.php".EOL.EOL;
        require_once("$file.class.php");
        return;
      }
    }
    // If not found look for class with just the php sufix
    
    foreach($loadMap as $file) {
      if($this->DEBUG) echo "FILE: $file.php".EOL;
      if(file_exists("$file.php")) {
        if($this->DEBUG) echo "FOUND $file.php".EOL.EOL;
        require_once("$file.php");
        return;
      }
    }
    // Failed miserably!
    throw new \Exception("Class Auto Loader could not fine class $class");
  }
}

// Instantiate the class
if(!isset($AutoLoadDEBUG)) {
  $AutoLoadDEBUG = '';
} else {
  echo "AutoLoadDEBUG: " . ($AutoLoadDEBUG ? "TRUE" : "FALSE") . EOL;
}

new SiteAutoLoad($AutoLoadDEBUG);

return SiteAutoLoad::getSiteInfo();

