# BLP 2023-01-30 - Updated all of the Version numbers.  
See the file in includes and includes/datbase-engines for details. 
# BLP 2022-08-18 -   
Added examples directory back and reworked exmples and tested it.
It seems to work ok from the examples directory
when using php -S ...; I tested it when loaded into a real website and it also seems to work OK.

# BLP 2022-07-31 -   
Removed the examples directory and deleted all of the database engines except
dbMysqli.class.php. We no longer support sqlite3, pod etc.

# BLP 2022-04-30 - tracker() and daycount() and checkIfBot().  
Logic changer to try to better identify zero entries in tracker etc.

# BLP 2022-04-24 -  
Added defines.php and added SITECLASS_DIR as a constant in siteload.php.
Changes to SiteClass, siteload.php, tracker.js, tracker.php and beacon.php. These all now use the constants defined in defines.php.

# BLP 2022-04-17 -   
The applitec.com domain expired and we didn't want to renew it.

# BLP 2022-04-12 -  
Removed if($this->nodb) in all of the protected functions.

# BLP 2022-04-10 -  
New version 3.2. Added argument and return types for php 8.1.
Big changes to getPageTopBottom(), getPageTop(), getPageHead(), getPageBanner() and getPageFooter().
I added a lot more $h, $b, and $this items. I need to further document this in the 'docs' section.

# BLP 2021-10-28 --   
Reworked Database.class.php.
It only use a single object. I no longer get password from the domain's mysitemap.json. I now get it from a secure location at
/var/www/bartonphillipsnet/PASSWORDS/datbase-password. This location is not saved to github.com and is secure against Internet access.

# BLP 2021-03-27 --   
Remove 'members' from counter2.
In the future I may remove the 'counter' table and just use 'counter2'. The 'counter' table has the sum of all hits while the
'counter2' table has the sum for a filename for a day. I can however get the same results with a mysql query on 'counter2'.

# BLP 2021-03-1 - About. 
SiteClass has been modified to not use 'members'.
I have made may changes in the last month. I removed the 'bartonlp.com' droplet at DigitalOcean and extended the droplet 'bartonlp.org'
to have 100GB and 4 cpus. Changed all of the DNS at DititalOcean to reflect the new layout.
I added the domains 'www.newbern-nc.info' and 'www.newbernzig.com" the sites for the 
Tyson Group. Dropped (Removed) 'www.mountainmesiah.com', 'grangyrotary.org',
'puppiesandmore.com'and 'www.applitec.com' now that Glen has moved to Disney.
While adding the Tyson Group I reorganized a lot. Changed the include/ directories to use as much as possible from 'www.bartonphillips.com'
and added symlinks to either the whole directory or items within it to the other domains.

# Currently I have:  
<ul>
<li>www.bartonphillips.com: my main domain</li>
<li>www.bartonlp.org: default that links to my main page at /var/www/html</li>
<li>www.allnaturalcleaningcompany.com: designed for Walid</li>
<li>www.newbern-nc.info: The Tyson Group</li>
<li>www.newbernzig.com: The Ziegler Suites</li>
<li>www.bartonphillips.net: my CookiLess domain</li>
<li>www.bartonphillips.org: my home HP-Envy disktop which is served from my home. It only has https</li>
<li>www.bonnieburch.com: my wifes website</li>
</ul>

I also have a <b>dyndns.org</b> account (bartonphillips.dyndns.org) that keeps track of my ip. I can get to my Rpi via
baronphillips.dyndns.org:8080 or bartonphillips.org:8080, these are only http. I will get rid of that once I can get a static ip.
