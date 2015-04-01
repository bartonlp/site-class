<UPDATE BLP 2015-01-12 -- I have made versions of the classes that use namespaces.
 namespace classes follow the standard for autoloading namespace classes. The non-
 namespace database classes are in the 'database-engines' directory while the classes with
 the 'namespace Database;' are in the 'Database' directory. Also the SiteClass has its
 namespaced version in the '/var/www/includes/SiteClass' directory and the non-namespace
 version is in '/var/www/includes'.
 Also the 'siteautoload.new.php' is now implemented as a class and namespace. The previous
 version is 'siteautoload.php'. I see no real good reason to use the define('TOPFILE'...
 peradyme I was using. From now on I will just use 'require_once("/path_to_loader/...");'.
 Also the new 'siteautoload.new.php' allows an argument to be passed to the constructor.
 The argument is a debug flag, if true then debug messages are displayed. To get this to
 work:
 <code>
   $AutoLoadDEBUG = true;
   require_once("/var/www/includes/autoload.new.php");
 </code>
/>

<UPDATE NEW-Way Jan. 25, 2013>
I have updated the bartonphillips.com and granbyrotary.org sites to use the
new classes and layout. The SiteClass.class.php is new and uses the new database classes in
DATABASE_ENGINES. These new database classes have a 'has' database rather than an inherited one.
This allows us to use a wrapper Database class (Database.class.php) which lets us use different 
databases. There are currently mysql (dbMysql.class.php), mysqli (dbMysqli.class.php), pdo
(dbPdo.class.php) (sqlite (dbSqlite.class.php) is expermental as it PostgreSql (dpPostgres.class.php)).
The new scheme uses a site map file (.sitemap.php) that resides in the Document Root. There is also a
site autoloader (siteautoload.php) that finds the sitemap as follows:

define('TOPFILE', $_SERVER['VIRTUALHOST_DOCUMENT_ROOT'] . "/siteautoload.php");
if(file_exists(TOPFILE)) {
  include(TOPFILE);
} else throw new Exception(TOPFILE . "not found");

$S = new Blp;

This is the preamble to every site file. The $_SERVER['VIRTUALHOST_DOCUMENT_ROOT'] can be changed to
any pre-defined location like for example on the LampHost server this could also be the home path
'/home/bartonlp/includes'. Right now I am using the document root and duplicating the
'siteautoload.php' file on each site. The site's siteautoload.php includes the version of that file
in /home/bartonlp/includes.

The '.sitemap.php' file has the configuration information for the site. This includes the layout map,
the site info ($siteinfo) and the database info ($dbinfo). 
There are 5 top level search locations described in the '.sitemap.php' file:
TOP
INCLUDES
DATABASE_ENGINES
SITE_ROOT
SITE_INCLUDES

These represent the locations of various components of the site. TOP is the top most directory. At
LampHost it is '/home/bartonlp' while on my localhost at home it is
"$_SERVER['VIRTUALHOST_DOCUMENT_ROOT']".  INCLUDES is under the TOP and is where classes etc. that
are used across domains (or subdomains) are located. For example the SiteClass, UpdateSite, email
etc. classes are in INCLUDES because they are used by all domains (bartonphillips.com,
granbyrotary.org, grandchorale.org etc).

Normally the DATABASE_ENGINES are under INCLUDES, but this is not manditory. The DATABASE_ENGINES 
directory has the dbXxxx.class.php files that define the database classes as well as Database.class.php
which is the wrapper and dbTables.class.php that has the 'make tables logic'. Also in this directory 
are the Error class (Error.class.php) and the SqlException class (SqlException.class.php). Finally
the helper functions are here (helper-functions.php).

SITE_ROOT is the base for the site. This can be the document root or a subdirectory in the case of
'kremmlingrotary.org' or 'grandlakerotary.org' or on my home system the various sub-directories under
'/var/www' like '/var/www/bartonphillips.com'.

SITE_INCLUDES is the directory where the site specific child class of SiteClass lives along with the
'head.i.php', 'banner.i.php', 'footer.i.php' etc. and any custome classes for the site.

The new class files under INCLUDES and DATABASE_ENGINES have the following formats:
1) the class name and the first part of the file name are the same and case sensitive. So the Database
class and the file that implements it are both Database.
2) all the new class files have [prefix name].class.php as in SiteClass.class.php, UpdateSite.class.php,
dbMysqli.class.php, Database.class.php, dbTables.class.php, Error.class.php etc.
3) all of my standard classes are auto-loaded by the 'siteautoload.php' program.

