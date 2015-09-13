# EXAMPLES

This directory has a number of examples of how to use the framework. I put the 'vendor/bartonlp/site-class/includes' directory in the /var/www that Apache2 creates when it is installed on a Ubuntu system. By default the Apache2 install makes '/var/www/html' its DocumentRoot (look at /'etc/apache2/sites-enabled/000-default.conf' the default site configuration file).

The following examples are provided.

The first examples either 'require_once' the 'SiteClass.class.php' or the 'siteautoload.class.php' via '../vendor/bartonlp/site-class/includes/'.

1. <a href="test1.php">test1.php</a> Simplest. No database.
2. <a href="test2.php">test2.php</a> Uses sqlite database.
3. <a href="test3.php">test3.php</a> Uses Database class directly.
4. <a href="test4.php">test4.php</a> Uses siteautoload.class.php
5. <a href="test5.php">test5.php</a> Uses siteautoload.class.php and dbTables class.

The next five examples are the same as above but use the composer autoloader:

1. <a href="composer-test1.php">composer-test1.php</a>
2. <a href="composer-test2.php">composer-test2.php</a>
3. <a href="composer-test3.php">composer-test3.php</a>
4. <a href="composer-test4.php">composer-test4.php</a>
5. <a href="composer-test5.php">composer-test5.php</a>

The next two examples show insertion and updating of the database and dbTables useage.

1. <a href="insert-update.php">insert-update.php</a>
2. <a href="composer-insert-update.php">composer-insert-update.php</a>

## Using Other Libraries

### Twig

You can use other frameworks or templeting engines. Here we will use Twig a popular templet engine. Twig is a super powerful templet engine with looping and conditional statements and much more. Here we do just about the minimum just as an example.

To use this example you need to install Twig in the 'examples' directory as it is NOT part of this package by default.

```
composer require twig/twig:~1.0
```

<a href="composer-with-twig.php">composer-with-twig.php</a>

### ReST Routing with Altorouter

If you need ReST routing (or pretty routing or SEO friendly routing as it is sometime called) you could use one of the popular routing engines available with **Meteor**, **Laravel**, **Synfony2** or **Silex** but then again by that point you might as well just bite the bullet and spend the hours or days trying to figure out those frameworks.

There is a pretty simple router called **Altorouter** which can be used without too much work. To install **Altorouter** do 

``` 
composer require altorouter/altorouter:1.1.0 
``` 

in the 'examples' directory.

The file 'composer-route.php' would normally be your 'index.php' in a production environment.  When using a server with Apache2 you would need a '.htaccess' file in the directory where the 'index.php' lives. The '.htaccess' file would look like this:

```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule . index.php [L]
```

This says that if the requested filename does not exist go to the 'index.php' file instead.

To test this without renaming the 'composer-route.php' you can use the PHP server like this:

```
php -S localhost:8080 composer-route.php
```

The PHP server uses the 'composer-route.php' file and you don't need a '.htaccess' file.

Now you can run the program. It will display a table and a form you can use to insert new records. Also a button lets you reset the database table to its original state. If you click on a number in the 'ID' column of the table you get an edit page where you can change the names.

You can also get to the edit page by entering the URI '/edit/3' for example. That will take you to the edit page for 'ID' three. From that page you and enter the URI '/home' which will take you back to the home page.

The advantage of ReST is you do not actually need a '/edit/3' or a '/home' directory on your system. These are just syntactical links to control logic and as a result are easily modified.  Also some people think that '/edit/3' somehow looks cooler then '?edit=3', I am not sure I agree. ReST is syntactical sugar that takes more code and obfuscates what is really going on.  But that is just my opinion.

## Conclusion

Have fun with my mini framework. Get in there and look at the code, there really isn't that much of it. Try it out and if you have any comments or suggestions please let me know. 

You can email me at <a href="mailto://barton@bartonphillips.com">barton@bartonphillips.com</a>. 
My homepage is http://www.bartonphillips.com.

Cheers.
