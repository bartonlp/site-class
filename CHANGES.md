# BLP 2021-03-27 -- remove 'members' from counter2
<pre>
CREATE TABLE `counter2` (
  `site` varchar(50) NOT NULL DEFAULT '',
  `date` date NOT NULL,
  `filename` varchar(255) NOT NULL DEFAULT '',
  `count` int DEFAULT '0',
  `bots` int DEFAULT '0',
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`site`,`date`,`filename`),
  KEY `site` (`site`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
</pre>
In the future I may remove the 'counter' table and just use 'counter2'. The 'counter' table has the sum of all hits while the 
'counter2' table has the sum for a filename for a day. I can however get the same results with a mysql query on 'counter2'.

SiteClass has been modified to not use 'members'.
I have made may changes in the last month. I removed the 'bartonlp.com' droplet at DigitalOcean and extended the droplet 'bartonlp.org'
to have 100GB and 4 cpus. Changed all of the DNS at DititalOcean to reflect the new layout.

I added the domain 'www.newbern-nc.info' the site for the Tyson Group. Dropped 'www.mountainmesiah.com', 'grangyrotary.org', 
'puppiesandmore.com'. Downsized 'www.applitec.com' now that Glen has moved to Disney.

While adding the Tyson Group I reorganized a lot. Changed the include/ directories to use as much as possible from 'www.bartonphillips.com' 
and added symlinks to either the whole directory or items within it to the other domains.

Currently I have:  
<ul>
<li>www.bartonphillips.com: my main domain</li>
<li>www.bartonlp.org: default that links to my main page</li>
<li>www.allnaturalcleaningcompany.com: designed for Walid</li>
<li>www.applitec.com: downsized</li>
<li>www.bartonphillips.net: my CookiLess domain</li>
</ul>
I still have 'www.bartonlp.com' but I will let it expire when it comes due.
