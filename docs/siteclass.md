# SiteClass (version 5.0.4pdo), Database and dbPdo Methods

---

## SiteClass methods:

While there are a number of methods for each of the major classes there are really only a small handful you will use on a regular bases. 
The ones most used have some documentation with them.

* constructor
* public function setSiteCookie(string $cookie, string $value, int $expire, string $path="/", ?string $thedomain=null,  
?bool $secure=null, ?bool $httponly=null, ?string $samesite=null):bool
* public function getIp():string
* public function getPageTopBottom():array

I usually have this code at the top of my page:
```php
$_site = require_once(getenv("SITELOADNAME"));
$S = new SiteClass($_site);
// Set the properties of $S
$S->title = "test page";
$S->banner = "<h1>$S->title</h1>";
$S->css = "h1 { color: red; }";
$S->b_inlineScript = "console.log('this is a test')";
[$top, $footer] = $S->getPageTopBottom();
echo <<<EOF
$top
<p>Some test stuff</p>
$footer
EOF;
```

getPageTopBottom() calls getPageHead(), getPageBanner(), getPageFooter().  

* public function getPageTop():string  
* public function getPageHead():string  
* public function getPageBanner():string  
* public function getPageFooter():string  
* public function getDoctype():string  
* public function \__toString():string  
* There are a number of 'protected' methods and properties that can be used in a child class.

## Database and dbPdo methods:

* constructor
* public function getDb():object. Get the database object.
* public function setDb($db). Set the database object.
* public function query($sql)  
This is the workhourse of the database. It is used for 'select', 'update', 'insert' and basically anything you need to do like 'drop', 'alter' etc.
*$sql* is the sql statement.
* public function fetchrow($result=null, $type="both")  
Probably the second most used method.
If it follows the 'query' the $result is not needed.
The only time $result is needed is if there are other queries in a while loop.
In that case you need to get the result of the query by calling the getResult() method before running the while loop.  
The $type can be 'assoc', 'num' or default 'both'. 'assoc' returns only an associative array, while 'num' return a numeric array.   
I usually use a numeric array with

```php
while([$name, $email] = $S->fetchrow('num')) { ... }
```

* public function queryfetch($query, $retarray=false)
* public function getLastInsertId()  
After an 'insert' this method returns the new row's primary key id.
* public function getResult()  
Returns the result object from the last 'query'. Usually not needed.
* public function escape($string)
* public function getNumRows($result=null)
* public function getErrorInfo()

---

[dbTables](dbTables.html)  
[Additional Files](files.html)  
[Index](index.html)

## Contact Me

Barton Phillips : <a href="mailto://bartonphillips@gmail.com">bartonphillips@gmail.com</a>
Copyright &copy; 2025 Barton Phillips  
Project maintained by [bartonlp](https://github.com/bartonlp)  
Last modified January 1, 2025
