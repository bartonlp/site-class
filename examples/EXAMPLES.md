# EXAMPLES
This directory has a number of examples of how to use the framework. I put the 
'vendor/bartonlp/site-class/includes' directory in the /var/www that Apache2 creates when it
is installed on a Ubuntu system. By default the Apache2 install makes /var/www/html its
DocumentRoot (look at /etc/apache2/sites-enabled/000-default.conf the default site 
configuration file).

The following examples are provided:

1. <a href="test1.php">test1.php</a>
2. <a href="test2.php">test2.php</a>
3. <a href="test3.php">test3.php</a>
4. <a href="test4.php">test4.php</a>
5. <a href="test5.php">test5.php</a>

The five above examples either ```require_once``` the SiteClass.class.php
or the siteautoload.class.php via '../vendor/bartonlp/site-class/includes/'.

The next five examples use the composer autoloader:

```require_once('../vendor/autoload.php');```

1. <a href="composer-test1.php">composer-test1.php</a>
2. <a href="composer-test2.php">composer-test2.php</a>
3. <a href="composer-test3.php">composer-test3.php</a>
4. <a href="composer-test4.php">composer-test4.php</a>
5. <a href="composer-test5.php">composer-test5.php</a>

The next two examples show insertion and updating of the database and dbTables useage.

1. <a href="insert-update.php">insert-update.php</a>
2. <a href="composer-insert-update.php">composer-insert-update.php</a>

