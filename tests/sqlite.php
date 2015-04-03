<?php  
require_once('../includes/siteautoload.class.php'); // path to siteautoload.class.php

Error::setNoEmailErrs(true);
Error::setDevelopment(true);

$S = new SiteClass($siteinfo);

$sql = 'select * from members';
$data = $S->queryfetch($sql, true);
echo count($data)."<br>";
vardump($data);
