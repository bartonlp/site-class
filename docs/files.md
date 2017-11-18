# Additional Files User by SiteClass

---

## The 'mysitemap.json' File

The 'mysitemap.json' file is the site configuration file. 'siteload.php' loads the 'mysitemap.json' file that is in the current directory. If a 'mysitemap.json' file is not found in the current directory the parent directory is searched and this continues up until the DocuementRoot is searched and if still not found an exception is thrown.

Once a 'mysitemap.json' file is found the information in it is read in via 'file_get_contents()'. 
The information from the 'mysitemap.json' file is converted into a PHP object and returned.

You can generate a 'mysitemap.json' file by running 'mysitemap.json.php' and redirecting the output to 'mysitemap.json'.

My usual directory structure starts under a 'www' subdirectory. On an Apache2 host the structure looks like this:

```bash
/var/www/vendor          // this is the 'composer' directory where the 'bartonlp/site-class' resides
/var/www/html            // this is where your php files and js, css etc. 
                         // directories live
/var/www/html/includes   // this is where 'headFile', 'bannerFile', 
                         // 'footerFile' and child classes live
```

If I have multiple virtual hosts they are all off the '/var/www' directory instead of a single 'html' directory.

## How the xxxFile files look

In the 'mysitemap.json' file there can be three elements that describe the location of special files. 
These files are 1) 'headFile', 2) 'bannerFile' and 3) 'footerFile'.

I put the three special file in my '/var/www/html/includes' directory (where 'html' may be one of your virtual hosts 
and not named 'html'). 

Here is an example of my 'headFile':

```php
<?php
// head.i.php

return <<<EOF
<head>
  <!-- Example head.i.php file -->
  <title>{$arg['title']}</title>
  <!-- METAs -->
  <meta name=viewport content="width=device-width, initial-scale=1">
  <meta charset='utf-8'/>
  <meta name="copyright" content="$this->copyright">
  <meta name="Author"
    content="Barton L. Phillips, mailto:bartonphillips@gmail.com"/>
  <meta name="description"
    content="{$arg['desc']}"/>
{$arg['link']}
{$arg['extra']}
{$arg['script']}
{$arg['css']}
</head>
EOF;
```

These 'xxxFile' files return their contents. The $arg array is created form the argument passed to the 'getPageTopBottom' method. The 'getPageTopBottom' method also has access to the SiteClass '$this' property.

You will see if you delve into the SiteClass code that many things can be passed to the getPageTopBottom method, and the various sub-methods, but the standard things are:

* title
* desc
* link
* extra
* script
* css

As you saw in example 5 (example5.php in the 'examples' directory) I passed a '$h' object to `getPageTopBottom($h);`. For example it might look like this:

```php
$h->title = 'my title';
$h->desc = 'This is the description';
$h->link = '<link rel="stylesheet" href="test.css">';
$h->extra = '<!-- this can be anything from a meta, link, script etc -->';
$h->script = '<script> var a="test"; </script>';
$h->css = '<style> /* some css */ #test { width: 10px; } </style>';
list($top, $footer) = $S->getPageTopBottom($h);
```

As you can see in the 'headFile' example the '$this' can also be used as in '$this->copyright'. Any of the public, protected or private '$this' properties can be used in any of the special files as they are all included within 'SiteClass.class.php'.

As these special files are PHP files you can do anything else that you need to, including database queries. Just remember that you need to use '$this'. For example, to do a query do `$this->query($sql);` not `$S->query($sql);`. You can't use the variable from your project file that you created via the `$S = new SiteClass($h);` because it is NOT within scope.

I usually call these files 'head.i.php', 'banner.i.php' and 'footer.i.php' but you can name them anything you like. In the 'mysitemap.json' just add the full path to the file. For example:

```json
{
    "siteDomain": "localhost",
    "siteName": "YourSiteName",
    "className": "SiteClass",
    "copyright": "2016 Barton L. Phillips",
    "memberTable": "members",
    "noTrack": true,
    "dbinfo": {
        "database": "test.sdb",
        "engine": "sqlite3"
    },
    "headFile": "includes/head.i.php",
    "count": false
}
```

There is a default for the head, banner and footer section if you do not have special files. The DOCTYPE is by default `<!DOCTYPE html>` but that can be altered via an argument to the 'getPageTopBottom' method (`$h->doctype='xxx';`).

Creating the special files make the tedious boiler plate simple and yet configureable via the $arg array.

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
