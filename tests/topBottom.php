<?php
// Test the other items of SiteClass
// Because mysqli is probably the most important database and the most consitent I
// am using it in the test.
// Run tests with: phpunit --stderr topBottom.php

require_once("../includes/SiteClass.class.php");
require_once("/home/barton/vendor/autoload.php"); // PHPUnit

class topBottom extends PHPUnit_Framework_TestCase {
  // These first three need to be static so they are not reset each time we enter the
  // test via phpunit.
  
  private static $init = false;
  private static $cnt = 0;
  private static $SS;

  // This is initialized each time so it is OK
  
  private $s = array(
                     'siteDomain'=>"localhost",
                     'siteName'=>"Test",
                     'copyright'=>"2015 Barton L. Phillips",
                     'memberTable'=>"members",
                     'headFile'=>"includes/head.i.php",
                     'footerFile'=>"includes/footer.i.php",
                     'bannerFile'=>"includes/banner.i.php",
                     'myUri'=>"bartonphillips.com",
                     'daycountwhat'=>"all",
                     //'count'=>true, // NOT initially set
                     'dbinfo'=>array(
                                     "host"=>"localhost",
                                     "user"=>"siteclass",
                                     "password"=>"siteclass",
                                     "database"=>"siteclass",
                                     "engine"=>"mysqli"
                                    )
                    );
  // This is also initialized each time from the $SS static.
  
  private $S;

  // Set Up the fixture.
  
