<?php

/**
 * A repository for files accessible via the Amazon S3 service, treated as local filesystem.
 * Does not support database access or registration.
 *
 * Based on LocalFile.php, LocalRepo.php, FSRepo.php, File.php and OldLocalFile.php (ver 1.16-alpha, r69121)
 *
 *** Installation instructions ***
 Need to add to the end of LocalSettings.php:

 // AWS access info
 // s3 filesystem repo
 // Location of files in S3:
 //	http://s3.amazonaws.com/$wgUploadS3Bucket/$wgCloudStorageDirectory/.....
 $wgUploadS3Bucket = '---change me----'; // ******* Your S3 bucket to be used *******
 $wgCloudStorageDirectory = 'wiki-images'; // prefix to uploaded files
 $wgUploadS3SSL = false; // true if SSL should be used
 $wgPublicS3 = true; // true if public, false if authentication should be used
 $wgS3BaseUrl = "http".($wgUploadS3SSL?"s":"")."://s3.amazonaws.com/$wgUploadS3Bucket";
 $wgCloudStorageBaseUrl = "$wgS3BaseUrl/$wgCloudStorageDirectory";
 $wgLocalFileRepo = array(
 'AWS_ACCESS_KEY' => '---change me----', // ********** Your S3 access key *************
 'AWS_SECRET_KEY' => '---change me----', // ********** Your S3 secret key *************
 'class' => 'AmazonS3Repo',
 'name' => 's3',
 'directory' => $wgCloudStorageDirectory,
 'url' => $wgCloudStorageBaseUrl ? $wgCloudStorageBaseUrl . $wgUploadPath : $wgUploadPath,
 'urlbase' => $wgS3BaseUrl ? $wgS3BaseUrl : "",
 'hashLevels' => $wgHashedUploadDirectory ? 2 : 0,
 'thumbScriptUrl' => $wgThumbnailScriptPath,
 'transformVia404' => !$wgGenerateThumbnailOnParse,
 'initialCapital' => $wgCapitalLinks,
 'deletedDir' => $wgCloudStorageDirectory.'/deleted',
 'deletedHashLevels' => $wgFileStore['deleted']['hash'],
 'bucket' => $wgUploadS3Bucket,
 'AWS_S3_PUBLIC' => $wgPublicS3,
 'AWS_S3_SSL' => $wgUploadS3SSL
 );
 require_once("$IP/extensions/AmazonS3Repo/AmazonS3Repo.php");
 // s3 filesystem repo - end
 ***
 * @ingroup FileRepo
 */

if (!class_exists('AmazonS3Service')) require_once 'AmazonS3Service.php';

// Instantiate the class
$wgCss = new AmazonS3Service();

require_once("$IP/extensions/CloudStorage/CloudStorageFileArchive.php");
require_once("$IP/extensions/CloudStorage/CloudStorageRepo.php");
require_once("$IP/extensions/CloudStorage/AmazonS3File.php");
require_once("$IP/extensions/CloudStorage/AmazonS3Repo.php");