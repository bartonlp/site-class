<header>

# SiteClass

SiteClass class mini framework for my websites.
</header>

<div id="banner">
<span id="logo"></span>
<a href="https://github.com/bartonlp/site-class" class="button fork"><strong>View On GitHub</strong></a>
<div class="downloads">
  <span>Downloads:</span>
  <ul>
    <li><a href="https://github.com/bartonlp/site-class/zipball/master" class="button">ZIP</a></li>
    <li><a href="https://github.com/bartonlp/site-class/tarball/master" class="button">TAR</a></li>
  </ul>
</div>
</div><!-- end banner -->

<div class="wrapper">
  <nav>
    <ul></ul>
  </nav>
<section>

# SiteClass Version 4.0.0
  
**SiteClass** is a PHP mini framework for my websites.

I have another class called **SimpleSiteClass** that is intended for
general use. It has most of the features without being designed just for
my websites.

It is located at
[githum.com/bartonlp/simple-site-class](https://github.com/bartonlp/simple-site-class).  
Documentation for
[**SimpleSiteClass**](https://bartonlp.github.io/simple-site-class/).

## Still Interested In **SiteClass**? Read On.

This project has several parts that can function standalone or combined.

- Database.class.php : provides a wrapper for several different database
  engines.
- dbTables.class.php : uses the functionality of Database.class.php to
  make creating tables easy.
- ErrorClass.class.php : Error and Exception classes
- SiteClass.class.php : tools for making creating a site a little
  easier. The class provides methods to help with headers, banners,
  footers and more.
- dbMysqli.class.php : provides a wrapper for the PHP mysqli class and
  has been rigorously tested.

## Disclamer

To start, this framework is meant for Linux not Windows. I don't use
Windows, like it or have it, so nothing has been tried on a Windows
server.

I use Linux Ubuntu 22.04 which is a Debian derivative. I have not tried
this package on any distributions that do not evolve from Debian.

## Install

There are several ways to install this project.

### Download The ZIP File

Download the ZIP file from GitHub. Expand it and move the *includes*
directory somewhere. On a system with Apache2, I usually put the
*includes* directory in the */var/www* directory that Apache creates.
Apache also usually creates */var/www/html* and makes this the default
DocumentRoot. I put the *includes* directory just outside of the
DocumentRoot. In my servers I have */var/www* and then have my virtual
hosts off that directory. That way the *includes* directory is easily
available to all of my virtual hosts.

If you are testing with the PHP server, I put a *www* directory off my
*$HOME* and put the *includes* directory there. I then make my test
DocumentRoot off *www* like *www/test*. I *cd* to the test directory and
do *php -S localhost:8080*. I can then use my browser and goto
*localhost:8080* and see my *index.php* file.

### Use Composer

If you have Apache installed, it has created */var/www*. You should
create your project in that directory. Apache also creates
*/var/www/html* by default, and if you do not change that, you should
create your project within */var/www/html*. Or if you want to make a
separate Apache virtual host with a registered domain name you can
create your new project in */var/www/YOUR_DOMAIN_NAME*.

If you do not already have a *composer.json* file just cut and past the
following:

``` sourceCode
echo "
{
    "require": {
        "bartonlp/site-class": "dev-master"
    }
}" > composer.json
```
Then run
``` sourceCode
composer install
```
**OR** you can just run
``` sourceCode
composer require bartonlp/site-class:dev-master
```
which will create the *composer.json* for you and load the package like
*composer install* above.  
In your PHP file add
``` sourceCode
$_site = require_once($PATH_TO_VENDOR . '/vendor/bartonlp/site-class/includes/siteload.php');
```
where *\$PATH_TO_VENDOR* is the path to the *vendor* directory.

There are some example files in the *examples* directory at
*\$PATH_TO_VENDOR/vendor/bartonlp/site-class/examples*. There is more
documentation in the */docs* directory.

## Further Documentation

- [dbTables Documentation](dbTables.html)
- [SiteClass and Database Methods](siteclass.html)
- [Additional Files User by SiteClass](files.html)
- [Page Counting, Analysis and Tracking](analysis.html)
- [Examples Readme](examplereadme.html)

## Contact me

Barton Phillips : <bartonphillips@gmail.com>  
Copyright © 2024 Barton Phillips  
Last modified January 6, 2024
</section>
<footer>
Project maintained by [Barton Phillips](https://github.com/bartonlp)

<span class="small">Hosted on GitHub Pages &mdash; Theme by  
  [mattgraham](https://twitter.com/michigangraham)</span>
</footer>
</div>
