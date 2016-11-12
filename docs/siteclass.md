# SiteClass and Database Methods

---

## SiteClass methods:

While there are a number of methods for each of the major classes there are really only a small handful you will use on a regular bases. The ones most used have some documentation with them.

* constructor
* public function setSiteCookie($cookie, $value, $expire, $path="/")
* public function getIp()
* public function getPageTopBottom($h, $b=null)  
This is the most used method. It takes one or two arguments which can be string|array|object.  
$h can have 'title', 'desc', 'banner' and a couple of other less used options.  
$b is for the footer or bottom. I sometimes pass a &lt;hr&gt; but you can also pass a 'msg', 'msg1', 'msg2' (see the code). I usually put things into the 'footerFile' but on occasions a page needs something extra.  
This method calls getPageHead(), getPageBanner(), getPageFooter().
* public function getPageTop($header, $banner=null, $bodytag=null)
* public function getPageHead(/* mixed */)
* public function getPageBanner($mainTitle, $nonav=false, $bodytag=null)
* public function getPageFooter(/* mixed */)
* public function getDoctype()
* public function \__toString()
* A number of 'protected' methods and properties that can be used in a child class.

## Database methods:

The database methods are implemented for all supported engines. There are some minor behavioral differences in the syntax the engine queries uses or the return values. For example sqlite3 does not support a number of rows functionality and there are also several syntactial differences between sqlite and mysql (caviat emptor).

* constructor
* public function getDb(). Get the database object.
* public function setDb($db). Set the database object.
* public function query($query)  
This is the workhourse of the database. It is used for 'select', 'update', 'insert' and basically anything you need to do like 'drop', 'alter' etc. $query is the sql statement.
* public function fetchrow($result=null, $type="both")  
Probably the second most used method. If it follows the 'query' the $result is not needed. The only time $result is needed is if there are other queries in a while loop. In that case you need to get the result of the query by calling the getResult() method before running the while loop.  
The $type can be 'assoc', 'num' or default 'both'. 'assoc' returns only an associative array, while 'num' return a numeric array. I usually use a numeric array with

```php
while(list($name, $email) = $S->fetchrow('num')) { ... }
```

* public function queryfetch($query, $retarray=false)
* public function getLastInsertId()  
After an 'insert' this method returns the new row's primary key id.
* public function getResult()  
Returns the result object from the last 'query'. Usually not needed.
* public function escape($string)
* public function escapeDeep($value)
* public function getNumRows($result=null)
* public function prepare($query)  
I hardly ever use prepare(), bindParam(), bindResults() or execute() so they are not as well tested as the other methods.
* public function bindParam($format)
* public function bindResults($format)
* public function execute()
* public function getErrorInfo()

---

[Examples](examples.html)
[dbTables](dbTables.html)
[SiteClass Methods](siteclass.html)
[Additional Files](files.html)
[Analysis and Tracking](analysis.html)
[Testing](testing.html)
[Index](index.html)

## Contact Me

Barton Phillips : <a href="mailto://bartonphillips@gmail.com">bartonphillips@gmail.com</a>
Copyright &copy; 2015 Barton Phillips
