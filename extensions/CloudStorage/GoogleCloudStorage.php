<?php 
global $wgRunOnGae, $wgUploadPath, $wgUploadDirectory;

if ( !isset($wgGaeHome) && !$wgRunOnGae )
	$wgGaeHome = '/data/tools/GAE/google_appengine/php/sdk/';

$wgCssProtocol = "gs";

if ($wgRunOnGae === true || $wgRunOnLocalGae === true) {
	$wgCloudStorageUploadPath = 'images';
}
else {
	// should leave this part to LocalSettingsSpecific.php
	$wgCssProtocol = "file";
	$wgCloudStorageBucket = '';
	//$wgCloudStorageUploadPath = $wgUploadDirectory;
	$wgCloudStorageUploadPath = "$IP/images";
}

$wgFsBackend = $wgCssProtocol;

$wgCloudStorageBaseUrl = $wgCssProtocol . "://";
$wgCloudStorageUrl = $wgCloudStorageBaseUrl . $wgCloudStorageBucket . '/';
$wgCloudStorageDirectory = $wgCloudStorageUrl . $wgCloudStorageUploadPath;
$wgUploadThumbUrl = $wgUploadPath . '/thumb';

/*******************************************************************************************************************
		'backend' => $wgFsBackend,

		'url' => $wgCloudStorageUrl ? $wgCloudStorageUrl . $wgCloudStorageUploadPath : $wgCloudStorageUploadPath,
		'urlbase' => $wgCloudStorageBaseUrl ? $wgCloudStorageBaseUrl : "",
		'hashLevels' => $wgHashedUploadDirectory ? 2 : 0,
		'thumbScriptUrl' => $wgThumbnailScriptPath,
		'transformVia404' => !$wgGenerateThumbnailOnParse,
		'initialCapital' => $wgCapitalLinks,
		'deletedDir' => $wgCloudStorageDirectory.'/deleted',
		'deletedHashLevels' => $wgHashedUploadDirectory ? 3 : 0,
 ********************************************************************************************************************/
$wgLocalFileRepo = array(
		'class' => 'GoogleCloudStorageRepo',
		'name' => 'gs',
		'directory' => $wgCloudStorageDirectory,
		'scriptDirUrl' => $wgScriptPath,
		'scriptExtension' => $wgScriptExtension,
		'url' => $wgUploadBaseUrl ? $wgUploadBaseUrl . $wgUploadPath : $wgUploadPath,
		'thumbUrl' => $wgUploadThumbUrl,
		'bucket' => $wgCloudStorageBucket
);

if (!class_exists('GoogleCloudStorageService')) require_once( "GoogleCloudStorageService.php");

# Cloud Storage Service instance
$wgCss = new GoogleCloudStorageService();
$wgCss->setProtocol($wgCssProtocol);
$wgCss->setBucketName($wgCloudStorageBucket);

require_once("CloudStorageRepo.php");
require_once("GoogleCloudStorageFile.php");
require_once("CloudStorageFileArchive.php");
require_once("GoogleCloudStorageRepo.php");
