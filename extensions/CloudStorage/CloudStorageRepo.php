<?php

/**
 * A repository for files accessible via the Cloud Storage filesystem. Does not support
 * database access or registration.
 * @ingroup FileRepo
 */
class CloudStorageRepo extends FileRepo {
	
	protected $fileFactory = array( 'CloudStorageFile', 'newFromTitle' );
	protected $oldFileFactory = false;
	
	protected $bucketName;
	
	var $directory, $deletedDir, $deletedHashLevels, $fileMode;
	var $urlbase;
	var $CSS_ACCESS_KEY, $CSS_SECRET_KEY, $CSS_PUBLIC, $CSS_SSL;

	var $pathDisclosureProtection = 'simple';

	function __construct( $info ) {
		parent::__construct( $info );

		global $wgCss;
		
		$this->directory = isset( $info['directory'] ) ? $info['directory'] : $wgCss->getBucketUrl();
		
		$this->bucketName = isset( $info['bucket'] ) ? $info['bucket'] : '';
	}
	

	public function setBucketName($bucketName) {
		$this->bucketName = $bucketName;
	}

	/**
	 * Get the public (upload) root directory of the repository.
	 */
	function getRootDirectory() {
		return $this->directory;
	}

	/**
	 * Get the public root URL of the repository (not authenticated, will not work in general)
	 */
	function getRootUrl() {
		return $this->url;
	}

	/**
	 * Get the base URL of the repository (not authenticated, will not work in general)
	 */
	function getUrlBase() {
		return $this->urlbase;
	}
	
	/**
	 * Returns true if the repository uses a multi-level directory structure
	 */
	function isHashed() {
		return (bool)$this->hashLevels;
	}

	/**
	 * Store a batch of files from local (i.e. Windows or Linux) filesystem to Cloud Storage
	 *
	 * @param $triplets Array: (src,zone,dest) triplets as per store()
	 * @param $flags Integer: bitwise combination of the following flags:
	 *     self::DELETE_SOURCE     Delete the source file after upload
	 *     self::OVERWRITE         Overwrite an existing destination file instead of failing
	 *     self::OVERWRITE_SAME    Overwrite the file if the destination exists and has the
	 *                             same contents as the source (not implemented in Cloud Storage)
	 */
	function storeBatch( $triplets, $flags = 0 ) {
		return parent::storeBatch($triplets, $flags);
	}

	/**
	 * Append a file from from local (i.e. Windows or Linux) filesystem to  existing file
	 *
	 * @param $srcPath - file on  filesystem
	 * @param $toAppendPath - file from local filesystem
	 * @param $flags Integer: bitwise combination of the following flags:
	 *     self::DELETE_SOURCE     Delete the toAppend file after append
	 */
	function append( $srcPath, $toAppendPath, $flags = 0 ) {
		// virtual
		return false;
	}

	/**
	 * Take all available measures to prevent web accessibility of new deleted
	 * directories, in case the user has not configured offline storage
	 * Not applicable in S3
	 */
	protected function initDeletedDir( $dir ) {
		return;
	}

	/**
	 * Pick a random name in the temp zone and store a file to it.
	 * @param $originalName String: the base name of the file as specified
	 *     by the user. The file extension will be maintained.
	 * @param $srcPath String: the current location of the file.
	 * @return FileRepoStatus object with the URL in the value.
	 */
	function storeTemp( $originalName, $srcPath ) {
		wfDebug(__METHOD__.": ".print_r($originalName,true)."--> $srcPath \n");
		$date = gmdate( "YmdHis" );
		$hashPath = $this->getHashPath( $originalName );
		$dstRel = "$hashPath$date!$originalName";
		$dstUrlRel = $hashPath . $date . '!' . rawurlencode( $originalName );

		$result = $this->store( $srcPath, 'temp', $dstRel );
		$result->value = $this->getVirtualUrl( 'temp' ) . '/' . $dstUrlRel;
		return $result;
	}

	/**
	 * Remove a temporary file or mark it for garbage collection
	 * @param $virtualUrl String: the virtual URL returned by storeTemp
	 * @return Boolean: true on success, false on failure
	 */
	function freeTemp( $virtualUrl ) {
		wfDebug(__METHOD__.": ".print_r($virtualUrl,true)."\n");
		global $wgCss;
		$path = $virtualUrl;
		$infoS3  = $wgCss->getObjectInfo($this->bucketName, $path); // see if on S3
		wfDebug(__METHOD__." path: $path, infoS3:".print_r($infoS3,true)."\n");
		$success = $wgCss->deleteObject($this->bucketName, $path);
		return $success;
	}

	/**
	 * Publish a batch of files
	 * @param $triplets Array: (source,dest,archive) triplets as per publish()
	 *        source can be on local machine or on S3, dest must be on S3
	 * @param $flags Integer: bitfield, may be FileRepo::DELETE_SOURCE to indicate
	 *        that the source files should be deleted if possible
	 */
	function publishBatch( $triplets, $flags = 0 ) {
		return parent::publishBatch($triplets, $flags);
	}