  protected function setUp() {
    if(!self::$init) {
      // We only want to do the initialization ONCE.
      // setUp() gets called for every test and would reinitate every time.
      
      Error::setNoHtml(true);
      self::$init = true;
      self::$SS = $S = new SiteClass($this->s);

      // Setup the counter tables.
      $S->query("drop table if exists counter");
      $S->query("drop table if exists logagent");
      $S->query("drop table if exists logip");
      $S->query("drop table if exists daycounts");
      $S->query("drop table if exists memberpagecnt");
      $S->query("drop table if exists members");
      
      $sql =<<<EOF
CREATE TABLE `counter` (
  `filename` varchar(255) NOT NULL,
  `count` int(11) DEFAULT NULL,
  `lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`filename`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
EOF;

      $S->query($sql);

      $sql =<<<EOF
CREATE TABLE `daycounts` (
  `date` date NOT NULL,
  `ip` varchar(20) NOT NULL,
  `id` int(11) DEFAULT NULL,
  `count` int(11) DEFAULT NULL,
  `visits` int(11) DEFAULT NULL,
  `lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`date`,`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
EOF;

      $S->query($sql);

      $sql =<<<EOF
CREATE TABLE `logagent` (
  `ip` varchar(25) NOT NULL,
  `agent` varchar(255) NOT NULL,
  `count` int(11) DEFAULT NULL,
  `id` int(11) DEFAULT NULL,
  `lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ip`,`agent`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=1
EOF;

      $S->query($sql);

      $sql =<<<EOF
CREATE TABLE `logip` (
  `ip` varchar(255) NOT NULL,
  `id` int(11) DEFAULT NULL,
  `count` int(11) DEFAULT NULL,
  `lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=1
EOF;
      $S->query($sql);

      $sql =<<<EOF
CREATE TABLE `memberpagecnt` (
  `page` varchar(255) NOT NULL,
  `id` int(11) NOT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `agent` varchar(255) DEFAULT NULL,
  `count` int(11) DEFAULT NULL,
  `lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`page`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=1
EOF;

      $S->query($sql);

      $sql =<<<EOF
Create table members(
  id int(11) auto_increment,
  fname text,
  lname text,
  email text,
  visits int(11) default 0,
  visittime datetime,
  primary key(id)
) engine=MyISAM
EOF;
      $S->query($sql);

      $sql =<<<EOF
insert into members (fname, lname, visits, visittime) values
  ('Barton', 'Phillips', 5, now()),
  ('Ingrid', 'Phillips', 1, now()),
  ('Mike', 'Phillips', 0, null)
EOF;
      $S->query($sql);

      // When we have finished the initalization we unset everything and
      // start abain with 'count' and 'countMe' set true.

      unset($S);
      unset($this->S);
      $this->s['count'] = true;
      $this->s['countMe'] = true;
      $this->S = $SS = $S = new SiteClass($this->s);
    }
    // Get the S from the static

    $this->S = self::$SS;
  }

  /**
   * Check the test setup
   */

  public function testSetUp() {
    $S = $this->S;
    //echo "\ntestSetUp: cnt=".self::$cnt."\n";
    $this->assertTrue(!is_null($S));
    $this->assertTrue(!is_null($S->getDb()));
    $this->assertTrue(!is_null($S->getEngineDb()));
    $this->assertEquals($S->copyright, $this->s['copyright']);
    $this->assertEquals($S->doctype, '<!DOCTYPE html>');
    $this->assertEquals($S->siteName, 'Test');
    $this->assertEquals($S->dbinfo['password'], 'siteclass');
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
This is the header via a return.

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
This is the footer via the pageFooterText variable
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
This is the header via a return.

<body>
This is the banner via a return!

EOF;
    $b = <<<EOF
This is the footer via the pageFooterText variable
EOF;
    list($top, $footer) = $S->getPageTopBottom();
    $this->assertEquals($top, $t);    
    $this->assertEquals($footer, $b);    
  }

  /**
   * Check the cookies etc.
   */

  public function testCookies() {
    $S = $this->S;
    // Set the ip to that of 'bartonphillips.com'
    $S->ip = $S->myIp;
    //echo "\nip: $S->ip\n";
    // setSiteCookie()
    try {
      // We can't set cookie because we have already output something
      $S->setSiteCookie("TESTofThis", "ValueOfThis");
    } catch(Exception $e) {
      echo "\nsetSiteCookie failed: " . $e->getMessage() . "\n";
      echo "Run PHPUnit with the --stderr flag to be able to set cookies.";
    }
    // setIdCookie()
    try {
      $S->setIdCookie(1, 'testcookie');
    } catch(Exception $e) {
      echo "\nsetIdCookie failed: " .$e->getMessage() . "\n";
      echo "Run PHPUnit with the --stderr flag to be able to set cookies.";
    }

    $this->assertEquals($S->checkId(null, 'testcookie'), 1);
    //echo "\ncheckId 1: " .$S->checkId() . "\n";
    try {
      $S->setIdCookie(2000, 'testcookie');
    } catch(Exception $e) {
      echo "\nsetIdCookie failed: " .$e->getMessage() . "\n";
      echo "Run PHPUnit with the --stderr flag to be able to set cookies.";
    }

    $this->assertEquals($S->checkId(), 2000);
    //echo "\ncheckId 2: " .$S->checkId() . "\n";
    // getId()
    $this->assertEquals($S->getId(), 2000);
    //echo "\ngetId: " . $S->getId() . "\n";
    // setId()
    $S->setId(1000);
    // checkId()
    $this->assertEquals($S->checkId(), 1000);
    //echo "\ncheckId 3: " .$S->checkId() . "\n";
    // getIp()
    $this->assertEquals($S->getIp(), '104.236.180.77'); //'107.170.244.155');
    //echo "\ngetIp: " . $S->getIp() . "\n";
  }

  /**
   * Check that the counter files are working.
   */

  public function testCounters() {
    $S = $this->S;
    // Look at each counter

    $tests = array("members"=>array('n'=>3, array(
                                                  array('fname'=>'Barton', 'visits'=>5),
                                                  array('fname'=>'Ingrid', 'visits'=>1),
                                                  array('fname'=>'Mike', 'visits'=>0)
                                                 )
                                   ),
                   "counter"=>array('n'=>1,
                                    'filename'=>
                                    '/home/barton/myClasses/site-class/tests/bootstrap.php',
                                    'count'=>1),
                   "daycounts"=>array('n'=>1, array(
                                                    'ip'=>'107.170.244.155',
                                                    'visits'=>1
                                                   )
                                     ),
                   "logagent",
                   "logip",
                   "memberpagecnt"=>array('n'=>0)
                  );

    foreach($tests as $k=>$v) {
      $ar = '';

      if(is_numeric($k)) {
        $cnt = 1;
        $tbl = $v;
      } else {
        $cnt = $v['n'];
        $tbl = $k;
        $ar = $v[0];
        if(is_null($ar)) {
          $ar = array_slice($v, 1);
        }
      }

      //echo "\ntable: $tbl\n";
      $n = $S->query("select * from $tbl");

      //echo "\nn=$n, cnt=$cnt\n";
      $this->assertTrue($n == $cnt, "Error: $v, n=$n");

      if(!$ar) {
        //echo "$n, $cnt\n";
        continue;
      }

      for($i=0; $row = $S->fetchrow('assoc'); ++$i) {
        if(isset($ar[$i])) {
          foreach($ar[$i] as $k=>$v) {
            //echo "$v ? $row[$k]\n";
            $this->assertEquals($v, $row[$k]);
          }
        } else {
          foreach($ar as $k=>$v) {
            //echo "$k=$v row=$row[$k]\n";
            //$this->assertEquals($v, $row[$k]);
          }
        }
      }
    }
  }
}
