# BLP 2022-04-10 - New version 3.2. Added argument and return types for php 8.1
Big changes to getPageTopBottom(), getPageTop(), getPageHead(), getPageBanner() and getPageFooter().
I added a lot more $h, $b, and $this items. I need to further document this in the 'docs' section.

# BLP 2021-10-28 -- Reworked Database.class.php

It only use a single object. I no longer get password from the domain's mysitemap.json. I now get it from a secure location at
/var/www/bartonphillipsnet/PASSWORDS/datbase-password. This location is not saved to github.com and is secure against Internet access.

# BLP 2021-03-27 -- remove 'members' from counter2

In the future I may remove the 'counter' table and just use 'counter2'. The 'counter' table has the sum of all hits while the 
'counter2' table has the sum for a filename for a day. I can however get the same results with a mysql query on 'counter2'.

# Updated Site

SiteClass has been modified to not use 'members'.
I have made may changes in the last month. I removed the 'bartonlp.com' droplet at DigitalOcean and extended the droplet 'bartonlp.org'
to have 100GB and 4 cpus. Changed all of the DNS at DititalOcean to reflect the new layout.

I added the domains 'www.newbern-nc.info' and 'www.newbernzig.com" the sites for the Tyson Group. Dropped 'www.mountainmesiah.com', 'grangyrotary.org', 
'puppiesandmore.com'. Downsized 'www.applitec.com' now that Glen has moved to Disney.

While adding the Tyson Group I reorganized a lot. Changed the include/ directories to use as much as possible from 'www.bartonphillips.com' 
and added symlinks to either the whole directory or items within it to the other domains.

# Currently I have:  
<ul>
<li>www.bartonphillips.com: my main domain</li>
<li>www.bartonlp.org: default that links to my main page</li>
<li>www.allnaturalcleaningcompany.com: designed for Walid</li>
<li>www.applitec.com: downsized</li>
<li>www.newbern-nc.info: The Tyson Group</li>
<li>www.newbernzig.com: The Ziegler Suites</li>
<li>www.bartonphillips.net: my CookiLess domain</li>
</ul>

See the includes/SiteClass.class.php file for a list of changes.
