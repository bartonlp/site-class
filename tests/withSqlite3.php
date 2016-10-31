<?php
// Test the site-class with SQLite3
// Run tests with: phpunit --stderr withSqlite3.php

require_once(getenv("HOME"). "/vendor/autoload.php"); // PHPUnit

use PHPUnit\Framework\TestCase;

class WithSqlite extends TestCase {
  private $s = array(
                     'siteDomain' => "localhost",
                     'siteName' => "Test",
                     'copyright' => "2015 Barton L. Phillips",
                     'memberTable' => "members",
                     'headFile'=>"./includes/head.i.php",
                     'dbinfo' =>   array(
                                         "host"=>"localhost",
                                         "user"=>"siteclass",
                                         "password"=>"siteclass",
                                         "database"=>"siteclass",
                                         "engine"=>"sqlite3"
                                        ),
                     'count' => false,
                     'noTrack' => true
                    );
  protected $S;
  
  protected function setUp() {
    ErrorClass::setNoHtml(true);
    $this->S = new SiteClass($this->s);
  }

  public function testSetUp() {
    $S = $this->S;
    $this->assertTrue(!is_null($S));
    $this->assertTrue(!is_null($S->getDb()));
    $this->assertEquals($S->getDb()->__toString(), "dbSqlite");
    $this->assertEquals($S->copyright, $this->s['copyright']);
    $this->assertEquals($S->doctype, '<!DOCTYPE html>');
    $this->assertEquals($S->siteName, 'Test');
    $this->assertEquals($S->dbinfo['password'], 'siteclass');
  }
  
  public function testDropCreate() {
    $S = $this->S;
    $n = $S->query("drop table if exists members");
    $this->assertTrue($n == 0);
    $n = $S->query("create table members (fname text, lname text, ".
                   "visits integer, visittime datetime)");
    $this->assertTrue($n == 0);
  }

  public function testInsert() {
    $S = $this->S;
    $S->query("insert into members (fname, lname) values".
                   "('Barton','Phillips'),".
                   "('Ingrid','Phillips'),".
                   "('Mike','Phillips')");

    $S->query("select count(*) from members");
    list($cnt) = $S->fetchrow('num');
    $this->assertTrue($cnt == 3, "testInsert select cnt=$cnt");
  }

  public function testSelect() {
    $S = $this->S;
    $S->query("select fname, lname from members where rowid=1");

    list($fname, $lname) = $S->fetchrow('num');
    $this->assertEquals($fname, 'Barton', "testSelect fname=$fname");
    $this->assertEquals($lname, 'Phillips', "testSelect lname=$lname");
  }

  public function testUpdate() {
    $S = $this->S;
    $S->query("update members set fname='NEW' where rowid=1");
    $S->query("select fname from members where rowid=1");
    list($fname) = $S->fetchrow('num');
    $this->assertEquals($fname, 'NEW', "testUpdate fname=$fname");
  }

  public function testUpdated() {
    $S = $this->S;
    $S->query("select fname from members");
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

    $this->assertEquals($head, $h, "head failed");
  }

/*
  public function testErrors() {
    echo "\ntestErrors\n";
    $S = $this->S;
    try {
      $S->query("select test from members");
    } catch(Exception $e) {
      echo "CATCH: " . $e->getCode() ."\n".$e->getMessage() ."\n";
    }

    echo "***********************\n";
    var_dump($S->getErrorInfo());
    //$S->getSqlState();
    //$row = $S->fetchrow();
  }
*/  
}
