<?php
// Test the site-class with PostgresSql

require_once("../includes/SiteClass.class.php");
require_once("/home/barton/vendor/autoload.php"); // PHPUnit

class WithPostgres extends PHPUnit_Framework_TestCase {
  private $s = array(
                     'siteDomain' => "localhost",
                     'siteName' => "Test",
                     'copyright' => "2015 Barton L. Phillips",
                     'memberTable' => "members",
                     'headFile'=>"./includes/head.i.php",                     
                     'dbinfo' => array(
                                       "host"=>"localhost",
                                       "user"=>"siteclass",
                                       "password"=>"siteclass",
                                       "database"=>"siteclass",
                                       "engine"=>"pgsql"
                                      )
                    );
  protected $S;
  
  protected function setUp() {
    Error::setNoHtml(true);
    $this->S = new SiteClass($this->s);
  }

  public function testSetUp() {
    $S = $this->S;
    $this->assertTrue(!is_null($S));
    $this->assertTrue(!is_null($S->getDb()));
    $this->assertEquals($S->getDb()->__toString(), "dbPostgreSql");
    $this->assertTrue(!is_null($S->getEngineDb()));
    $this->assertEquals($S->copyright, $this->s['copyright']);
    $this->assertEquals($S->doctype, '<!DOCTYPE html>');
    $this->assertEquals($S->siteName, 'Test');
    $this->assertEquals($S->dbinfo['password'], 'siteclass');
  }
  
  public function testDropCreate() {
    $S = $this->S;
    $n = $S->query("drop table if exists members");
    $this->assertTrue($n == 0);
    $n = $S->query("create table members (rowid serial primary key,".
                   "fname text, lname text, visits integer, visittime integer)");
    $this->assertTrue($n == 0);
  }

  public function testInsert() {
    $S = $this->S;
    $n = $S->query("insert into members (fname, lname) values".
                   "('Barton','Phillips'),".
                   "('Ingrid','Phillips'),".
                   "('Mike','Phillips')");
    $this->assertTrue($n == 3, "testInsert n=$n");
    
    $n = $S->query("select count(*) from members");
    $this->assertTrue($n == 1, "testInsert select n=$n");
    list($cnt) = $S->fetchrow('num');
    $this->assertTrue($cnt == 3, "testInsert select cnt=$cnt");
  }

  public function testSelect() {
    $S = $this->S;
    $n = $S->query("select fname, lname from members where rowid=1");
    $this->assertTrue($n == 1, "testSelect n=$n");

    list($fname, $lname) = $S->fetchrow('num');
    
    $this->assertEquals($fname, 'Barton', "testSelect fname=$fname");
    $this->assertEquals($lname, 'Phillips', "testSelect lname=$lname");
  }

  public function testUpdate() {
    $S = $this->S;
    $n = $S->query("update members set fname='NEW' where rowid=1");
    $this->assertTrue($n == 1);
  }

  public function testUpdated() {
    $S = $this->S;
    $n = $S->query("select fname from members order by rowid");
    $this->assertTrue($n == 3);
    $l = array('NEW', 'Ingrid', 'Mike');
    for($i=0; $row = $S->fetchrow(); ++$i) {
      $this->assertEquals($row[0], $l[$i]);
    }
  }

  public function testHeadFile() {
    $S = $this->S;
    $head = $S->getPageHead();
    $h = <<<EOF
<!DOCTYPE html>
<html lang="en" >
This is the header via a return.

EOF;

    $t = <<<EOF
<!DOCTYPE html>
<html lang="en" >
This is the header via a return.

<body>
<!-- Default Header/Banner -->
<header>
<div id='pagetitle'>

</div>
<noscript style="color: red; border: 1px solid black; padding: 10px; font-size: large;">
<strong>Your browser either does not support JavaScripts
or you have JavaScripts disabled.</strong>
</noscript>
</header>


EOF;

    $f = <<<EOF
<!-- Default Footer -->
<footer><div style="text-align: center;">
<p id='lastmodified'>Last Modified&nbsp;Apr 10, 2015 22:24:35</p>
<p id='contactUs'><a href='mailto:webmaster@localhost'>Contact Us</a></p>
</div>
</footer>
</body>
</html>

EOF;
    $this->assertEquals($head, $h);

    list($top, $footer) = $S->getPageTopBottom();
 
    $this->assertEquals($top, $t, "top failed");
    $this->assertEquals($footer, $f, "footer failed");
  }

/*  
  public function testErrors() {
    echo "\ntestErrors\n";
    $S = $this->S;
    try {
      $S->query("select test from members");
    } catch(Exception $e) {
      echo "getMessage: " . $e->getMessage() . "\ngetCode: ". $e->getCode() . "\n\n";
    }

    echo "***********************\n";
    var_dump($S->getErrorInfo());
    //$row = $S->fetchrow();
  }
*/  
}