The classes under SITE_INCLUDES should also follow the above rules when possible; however, some classes
are from third parties and do not follow my rules so there may be special casses where the file name 
and the class name do not match.

I have tried to use relative link names whenever the link is local. I put a <base ...> tag in the 
head file (head.i.php) for the site. This means I can move the site from place to place and only have
to modify the '.sitemap.php' file (or at least that is my goal).

The old sites and some files in the new sites still use the old layout which has a config file and
specific head, banner, and footer files in the INCLUDES directory. These have the format
'[name].i.php' like 'blp.head.i.php' or 'rotary.head.i.php', or 'blp.config.php' for the configuration 
file that has the database info etc.

The old version of the SiteClass that uses inherets the Database class is implemented in 
'site.class.php' and the mysql database is in 'db.class.php'.

The 'email.class.php', 'sendemails.class.php', 'updatesite.class.php' are all older classes that
use the old SiteClass, and Database classes (note they are all lowercase).

One of the biggest changes from the old classes to the new is the database methods. In the old class
scheme I had a database query that returned a result set resource. All of the row fetches were done
by using the PHP mysql functions like 'mysql_fetch_array($result)' etc. When I went to the 'has' 
database approach I had to bring the PHP functions into the class definition so now all access is
done via the class. For example the old way looked like this:
$S = new Blp;
list($result, $n) = $S->query("select * from xyz", true);
if($n) {
  while($row = mysql_fetch_array($result)) { ... }
} ...

Now it would look like:
$S = new Blp;
$n = $S->query("select * from xyz");
if($n) {
  while($row = $S->fetchrow()) { ... }
} ...

The result is maintained by the class and the query methode now returns the number of rows in the
result set of the number of rows affected by the query if not a select.

If you need to do other querys inside of a fetchrow loop you need to do the query and then use the
'getResult()' method to get the outside loops result resource and then use the 'fetchrow($resource)'
version of the fetch.

Both the old and new scheme can work side by side.

</UPDATE NEW-Way Jan. 25, 2013>

<OLD Way>
Each of the above sites include their own xxx.i.php file at the top of each page. The granbyrotary.org
files include the granbyrotary.conf from this directory which includes its xxx.i.php file because
I changed the way I did things and didn't want to edit each page file -- long story.

The xxx.i.php files contain a class for that site that extends the SiteClass as needed for individual
needs. Each has an overriden constructor that does a require_once of the sites xxx.conf.php file from
this directory which has a $s array that contains the database information etc. that is then passed
on to the parent (SiteClass) class. Some sites have other overriden methods from SiteClass.

