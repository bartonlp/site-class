# Additional Files User by SiteClass

---

## The 'mysitemap.json' File

The 'mysitemap.json' file is the site configuration file. 'siteload.php' loads the 'mysitemap.json' file that is in the current directory. If a 'mysitemap.json' file is not found in the current directory the parent directory is searched and this continues up until the DocuementRoot is searched and if still not found an exception is thrown.

Once a 'mysitemap.json' file is found the information in it is read in via 'file_get_contents()'. 
The information from the 'mysitemap.json' file is converted into a PHP object and returned.

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
return <<<EOF
<head>
  <!-- bartonphillips.com/includes/head.i.php -->
  $h->title
  $h->base
  $h->viewport
  $h->charset
  $h->copyright
  $h->author
  $h->desc
  $h->keywords
  $h->canonical
  $h->meta
  $h->favicon
  $h->defaultCss
  $h->link
  $jQuery
  $trackerStr
  $h->extra
  $h->script
  $h->inlineScript
  $h->css
</head>
EOF;
```

All of the *$h* properties are created by _SiteClass_.

These 'xxxFile' files return their contents. 

You will see, if you delve into the SiteClass code, that many things can be passed to the getPageTopBottom method,  
and the various sub-methods, but the standard things are:

* title
* desc
* link
* extra
* script
* css

As you saw in example 5 (example5.php in the 'examples' directory) I set the properties of *$S* before calling `getPageTopBottom();`.   
For example it might look like this:

```php
$_site = require_once(getenv("SITELOADNAME"));
$S = new SiteClass($_site);
$S->title = 'my title';
$S->desc = 'This is the description';
$S->link = '<link rel="stylesheet" href="test.css">';
$S->extra = '<!-- this can be anything from a meta, link, script etc -->';
$S->h_script = '<script> var a="test"; </script>';
$S->css = '/* some css */ #test { width: 10px; };
[$top, $footer] = $S->getPageTopBottom();
```

As these special files are PHP files you can do anything else that you need to, including database queries.   
For example, to do a query do `$this->query($sql);` not `$S->query($sql);`.   

I usually call these files 'head.i.php', 'banner.i.php' and 'footer.i.php' but you can name them anything you like.   
In the 'mysitemap.json' just add the full path to the file. For example:

```json
/* This is a comment. This file allows comments, a true JSON file does not */
{
    "siteDomain": "localhost",
    "siteName": "YourSiteName",
    "className": "SiteClass",
    "copyright": "2016 Barton L. Phillips",
    "memberTable": "members",
    "noTrack": true,
    "dbinfo": {
        "host": "localhost",
        "user": "YOUR_DATABASE_USER",
        "database": "test.sdb",
        "engine": "mysqli"
    },
    "headFile": "includes/head.i.php",
}
```

Note, the *mysitemap.json* is not really a JSON file because you can add comments to the file.

There is a default for the head, banner and footer section if you do not have special files.   
The DOCTYPE is by default `<!DOCTYPE html>` but that can be altered via an argument to the 'getPageTopBottom' method (`$S->doctype='xxx';`).

Creating the special files make the tedious boiler plate simple and yet configureable via the $S properties.

---

[Examples](examples.html)  
[dbTables](dbTables.html)  
[SiteClass Methods](siteclass.html)  
[Additional Files](files.html)  
[Analysis and Tracking](analysis.html)  
[Index](index.html)

## Contact Me

Barton Phillips : [bartonphillips@gmail.com](mailto://bartonphillips@gmail.com)  
Copyright &copy; 2024 Barton Phillips  
Project maintained by [bartonlp](https://github.com/bartonlp)   
Last Modified January 6, 2024
