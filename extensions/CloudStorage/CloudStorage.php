<?php

$wgCloundStorageDriver = "gs";

$wgCloudStorageBucket = 'xxxpedia'; // ******* Your S3 bucket to be used *******
$wgCloudStorageDirectory = 'images'; // prefix to uploaded files
$wgUseSSL = false; // true if SSL should be used
$wgPublic = true; // true if public, false if authentication should be used

require_once 'CloudStorageService.php';

# Cloud Storage Service instance
$wgCss = false;

if ($wgCloundStorageDriver == "gs") {
	$wgUploadToRepoName = 'GoogleCloudStorage';
	require_once 'GoogleCloudStorage.php';
	require_once 'UploadFromUrlToGoogleCloudStorage.php';
}
elseif ($wgCloundStorageDriver == "s3") {
	$wgUploadToRepoName = 'AmazonS3';
	require_once 'AmazonS3.php';
}
else {
	die ("No Approprieate Cloud Storage Driver Found");
}

$wgUploadDirectory = $wgCloudStorageDirectory;
