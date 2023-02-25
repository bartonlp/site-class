# SiteClass and Database Methods (version 3.6.0 and 2.0.1)

---

## SiteClass methods:

While there are a number of methods for each of the major classes there are really only a small handful you will use on a regular bases. 
The ones most used have some documentation with them.

* constructor
* public function setSiteCookie(string $cookie, string $value, int $expire, string $path="/", ?string $thedomain=null,  
?bool $secure=null, ?bool $httponly=null, ?string $samesite=null):bool
* public function getIp():string
* public function getPageTopBottom(?object $h=null, ?object $b=null):array   
This is the most used method. It takes one or two arguments which can be object or null (defaults to null).  
\$h can have 'title', 'desc', 'banner' and many other less used options.  
\$b is for the footer or bottom. You can pass 'msg', 'msg1', 'msg2' or 'cntmsg' (see the code).  
I usually put things into the 'footerFile' but on occasions a page needs something extra.  
This method calls getPageHead(), getPageBanner(), getPageFooter().  
You can't pass $h or $b to these function. Instead you should set the values in $S.  
The two values inlineScript and script values for $h and $b should be set as $S->h_inlineScript.  
Add an h_ or b_ prefix to the inlineScript or script properties.
* public function getPageTop():string  
* public function getPageHead():string  
* public function getPageBanner():string  
* public function getPageFooter():string  
* public function getDoctype():string  
* public function \__toString():string  
* There are a number of 'protected' methods and properties that can be used in a child class.

## Database methods:

* constructor
* public function getDb():object. Get the database object.
* public function setDb($db). Set the database object.
* public function query(\$query)  
This is the workhourse of the database. It is used for 'select', 'update', 'insert' and basically anything you need to do like 'drop', 'alter' etc.
$query is the sql statement.
* public function fetchrow($result=null, $type="both")  
Probably the second most used method.
If it follows the 'query' the $result is not needed.
The only time $result is needed is if there are other queries in a while loop.
In that case you need to get the result of the query by calling the getResult() method before running the while loop.  
The $type can be 'assoc', 'num' or default 'both'. 'assoc' returns only an associative array, while 'num' return a numeric array. I usually use a numeric array with

```php
while([$name, $email] = $S->fetchrow('num')) { ... }
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

[dbTables](dbTables.html)  
[SiteClass Methods](siteclass.html)  
[Additional Files](files.html)  
[Analysis and Tracking](analysis.html)  
[Index](index.html)

## Contact Me

Barton Phillips : <a href="mailto://bartonphillips@gmail.com">bartonphillips@gmail.com</a>
Copyright &copy; 2023 Barton Phillips  
Project maintained by [bartonlp](https://github.com/bartonlp)  
Last modified February 21, 2023
