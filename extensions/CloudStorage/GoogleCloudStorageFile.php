<?php

require_once( "$IP/extensions/CloudStorage/CloudStorageFile.php" );

class GoogleCloudStorageFile extends CloudStorageFile {
	
	protected $repoClass = 'GoogleCloudStorageRepo';
	
	public function __construct($title, $repo) {
		parent::__construct();
// 		$this->repoClass = 'GoogleCloudStorageRepo';
	}
	
	function publish( $srcPath, $flags = 0 ) {
		return LocalFile::publish($srcPath, $flags);
	}
	
}