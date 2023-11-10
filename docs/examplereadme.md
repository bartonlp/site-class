# Examples

I have included a couple of examples to show how to use the SiteClass.

First you will need to install mysql if you don't already have it. You will need to install the PHP extension for mysql. The SiteClass code
runs with PHP 8 so it may not work with older versions of PHP.

Once you have mysql and the PHP extension you can set up mysql. These examples use a database called 'barton' with user 'barton'. There is
a file in this directory called schema.sql. You can run it in mysql and create the database and the tables.

There is a *mysitemap.json* file in the directory above this. It has the setup information and is pretty well documented. Oh, it is really
not a true *json* file as it can contain comments. After I remove the comments programmatically it is a true *json* file which I run
throush json_decode() so it must be valid *json*.

I have set up a .htaccess file which has a RewriteRule to redirect a css file to *tracker.php* for tracking.

The 'examples' directory should have everything you will need to run the examples including header, banner and footer files as well as css, 
images and fonts.

Now that the database is set up you can run the examples.

If you are just trying this out you can use the PHP server: <code>PHP -S localhost:8080</code>. You can change the port to what you want.   
Then use your browser to go to: <code>localhost:8080/&lt;AN_EXAMPLE_FILE&gt;</code>. See the example programs in the examples directory.

Any questions can be directed to bartonphillips@gmail.com

Have fun

---

[Examples](examplereadme.html)  
[dbTables](dbTables.html)  
[SiteClass Methods](siteclass.html)  
[Additional Files](files.html)  
[Analysis and Tracking](analysis.html)  
[Index](index.html)

## Contact Me

Barton Phillips : [bartonphillips@gmail.com](mailto://bartonphillips@gmail.com)  
Copyright &copy; 2023 Barton Phillips  
Project maintained by [bartonlp](https://github.com/bartonlp)
Last Modified November 8, 2023
