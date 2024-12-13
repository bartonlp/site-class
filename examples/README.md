# Examples

**INPORTANT NOTE:** you can disable the log function by either setting ```$_site->noTrack=true``` or adding ```"noTrack":true,``` to the mysitemap.json.
Doing either of those things will keep the **SiteClass** from using the any tables to log visitors. 
This will also mean that example2.php programs will not work.

You will need to install *sqlite3* for the OS and the *pdo_sqlite* for PHP, or
install *mysql* for the OS and *pdo_mysql* for PHP.

The **SiteClass** code runs with **PHP 8+**, and it may not work with older versions of PHP.

These examples use a database called *barton* with user *barton*. There is
a file in this directory called *schema.sql* if you want a MySql database. You can run it in mysql and create the table.

If you are using *sqlite* a database named *barton* will be created when you run the examples.

Now that the database is set up you can run the examples.

I have included twp examples to show how to use the **SiteClass**. 
If you set up your directory structure as:  
```/var/www/```  
```/var/www/html```  
and used **composer** to create a */var/www/vendor/bartonlp/site-class* directory.

You can also *clone* the repository from [https://github.com/bartonlp/-site-class](https://github.com/bartonlp/site-class). 

In either case, from the *examples* directory in the downloaded location, do:  
```php -S localhost:3000```  
Then open your browser and enter:  
```localhost:3000/example1.php```  

When you do this you should see the following message:

<div style='border: solid black 2px; padding: 5px; display: inline-block'>
   <h1 style='margin-top: 0px;'>You Must Fill in $home!</h1>
   <p>Open the example file and fill in the $home variable.<br>
   You can also add a query: ?home=&lt;your home directory&gt;.<br>
   For example, the query string would be ?home=/var/www, if you cloned the repository into your /var/www directory.</p>
</div>

Each example has a link to the other example.

Note, that when running with the PHP server it does not use the **apache** *.htaccess* file.

You can run both sets of examples from a browser if you have the **apache** server running on your machine. 
In that case you should copy the the _examples_ directory to your **apache** server page.

Any questions can be directed to Barton Phillips at [bartonphillips@gmail.com](mail-to:bartonphillips@gmail.com)

Have fun

<h2 style='margin-top: 0px;margin-bottom: 0px;'>Contact Me</h2>

Barton Phillips : [bartonphillips@gmail.com](mailto://bartonphillips@gmail.com)  
Copyright &copy; 2025 Barton Phillips  
Project maintained by [Barton Phillips](https://github.com/bartonlp)  
Last Modified January 1, 2025