	/**
	 * Move a group of files to the deletion archive.
	 * If no valid deletion archive is configured, this may either delete the
	 * file or throw an exception, depending on the preference of the repository.
	 *
	 * @param $sourceDestPairs Array of source/destination pairs. Each element
	 *        is a two-element array containing the source file path relative to the
	 *        public root in the first element, and the archive file path relative
	 *        to the deleted zone root in the second element.
	 * @return FileRepoStatus
	 */
	function deleteBatch( $sourceDestPairs ) {
		wfDebug(__METHOD__.": ".print_r($sourceDestPairs,true)."\n");
		global $wgCss;
		$status = $this->newGood();
		if ( !$this->deletedDir ) {
			throw new MWException( __METHOD__.': no valid deletion archive directory' );
		}

		/**
		 * Validate filenames and create archive directories
		 */
		foreach ( $sourceDestPairs as $pair ) {
			list( $srcRel, $archiveRel ) = $pair;
			if ( !$this->validateFilename( $srcRel ) ) {
				throw new MWException( __METHOD__.':Validation error in $srcRel' );
			}
			if ( !$this->validateFilename( $archiveRel ) ) {
				throw new MWException( __METHOD__.':Validation error in $archiveRel' );
			}
			$archivePath = "{$this->deletedDir}/$archiveRel";
			// $archiveDir = dirname( $archivePath );
			// if ( !is_dir( $archiveDir ) ) {
				// if ( !wfMkdirParents( $archiveDir ) ) {
					// $status->fatal( 'directorycreateerror', $archiveDir );
					// continue;
				// }
				// $this->initDeletedDir( $archiveDir );
			// }
			// // Check if the archive directory is writable
			// // This doesn't appear to work on NTFS
			// if ( !is_writable( $archiveDir ) ) {
				// $status->fatal( 'filedelete-archive-read-only', $archiveDir );
			// }
		}
		if ( !$status->ok ) {
			// Abort early
			return $status;
		}

		/**
		 * Move the files
		 * We're now committed to returning an OK result, which will lead to
		 * the files being moved in the DB also.
		 */
		foreach ( $sourceDestPairs as $pair ) {
			list( $srcRel, $archiveRel ) = $pair;
			$srcPath = "{$this->directory}/$srcRel";
			$archivePath = "{$this->deletedDir}/$archiveRel";
			wfDebug(__METHOD__.": src: $srcPath, dest: $archivePath \n");
			$good = true;
			$info = $wgCss->getObjectInfo($this->bucketName, $archivePath);
			wfDebug(__METHOD__." :$archivePath\ninfo:".print_r($info,true)."\n");
			if ( $info ) {
				# A file with this content hash is already archived
				if ( !$wgCss->deleteObject($this->bucketName, $srcPath) ) {
					$status->error( 'filedeleteerror', $srcPath );
					$good = false;
				}
			} else{
				if(! (
						$wgCss->copyObject($this->bucketName, $srcPath, 
							$this->bucketName, $archivePath, 
							   ($this->CSS_PUBLIC ? S3::ACL_PUBLIC_READ : S3::ACL_PRIVATE)) &&
						$wgCss->deleteObject($this->bucketName, $srcPath))
					) {
					wfDebug(__METHOD__.": FAILED moving file $dstPath to $archivePath\n");
					$status->error( 'filerenameerror', $srcPath, $archivePath );
					$good = false;
				}
			}
			if ( $good ) {
				$status->successCount++;
			} else {
				$status->failCount++;
			}
		}
		return $status;
	}

	/**
	 * Get a relative path for a deletion archive key,
	 * e.g. s/z/a/ for sza251lrxrc1jad41h5mgilp8nysje52.jpg
	 */
	function getDeletedHashPath( $key ) {
		$path = '';
		for ( $i = 0; $i < $this->deletedHashLevels; $i++ ) {
			$path .= $key[$i] . '/';
		}
		return $path;
	}

	/**
	 * Call a callback function for every file in the repository.
	 * Uses the filesystem even in child classes.
	 */
	function enumFilesInFS( $callback ) {
		global $wgCss;
		$contents = $wgCss->getBucket($this->bucketName, $this->directory."/");
		wfDebug(__METHOD__." :".print_r($contents,true)."\n");
		foreach( $contents as $path ) {
			call_user_func( $callback, $path->name );
		}
	}

	/**
	 * Call a callback function for every file in the repository
	 * May use either the database or the filesystem
	 */
	function enumFiles( $callback ) {
		$this->enumFilesInFS( $callback );
	}

	/**
	 * Get properties of a file with a given virtual URL
	 * The virtual URL must refer to this repo
	 */
	function getFileProps( $virtualUrl ) {
		$path = $this->resolveVirtualUrl( $virtualUrl );
		return File::getPropsFromPath( $path );
	}

	/**
	 * Path disclosure protection functions
	 *
	 * Get a callback function to use for cleaning error message parameters
	 */
	function getErrorCleanupFunction() {
		switch ( $this->pathDisclosureProtection ) {
			case 'simple':
				$callback = array( $this, 'simpleClean' );
				break;
			default:
				$callback = parent::getErrorCleanupFunction();
		}
		return $callback;
	}

	function simpleClean( $param ) {
		if ( !isset( $this->simpleCleanPairs ) ) {
			global $IP;
			$this->simpleCleanPairs = array(
				$this->directory => 'public',
				"{$this->directory}/temp" => 'temp',
				$IP => '$IP',
				dirname( __FILE__ ) => '$IP/extensions/WebStore',
			);
			if ( $this->deletedDir ) {
				$this->simpleCleanPairs[$this->deletedDir] = 'deleted';
			}
		}
		return strtr( $param, $this->simpleCleanPairs );
	}

	/**
	 * Chmod a file, supressing the warnings.
	 * @param $path String: the path to change
	 */
	protected function chmod( $path ) {
		wfSuppressWarnings();
		chmod( $path, $this->fileMode );
		wfRestoreWarnings();
	}

}
