# Unit Tests

These test use phpunit.
From this directory enter: 
```bash
HOME=<path_to_autoload.php> phpunit --stderr <filename>;
```
Where 'path_to_autoload.php' is the path to the location of 'vendor/autoload.php',
and 'filename' is one of the three file that follow:

* topBottom.php
* withSqlite3.php
* withMysql.php

## MySql

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

## Sqlite3

There is already a Sqlite3 database. It is called just 'siteclass'. You can run the 'withSqlite3.php' 
with:
```bash
HOME=/var/www phpunit --stderr withSqlite3.php
```

## Top Bottom

The final test 'topBottom.php' does not need a database and only test the 'getPageHead', 'getPageFooter'
and 'getPageTopBottom' methods and the 'includes/' files.
```bash
HOME=/var/www phpunit --stderr topBottom.php
```

Feel free to extend the test if you like.