Each class also has a xxx.head.i.php and xxx.banner.i.php file in this directory. The xxx.head.i.php
file contains the <head></head> information for the site. The file sets the $pageHeadText variable
with the full header info. The $arg array is the input parameter with 'title', 'desc' and 'extra'. 
'title' is inserted between <title></title>, 'desc' is inserted into the meta description element, and
'extra' is inserted just before the </head> element. 'extra' usually contains either nothing or 
additional <script> or <style> elements (but can really have anything that can go into the <head>
section.

The xxx.head.i.php file is included in the SiteClass::getPageHead() function. The xxx.conf.php sets
the $s array element 'headFile' to the appropriate file.

Each site also has a xxx.banner.i.php file in this directory. The file contains the "banner" which is
placed in $pageBannerText and used by the SiteClass::getBanner() function which includes it like the
 getPageHead() function does with xxx.head.i.php. Most sites pass in the $mainTitle variable and some
pass other variables in from custom overriden getBanner() functions.

The Database class (db.class.php) does all of the MySql work for the sites.

The db.mysqli.class.php uses the newer interface but I have never changed over so db.class.php is 
the Database class that everything uses.

The Email class (email.class.php) implements email. The constructor HAS the SiteClass as its first
argument and uses SiteClass's Database and other elements. This class is used by granbyrotary.org's 
member_directory.php page and also by grandchorale.org. See these emplementations for more details. 
The class is pretty complicated.

The UpdateSite class (updatesite.class.php) extends Database. The class uses the 'site' table which
is in the bartonphillipsdotorg database. The constructor connects to that database. The class is
pretty complex and is used by the sites to allow most of the site content to be changed and edited 
online. The pages have this code near their top:
>>>>> BEGIN Example code

require_once("/home/bartonlp/includes/updatesite.class.php");

$s->site = "granbyrotary.org"; // the site we are 
$s->page = "index.php";        // the page we are
$s->itemname ="PresidentMsg";  // the section (or item) we are

// instantiate the class

$u = new UpdateSite($s); // Should do this outside of the START comments

// The following comment is more than a mear comment it is used by the UpdateSite class to locate 
// items and must have the form as follows: START UpdateSite <section_name><human readable text>
// then get the item and end the section with the END comment as shown.

// START UpdateSite PresidentMsg "President's Message"
$item = $u->getItem();
// END UpdateSite PresidentMsg

// If item is false then no item in table

if($item !== false) {
  // Create a message variable
  $presidentmsg = <<<EOF
<div>
<h2>{$item['title']}</h2>
<div>{$item['bodytext']}</div>
<p class="itemdate">Created: {$item['date']}</p>
</div>
<hr/>
EOF;
}
<<<<< END Example code

The information is read from the 'site' table.

Within the page the 'items' are rendered like this:
>>>>> BEGIN Example code
<!-- START UpdateSite: PresidentMsg -->
$presidentmsg
<!-- UpdateSite: PresidentMsg End -->
<<<<< END Example code

The above is usually 'echoed' in a 'echo <<<EOF' section. The <!-- comments are required by the class.

The class also provides editing, adding, and deleting of section items from the database. Site 
administrators see a special box on site pages that they can click and perform specail functions one
of which is the Updating if the 'site' table. The UpdateSite class provides all of this functionallity.

There are several helper file used by the UpdateSite class, they are:
updatesite-preview.i.php which does the full preview and update task,
updatesite-simple-preview.i.php which just show a quick simple preview and then updates.
Each file is fully commented at the start.

**************

I have several sites that do not use the SiteClass. The Grand Lake and Kremmling Rotary sites do not
use SiteClass currently. Nor does Tina's or Connie's site. Connie's site is in Canoga Park, the login 
information follows:
>>>>> BEGIN info
# Human Aspects
# account: tha123
# password: cjf123
ssh tha123@humanaspect.com
<<<<< END info

The endpolio.com site does not use SiteClass either. It has a fairly complex and kluggie archetecture
that uses an indexswitch.php file to gather elements for each page and past them together. If I had
more energy I would rewrite this mess to use the SiteClass approch but I don't.
Tina's site uses a somewhat similar index switch.

</OLD Way>

<NOV-02-2013> 

The contents of this directory (/home/bartonlp/includes) has both OLD and NEW classes. 
I think that bartonphillips.com is using the new classes except for the following directories:
fairway, messiah, mysqlslideshow, test and wildernessgroup.
granbyrotary.org is mostly using the new classes. Most of the files in the articles directory use the
old classes.
grandchorale.org use the OLD classes.
endpolio.com do not use any of these classes.
tinapurwininsurance.com uses db.class.php only.

 database_engines [dir]          (new directory has all of the new database classes)
 Mobile_Detect.php               (new)
 SendEmails.class.php            (new)
 SiteClass.class.php             (new)
 UpdateSite.class.php            (new)
 blp.banner.i.php                (OLD) only used in the 'test' dir
 blp.conf.php                    (OLD) used in bartonphillips.com/htdocs/blp.i.php and in 'test' dir.
 blp.footer.i.php                (OLD) not used anywhere
 blp.head.i.php                  (OLD) only used in the 'test' dir
 db.class.php                    (OLD)
 db.mysqli.class.php             (OLD)
 db.pdo.class.php                (OLD)
 email.class.php                 (OLD)
 granbyranch.banner.i.php        (OLD)
 granbyranch.head.i.php          (OLD)
 granbyrotary.conf               (OLD)
 granbyrotary.conf.php           (OLD)
 grandchorale.banner.i.php       (OLD)
 grandchorale.conf.php           (OLD)
 grandchorale.head.i.php         (OLD)
 rotary.banner.i.php             (OLD)
 rotary.head.i.php               (OLD)
 sendemails.class.php            (OLD)
 site.class.php                  (OLD)
 siteautoload.php                (NEW) the siteautoload.php file in each site link to this one
 updatesite-preview.i.php        (OLD/NEW) these three are used by the site's updatesite2.php
 updatesite-preview.new.i.php    (OLD/NEW) 
 updatesite-simple-preview.i.php (OLD/NEW)
 updatesite.class.php            (OLD)

</NOV-02-2013>
