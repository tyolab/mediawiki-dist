<?php

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = (realpath( '.' ) ?: dirname( __DIR__ )) . '/../../';
	putenv("MW_INSTALL_PATH=$IP");
}

require "$IP/includes/WebStart.php";

$file = ($wgGaeHome ? $wgGaeHome : '') . 'google/appengine/api/cloud_storage/CloudStorageTools.php';

require_once $file;
use google\appengine\api\cloud_storage\CloudStorageTools;

require_once( "GoogleCloudStorage.php" );

$url = $_SERVER['REQUEST_URI'];
if ( !preg_match( '!^https?://!', $url ) ) {
	$url = 'http://unused' . $url;
}
wfSuppressWarnings();
$a = parse_url( $url );
$path = $a['path'];
$pos = strpos($path, 'images');

if ($pos) {

	global $wgCss;
	
	$imagePathFile = substr($path, $pos);
	
	$object_image_file = $wgCss->getBucketUrl() . $imagePathFile;
	$object_image_url = CloudStorageTools::getImageServingUrl($object_image_file/* , ['size' => 400, 'crop' => true] */);
	
	header('Location:' . $object_image_url);
	
	if (!$wgRunOnGae)
		header('Content-Type:image/jpeg');
}

