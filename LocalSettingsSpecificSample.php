<?php

$wgDBserver = '173.194.226.127';
$wgServer = "//104.197.40.53";

$wgScriptPath       = "/w"; # Alias of /var/www/mediawiki-1.17.0
$wgScriptExtension  = ".php";
$wgArticlePath      = "$wgScriptPath/$1"; # Alias of /var/www/mediawiki-1.17.0/index.php
$wgUsePathInfo      = true;

$wgStylePath = "$wgScriptPath/skins"; 
$wgLogo = "$wgStylePath/common/images/xxxpedia.png";

/* use Google Cloud Storage to store images */
$wgCssProtocol = "file";
