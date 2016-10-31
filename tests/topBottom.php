<?php
// Test the other items of SiteClass
// Because mysqli is probably the most important database and the most consitent I
// am using it in the test.
// Run tests with: phpunit --stderr topBottom.php

require_once(getenv("HOME") ."/vendor/autoload.php"); 

use PHPUnit\Framework\TestCase;

class topBottom extends TestCase {
  // These first three need to be static so they are not reset each time we enter the
  // test via phpunit.
  
//  private static $init = false;
//  private static $cnt = 0;
//  private static $SS;

  // This is initialized each time so it is OK
  
  private $s = array(
                     'siteDomain'=>"localhost",
                     'siteName'=>"Test",
                     'copyright'=>"2015 Barton L. Phillips",
                     'headFile'=>"includes/head.i.php",
                     'footerFile'=>"includes/footer.i.php",
                     'bannerFile'=>"includes/banner.i.php",
                     'noTrack' =>true,
                    );

  protected $S;

  // Set Up the fixture.

  
  protected function setUp() {
    $this->S = new SiteClass($this->s);
  }
    
  /**
   * Check the test setup
   */

  public function testSetUp() {
    $S = $this->S;
    $this->assertTrue(!is_null($S), "\$S is not set");
    $this->assertEquals($S->copyright, $this->s['copyright']);
    $this->assertEquals($S->doctype, '<!DOCTYPE html>');
    $this->assertEquals($S->siteName, $this->s['siteName']);
    $this->assertEquals($S->headFile, $this->s['headFile']);
    $this->assertEquals($S->bannerFile, $this->s['bannerFile']);
    $this->assertEquals($S->footerFile, $this->s['footerFile']);
  }

  /**
   * Check the getPageHead(), getPageBanner(), getPageFooter and getPageTopBottom
   * functions.
   */

  public function testHead() {
    $S = $this->S;
    $h =<<<EOF
<!DOCTYPE html>
<html lang="en" >
This is the header via a return!

EOF;
    $head = $S->getPageHead();
    $this->assertEquals($head, $h);
  }

  // Banner

  public function testBanner() {
    $S = $this->S;
    $b =<<<EOF
<body>
This is the banner via a return!

EOF;
    $banner = $S->getPageBanner();
    $this->assertEquals($banner, $b);    
  }

  // Footer

  public function testFooter() {
    $S = $this->S;
    $f = <<<EOF
This is the footer via a return!
EOF;

    $footer = $S->getPageFooter();
    $this->assertEquals($footer, $f);
  }

  // Top Bottom

  public function testTopBottom() {
    $S = $this->S;
    $t = <<<EOF
<!DOCTYPE html>
<html lang="en" >


<body>
This is the banner via a return!

EOF;

    $b = <<<EOF
This is the footer via a return!
EOF;
    list($top, $footer) = $S->getPageTopBottom();
    
    $this->assertEquals($top, $t);    
    $this->assertEquals($footer, $b);    
  }
}
