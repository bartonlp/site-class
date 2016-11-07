<?php
// Test the site-class with Mysqli
// Run tests with: phpunit --stderr withMysql.php

require_once(getenv("HOME") ."/vendor/autoload.php");

use PHPUnit\Framework\TestCase;

class WithMysql extends TestCase { //PHPUnit_Extensions_Database_TestCase {
  private $s = array(
                     'siteDomain' => "localhost",
                     'siteName' => "Test",
                     'copyright' => "2015 Barton L. Phillips",
                     'memberTable' => "members",
                     'headFile'=>"./includes/head.i.php",
//                     'footerFile'=>"./includes/footer.i.php",
                     'noTrack' => true,
                     'count' => false,
                     'dbinfo' => array(
                                       "host"=>"localhost",
                                       "user"=>"siteclass",
                                       "password"=>"siteclass",
                                       "database"=>"siteclass",
                                       "engine"=>"mysqli"
                                      )
                    );
                    
  protected $S;

  protected function setUp() {
    ErrorClass::setNoHtml(true);
    $this->s = arraytoobjectdeep($this->s);
    $this->S = new SiteClass($this->s);
  }

  public function testSetUp() {
    $S = $this->S;
    $S = new SiteClass($this->s);

    $this->assertTrue(!is_null($S));
    $this->assertTrue(!is_null($S->getDb()));
    $this->assertEquals($S->getDbName(), "siteclass");
    $this->assertEquals($S->copyright, $this->s->copyright);
    $this->assertEquals($S->doctype, '<!DOCTYPE html>');
    $this->assertEquals($S->siteName, 'Test');
    $this->assertEquals($S->dbinfo->password, 'siteclass');
    $S->query("drop database if exists siteclass");
    $S->query("create database siteclass");
    $S->query("show databases like 'siteclass'");
    list($db) = $S->fetchrow('num');
    $this->assertEquals($db, 'siteclass', 'Database Not Found');
  }

  public function testDropCreate() {
    $S = $this->S;
    $n = $S->query("drop table if exists members");
    $this->assertTrue($n == 0);
    $n = $S->query("create table members (rowid int(11) auto_increment, ".
                   "fname text, lname text, email text, visits int(11), ".
                   "visittime datetime, primary key(rowid)) engine=MyISAM");
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
    $n = $S->query("select fname from members");
    $this->assertTrue($n == 3);
    $l = array('NEW', 'Ingrid', 'Mike');
    for($i=0; $row = $S->fetchrow(); ++$i) {
      $this->assertEquals($row[0], $l[$i]);
    }
  }

  public function testHeadFile() {
    $S = $this->S;

    $h = <<<EOF
<!DOCTYPE html>
<html lang="en" >
This is the header via a return!

EOF;

    $head = $S->getPageHead();

    $this->assertEquals($head, $h);
  }

/*
  public function testErrors() {
    echo "\ntestErrors\n";
    $S = $this->S;
    try {
      $S->query("select test from members");
    } catch(Exception $e) {}

    echo "***********************\n";
    var_dump($S->getErrorInfo());
    echo "sqlState: " . $S->getSqlState() . "\n";
    //$row = $S->fetchrow();
  }
*/
}
