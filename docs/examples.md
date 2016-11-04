# SiteClass Verion 2.0

**SiteClass** is a PHP mini framework for simple, small websites. It can be esaly combined with other frameworks or templeting engines if needed. For small websites I feel that frameworks like Laravel or Meteor etc. are just too much.

This project has several parts that can function standalone or combined.

* Database.class.php : provides a wrapper for several different database engines.
* dbTables.class.php : uses the functionality of Database.class.php to make creating tables easy.
* ErrorClass.class.php : Error and Exception classes
* SiteClass.class.php : tools for making creating a site a little easier. The class provides methods to help with headers, banners, footers and more.

The following database engines are provided as the following classes:

1. dbMysqli.class.php : (rigorously tested) This is the latest PHP version of the MySql database engine.
2. dbSqlite.class.php : sqlite3 (used for the examples)

There are a couple of additional databases but they have not be rigouously tested.

## Disclamer

To start, this framework is meant for Linux not Windows. I don't use Windows, like it or have it, 
so nothing has been tried on Windows. 

I use Linux Mint which is an Ubuntu derivative which is a Debian derivative. 
I have not tried this package on any distributions that do not evolve from Debian.

## Install

There are several ways to install this project. 

### Download The ZIP File

Download the ZIP file from GitHub. Expand it and move the 'includes' directory somewhere. On a system with Apache2,
I usually put the 'includes' directory in the /var/www directory that Apache creates. 
Apache also usually creates /var/www/html and makes this the default DocumentRoot. 
I put the 'includes' directory just outside of the DocumentRoot. 
In my servers I have /var/www and then have my virtual hosts off that directory. 
That way the 'includes' directory is easily available to all of my virtual hosts.

If you are testing with the PHP server I put a 'www' directory off my $HOME and put the 'includes' directory there. 
I then make my test DocumentRoot off '&#126;/www' like '&#126;/www/test'. I `cd` to the test directory and 
do `php -S localhost:8080`. I can then use my browser and goto `localhost:8080` and see my 'index.php' file.

### Use Composer

If you have Apache or Nginx installed then you should made your project root somewhere within your 
DocumentRoot ('/var/www/html' for Apache2 on Ubuntu). Or if you want to make a seperate Apache virtual host with a 
registered domain name you can make your new project in '/var/www'.

Create a directory `mkdir myproject; cd myproject`, this is your project root directory. 
Add the following to 'composer.json', just cut and past:

```json
{
  "require": {
      "bartonlp/site-class": "dev-master"
  }
}
```

Then run 

```bash
composer install
```

**OR** you can just run 

```bash
composer require bartonlp/site-class:dev-master
``` 

which will create the 'composer.json' for you and load the package like 'composer install' above.

In your PHP file add `require_once($PATH_TO_VENDOR . '/vendor/autoload.php');` 
where '$PATH' is the path to the 'vendor' directory like './' or '../' etc.

There are some example files in the 'examples' directory at '$PATH_TO_VENDOR/vendor/bartonlp/site-class/examples'.

---
[Examples](examples.html)
[dbTables](dbTables.html)
[SiteClass Methods](siteclass.html)
[Additional Files](files.html)
[Analysis and Tracking](analysis)
[Index](index.html)

## Contact Me

Barton Phillips : [bartonphillips@gmail.com](mailto://bartonphillips@gmail.com)  
Copyright &copy; 2015 Barton Phillips  
Project maintained by [bartonlp](https://github.com/bartonlp)
