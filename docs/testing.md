# Testing

---

## phpunit

You will need to insure that 'phpunit' is installed. 'phpunit' is on [GitHub](https://github.com/sebastianbergmann/phpunit) checkout the README for instalation instruction.

Once you have 'phpunit' installed you can run the following test:

* topBottom.php
* withSqlite3.php
* withMysql.php

From 'tests' directory enter: 

```bash
HOME=<path_to_autoload.php> phpunit --stderr <filename>;
```
Where 'path_to_autoload.php' is the path to the location of 'vendor/autoload.php',
and 'filename' is one of the three file that follow:

## Using MySql

To use the 'withMysql.php' test you will need to setup the MySql database. First you will need to create a 
database 'siteclass'. Then you will need to grand priveleges.

```sql
mysql> create database siteclass;
Query OK, 1 row affected (0.00 sec)

mysql> create user siteclass@localhost identified by 'siteclass';
Query OK, 0 rows affected (0.04 sec)

mysql> grant all on siteclass.* to siteclass@localhost;
Query OK, 0 rows affected (0.01 sec)

mysql> show grants for siteclass@localhost;
+------------------------------------------------------------------+
| Grants for siteclass@localhost                                   |
+------------------------------------------------------------------+
| GRANT USAGE ON *.* TO 'siteclass'@'localhost'                    |
| GRANT ALL PRIVILEGES ON `siteclass`.* TO 'siteclass'@'localhost' |
+------------------------------------------------------------------+
2 rows in set (0.00 sec)

mysql> exit
```

Now you can run the 'withMysql.php' with:

```bash
HOME=/var/www phpunit --stderr withMysql.php
```

## Using Sqlite3

There is already a Sqlite3 database in the 'tests' directory. It is called just 'siteclass'. You can run the 'withSqlite3.php' 
with:

```bash
HOME=/var/www phpunit --stderr withSqlite3.php
```

## Top Bottom Test

The final test 'topBottom.php' does not need a database and only test the 'getPageHead', 'getPageFooter'
and 'getPageTopBottom' methods and the 'includes/' files.

```bash
HOME=/var/www phpunit --stderr topBottom.php
```

Feel free to extend the test if you like.

---

[Examples](examples.html)
[dbTables](dbTables.html)
[SiteClass Methods](siteclass.html)
[Additional Files](files.html)
[Analysis and Tracking](analysis.html)
[Testing](testing.html)
[Index](index.html)

## Contact Me

Barton Phillips : [bartonphillips@gmail.com](mailto://bartonphillips@gmail.com)  
Copyright &copy; 2017 Barton Phillips  
Project maintained by [bartonlp](https://github.com/bartonlp)

