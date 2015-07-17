<?php

class AmazonS3Repo extends CloudStorageRepo {
	
	var $fileFactory = array( 'AmazonS3File', 'newFromTitle' );
	var $oldFileFactory = array( 'CloudStorageFileArchive', 'newFromTitle' );
	var $fileFromRowFactory = array( 'AmazonS3File', 'newFromRow' );
	var $oldFileFromRowFactory = array( 'CloudStorageFileArchive', 'newFromRow' );
	
	function __construct( $info ) {
		parent::__construct( $info );
		
		$this->hashLevels = isset( $info['hashLevels'] ) ? $info['hashLevels'] : 2;
		$this->deletedHashLevels = isset( $info['deletedHashLevels'] ) ?
		$info['deletedHashLevels'] : $this->hashLevels;
		$this->deletedDir = isset( $info['deletedDir'] ) ? $info['deletedDir'] : false;
		$this->fileMode = isset( $info['fileMode'] ) ? $info['fileMode'] : 0644;
		if ( isset( $info['thumbDir'] ) ) {
			$this->thumbDir =  $info['thumbDir'];
		} else {
			$this->thumbDir = "{$this->directory}/thumb";
		}
		$this->urlbase = $info['urlbase'];
		
		if (!$this->directory)
			$this->directory = isset( $info['directory'] ) ? $info['directory'] : "http://s3.amazonaws.com/$wgUploadS3Bucket/$wgCloudStorageDirectory";
		
		if ( isset( $info['thumbUrl'] ) ) {
			$this->thumbUrl = $info['thumbUrl'];
		} else {
			$this->thumbUrl = "{$this->url}/thumb";
		}
		
		$this->CSS_ACCESS_KEY = $info['AWS_ACCESS_KEY'];
		$this->CSS_SECRET_KEY = $info['AWS_SECRET_KEY'];
		$this->bucketName = $info['AWS_S3_BUCKET'];
		global $wgCss;
		$wgCss->setAuth($this->CSS_ACCESS_KEY, $this->CSS_SECRET_KEY);
		
		// Optional settings
		$this->CSS_PUBLIC = isset( $info['AWS_S3_PUBLIC'] ) ? $info['AWS_S3_PUBLIC'] : false;
		$wgCss->useSSL = $this->CSS_SSL = isset( $info['AWS_S3_SSL'] ) ? $info['AWS_S3_SSL'] : true;
		$this->url = isset( $info['url'] ) ? $info['url'] :
		($this->CSS_SSL ? "https://" : "http://") . "s3.amazonaws.com/" .
		$this->bucketName . "/" . $this->directory;
	}

	/**
	 * Get the S3 directory corresponding to one of the three basic zones
	 */
	function getZonePath( $zone ) {
		switch ( $zone ) {
			case 'public':
				return $this->directory;
			case 'temp':
				return "{$this->directory}/temp";
			case 'deleted':
				return $this->deletedDir;
			case 'thumb':
				return $this->thumbDir;
			default:
				return false;
		}
	}
	
	function newFileFromRow( $row ) {
		if ( isset( $row->img_name ) ) {
			return call_user_func( $this->fileFromRowFactory, $row, $this );
		} elseif ( isset( $row->oi_name ) ) {
			return call_user_func( $this->oldFileFromRowFactory, $row, $this );
		} else {
			throw new MWException( __METHOD__.': invalid row' );
		}
	}

	function newFromArchiveName( $title, $archiveName ) {
		return CloudStorageFileArchive::newFromArchiveName( $title, $this, $archiveName );
	}

	/**
	 * Delete files in the deleted directory if they are not referenced in the
	 * filearchive table. This needs to be done in the repo because it needs to
	 * interleave database locks with file operations, which is potentially a
	 * remote operation.
	 * @return FileRepoStatus
	 */
	function cleanupDeletedBatch( $storageKeys ) {
		$root = $this->getZonePath( 'deleted' );
		$dbw = $this->getMasterDB();
		$status = $this->newGood();
		$storageKeys = array_unique($storageKeys);
		foreach ( $storageKeys as $key ) {
			$hashPath = $this->getDeletedHashPath( $key );
			$path = "$root/$hashPath$key";
			$dbw->begin();
			$inuse = $dbw->selectField( 'filearchive', '1',
				array( 'fa_storage_group' => 'deleted', 'fa_storage_key' => $key ),
				__METHOD__, array( 'FOR UPDATE' ) );
			if( !$inuse ) {
				$sha1 = substr( $key, 0, strcspn( $key, '.' ) );
				$ext = substr( $key, strcspn($key,'.') + 1 );
				$ext = File::normalizeExtension($ext);
				$inuse = $dbw->selectField( 'oldimage', '1',
					array( 'oi_sha1' => $sha1,
						'oi_archive_name ' . $dbw->buildLike( $dbw->anyString(), ".$ext" ),
						'oi_deleted & ' . File::DELETED_FILE => File::DELETED_FILE ),
					__METHOD__, array( 'FOR UPDATE' ) );
			}
			if ( !$inuse ) {
				wfDebug( __METHOD__ . ": deleting $key\n" );
				if ( !@unlink( $path ) ) {
					$status->error( 'undelete-cleanup-error', $path );
					$status->failCount++;
				}
			} else {
				wfDebug( __METHOD__ . ": $key still in use\n" );
				$status->successCount++;
			}
			$dbw->commit();
		}
		return $status;
	}
	
	/**
	 * Checks if there is a redirect named as $title
	 *
	 * @param $title Title of file
	 */
	function checkRedirect( $title ) {
		global $wgMemc;

		if( is_string( $title ) ) {
			$title = Title::newFromTitle( $title );
		}
		if( $title instanceof Title && $title->getNamespace() == NS_MEDIA ) {
			$title = Title::makeTitle( NS_FILE, $title->getText() );
		}

		$memcKey = $this->getSharedCacheKey( 'image_redirect', md5( $title->getDBkey() ) );
		if ( $memcKey === false ) {
			$memcKey = $this->getLocalCacheKey( 'image_redirect', md5( $title->getDBkey() ) );
			$expiry = 300; // no invalidation, 5 minutes
		} else {
			$expiry = 86400; // has invalidation, 1 day
		}
		$cachedValue = $wgMemc->get( $memcKey );
		if ( $cachedValue === ' '  || $cachedValue === '' ) {
			// Does not exist
			return false;
		} elseif ( strval( $cachedValue ) !== '' ) {
			return Title::newFromText( $cachedValue, NS_FILE );
		} // else $cachedValue is false or null: cache miss

		$id = $this->getArticleID( $title );
		if( !$id ) {
			$wgMemc->set( $memcKey, " ", $expiry );
			return false;
		}
		$dbr = $this->getSlaveDB();
		$row = $dbr->selectRow(
			'redirect',
			array( 'rd_title', 'rd_namespace' ),
			array( 'rd_from' => $id ),
			__METHOD__
		);

		if( $row && $row->rd_namespace == NS_FILE ) {
			$targetTitle = Title::makeTitle( $row->rd_namespace, $row->rd_title );
			$wgMemc->set( $memcKey, $targetTitle->getDBkey(), $expiry );
			return $targetTitle;
		} else {
			$wgMemc->set( $memcKey, '', $expiry );
			return false;
		}
	}


	/**
	 * Function link Title::getArticleID().
	 * We can't say Title object, what database it should use, so we duplicate that function here.
	 */
	protected function getArticleID( $title ) {
		if( !$title instanceof Title ) {
			return 0;
		}
		$dbr = $this->getSlaveDB();
		$id = $dbr->selectField(
			'page',	// Table
			'page_id',	//Field
			array(	//Conditions
				'page_namespace' => $title->getNamespace(),
				'page_title' => $title->getDBkey(),
			),
			__METHOD__	//Function name
		);
		return $id;
	}

	/**
	 * Get an array or iterator of file objects for files that have a given 
	 * SHA-1 content hash.
	 */
	function findBySha1( $hash ) {
		$dbr = $this->getSlaveDB();
		$res = $dbr->select(
			'image',
			CloudStorageFile::selectFields(),
			array( 'img_sha1' => $hash )
		);
		
		$result = array();
		while ( $row = $res->fetchObject() )
			$result[] = $this->newFileFromRow( $row );
		$res->free();
		return $result;
	}

	/**
	 * Get a connection to the slave DB
	 */
	function getSlaveDB() {
		return wfGetDB( DB_SLAVE );
	}

	/**
	 * Get a connection to the master DB
	 */
	function getMasterDB() {
		return wfGetDB( DB_MASTER );
	}

	/**
	 * Get a key on the primary cache for this repository.
	 * Returns false if the repository's cache is not accessible at this site. 
	 * The parameters are the parts of the key, as for wfMemcKey().
	 */
	function getSharedCacheKey( /*...*/ ) {
		$args = func_get_args();
		return call_user_func_array( 'wfMemcKey', $args );
	}

	/**
	 * Invalidates image redirect cache related to that image
	 *
	 * @param $title Title of page
	 */
	function invalidateImageRedirect( $title ) {
		global $wgMemc;
		$memcKey = $this->getSharedCacheKey( 'image_redirect', md5( $title->getDBkey() ) );
		if ( $memcKey ) {
			$wgMemc->delete( $memcKey );
		}
	}
	
	/**
	 * Store a batch of files from local (i.e. Windows or Linux) filesystem to S3
	 *
	 * @param $triplets Array: (src,zone,dest) triplets as per store()
	 * @param $flags Integer: bitwise combination of the following flags:
	 *     self::DELETE_SOURCE     Delete the source file after upload
	 *     self::OVERWRITE         Overwrite an existing destination file instead of failing
	 *     self::OVERWRITE_SAME    Overwrite the file if the destination exists and has the
	 *                             same contents as the source (not implemented in S3)
	 */
	function storeBatch( $triplets, $flags = 0 ) {
		wfDebug(__METHOD__." triplets: ".print_r($triplets,true)."flags: ".print_r($flags)."\n");
		global $wgCss;
		$status = $this->newGood();
		foreach ( $triplets as $i => $triplet ) {
			list( $srcPath, $dstZone, $dstRel ) = $triplet;
	
			$root = $this->getZonePath( $dstZone );
			if ( !$root ) {
				throw new MWException( "Invalid zone: $dstZone" );
			}
			if ( !$this->validateFilename( $dstRel ) ) {
				throw new MWException( 'Validation error in $dstRel' );
			}
			$dstPath = "$root/$dstRel";
	
			if ( self::isVirtualUrl( $srcPath ) ) {
				$srcPath = $triplets[$i][0] = $this->resolveVirtualUrl( $srcPath );
			}
			$s3path = $srcPath;
			$info = $wgCss->getObjectInfo($this->bucketName, $s3path);
			if ( ! $info && !is_file( $srcPath ) ) { // check both local system and S3
				// Make a list of files that don't exist for return to the caller
				$status->fatal( 'filenotfound', $srcPath );
				continue;
			}
			$s3path = $dstPath;
			$info = $wgCss->getObjectInfo($this->bucketName, $s3path);
			wfDebug(__METHOD__."(validation) s3path-dest: $s3path\ninfo:".print_r($info,true)."\n");
					if ( !( $flags & self::OVERWRITE ) && $info ) {
					$status->fatal( 'fileexistserror', $dstPath );
					}
					}
	
					$deleteDest = wfIsWindows() && ( $flags & self::OVERWRITE );
	
					// Abort now on failure
					if ( !$status->ok ) {
						return $status;
					}
	
					foreach ( $triplets as $triplet ) {
						list( $srcPath, $dstZone, $dstRel ) = $triplet;
						$root = $this->getZonePath( $dstZone );
						$dstPath = "$root/$dstRel";
						$good = true;
	
						if ( $flags & self::DELETE_SOURCE ) {
							wfDebug(__METHOD__."(delete): dstPath: $dstPath, ".print_r($triplet,true));
							if ( $deleteDest ) {
								if(! $wgCss->deleteObject($this->bucketName, $dstPath)) {
									wfDebug(__METHOD__.": FAILED - delete: $dstPath");
								}
							}
							$info = $wgCss->getObjectInfo($this->bucketName, $srcPath);
							if ( ! $info ) { // local file
								if ( ! $wgCss->putObjectFile($srcPath, $this->bucketName, $dstPath,
										($this->CSS_PUBLIC ? AmazonS3Service::ACL_PUBLIC_READ : AmazonS3Service::ACL_PRIVATE))) {
											$status->error( 'filecopyerror', $srcPath, $dstPath );
											$good = false;
										}
										unlink( $srcPath );
							} else { // s3 file
								if ( ! $wgCss->copyObject($this->bucketName, $srcPath,
										$this->bucketName, $dstPath,
							   ($this->CSS_PUBLIC ? AmazonS3Service::ACL_PUBLIC_READ : AmazonS3Service::ACL_PRIVATE)) &&
										! $wgCss->deleteObject($this->bucketName, $srcPath)) {
											$status->error( 'filecopyerror', $srcPath, $dstPath );
											$good = false;
										}
							}
						} else {
							wfDebug(__METHOD__."(transfer): dstPath: $dstPath, ".print_r($triplet,true));
							$info = $wgCss->getObjectInfo($this->bucketName, $srcPath);
							if ( ! $info ) { // local file
								if ( ! $wgCss->putObjectFile($srcPath, $this->bucketName, $dstPath,
										($this->CSS_PUBLIC ? AmazonS3Service::ACL_PUBLIC_READ : AmazonS3Service::ACL_PRIVATE))) {
											$status->error( 'filecopyerror', $srcPath, $dstPath );
											$good = false;
										}
										unlink( $srcPath );
							} else { // s3 file
								if ( ! $wgCss->copyObject($this->bucketName, $srcPath,
										$this->bucketName, $dstPath,
							   ($this->CSS_PUBLIC ? AmazonS3Service::ACL_PUBLIC_READ : AmazonS3Service::ACL_PRIVATE)) &&
										! $wgCss->deleteObject($this->bucketName, $srcPath)) {
											$status->error( 'filecopyerror', $srcPath, $dstPath );
											$good = false;
										}
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
	 * Append a file from from local (i.e. Windows or Linux) filesystem to  existing file
	 *
	 * @param $srcPath - file on  filesystem
	 * @param $toAppendPath - file from local filesystem
	 * @param $flags Integer: bitwise combination of the following flags:
	 *     self::DELETE_SOURCE     Delete the toAppend file after append
	 */
	function append( $srcPath, $toAppendPath, $flags = 0 ) {
		global $wgCss;
		$status = $this->newGood();
	
		// Resolve the virtual URL
		if ( self::isVirtualUrl( $srcPath ) ) {
			$srcPath = $this->resolveVirtualUrl( $srcPath );
		}
		// Make sure the files are there
		if ( !is_file( $toAppendPath ) )
			$status->fatal( 'filenotfound', $toAppendPath );
	
		$info = $wgCss->getObjectInfo($this->bucketName, $srcPath);
		if ( ! $info )
			$status->fatal( 'filenotfound', $srcPath );
	
		if ( !$status->isOk() ) return $status;
	
		// Do the append
		$tmpLoc = tempnam(wfTempDir(), "Append");
		if(! $wgCss->getObject($this->bucketName, $srcPath, $tmpLoc)) {
			$status->fatal( 'fileappenderrorread', $srcPath );
		}
		$chunk = file_get_contents( $toAppendPath );
		if( $chunk === false ) {
			$status->fatal( 'fileappenderrorread', $toAppendPath );
		}
	
		if( $status->isOk() ) {
			if ( file_put_contents( $tmpLoc, $chunk, FILE_APPEND ) ) {
				$status->value = $srcPath;
				if ( ! $wgCss->putObjectFile($tmpLoc, $this->bucketName, $srcPath,
						($this->CSS_PUBLIC ? AmazonS3Service::ACL_PUBLIC_READ : AmazonS3Service::ACL_PRIVATE))) {
							$status->fatal( 'fileappenderror', $toAppendPath,  $srcPath);
						}
			} else {
				$status->fatal( 'fileappenderror', $toAppendPath,  $srcPath);
			}
		}
	
		if ( $flags & self::DELETE_SOURCE ) {
			unlink( $toAppendPath );
		}
	
		return $status;
	}
	
	/**
	 * Publish a batch of files
	 * @param $triplets Array: (source,dest,archive) triplets as per publish()
	 *        source can be on local machine or on S3, dest must be on S3
	 * @param $flags Integer: bitfield, may be FileRepo::DELETE_SOURCE to indicate
	 *        that the source files should be deleted if possible
	 */
	function publishBatch( $triplets, $flags = 0 ) {
		// Perform initial checks
		wfDebug(__METHOD__.": ".print_r($triplets,true));
		global $wgCss;
		$status = $this->newGood( array() );
		foreach ( $triplets as $i => $triplet ) {
			list( $srcPath, $dstRel, $archiveRel ) = $triplet;
	
			if ( substr( $srcPath, 0, 9 ) == 'mwrepo://' ) {
				$triplets[$i][0] = $srcPath = $this->resolveVirtualUrl( $srcPath );
			}
			if ( !$this->validateFilename( $dstRel ) ) {
				throw new MWException( 'Validation error in $dstRel' );
			}
			if ( !$this->validateFilename( $archiveRel ) ) {
				throw new MWException( 'Validation error in $archiveRel' );
			}
			$dstPath = "{$this->directory}/$dstRel";
			$archivePath = "{$this->directory}/$archiveRel";
	
			$dstDir = dirname( $dstPath );
			$archiveDir = dirname( $archivePath );
			$infoS3  = $wgCss->getObjectInfo($this->bucketName, $srcPath); // see if on S3
			$infoLoc = is_file( $srcPath ); // see if local file
			wfDebug(__METHOD__."(validation) srcPath: $srcPath, infoLoc: $infoLoc, infoS3:".print_r($infoS3,true)."\n");
			if ( ! $infoS3 && ! $infoLoc ) {
				// Make a list of files that don't exist for return to the caller
				$status->fatal( 'filenotfound', $srcPath );
			}
		}
	
		if ( !$status->ok ) {
			return $status;
		}
	
		foreach ( $triplets as $i => $triplet ) {
			list( $srcPath, $dstRel, $archiveRel ) = $triplet;
			$dstPath = "{$this->directory}/$dstRel";
			$archivePath = "{$this->directory}/$archiveRel";
	
			// Archive destination file if it exists
			$info = $wgCss->getObjectInfo($this->bucketName, $dstPath);
			wfDebug(__METHOD__."(transfer) dstPath: $dstPath, info:".print_r($info,true)."\n");
			if( $info ) {
				// Check if the archive file exists
				// This is a sanity check to avoid data loss. In UNIX, the rename primitive
				// unlinks the destination file if it exists. DB-based synchronisation in
				// publishBatch's caller should prevent races. In Windows there's no
				// problem because the rename primitive fails if the destination exists.
				if ( is_file( $archivePath ) ) {
					$success = false;
				} else 				// Check if the archive file exists
					// This is a sanity check to avoid data loss. In UNIX, the rename primitive
					// unlinks the destination file if it exists. DB-based synchronisation in
					// publishBatch's caller should prevent races. In Windows there's no
					// problem because the rename primitive fails if the destination exists.
					$path = /*$this->directory/*AWS_S3_FOLDER .*/ $archivePath;
				$info = $wgCss->getObjectInfo($this->bucketName, $path);
				wfDebug(__METHOD__."(file exists): $path, info:".print_r($info,true)."\n");
				if ( $info /*is_file( $archivePath )*/ ) {
					$success = false;
				} else {
					wfDebug(__METHOD__.": moving file $dstPath to $archivePath\n");
					if(! (
							$wgCss->copyObject($this->bucketName, $dstPath,
									$this->bucketName, $archivePath,
									($this->CSS_PUBLIC ? S3::ACL_PUBLIC_READ : S3::ACL_PRIVATE)) &&
							$wgCss->deleteObject($this->bucketName, $dstPath))
					) {
						wfDebug(__METHOD__.": FAILED moving file $dstPath to $archivePath\n");
						$success = false;
					} else {
						$success = true;
					}
				}
	
	
				if( !$success ) {
					$status->error( 'filerenameerror',$dstPath, $archivePath );
					$status->failCount++;
					continue;
				} else {
					wfDebug(__METHOD__.": moved file $dstPath to $archivePath\n");
				}
				$status->value[$i] = 'archived';
			} else {
				$status->value[$i] = 'new';
			}
	
			$good = true;
			wfSuppressWarnings();
			if(! is_file( $srcPath )) {
				// S3
				if(! $wgCss->copyObject($this->bucketName, $srcPath, $this->bucketName,
						$this->AWS_S3_FOLDER . $dstPath, ($this->CSS_PUBLIC ? S3::ACL_PUBLIC_READ : S3::ACL_PRIVATE))) {
							wfDebug(__METHOD__.": FAILED - copy: $srcPath to $dstPath");
						}
						//$css->putObjectFile($srcPath, $this->AWS_S3_BUCKET, $this->directory/*AWS_S3_FOLDER*/ . $dstPath, ($this->AWS_S3_PUBLIC ? S3::ACL_PUBLIC_READ : S3::ACL_PRIVATE));
						if ( $flags & self::DELETE_SOURCE ) {
							if(! $wgCss->deleteObject($this->bucketName, /*$this->directory/*AWS_S3_FOLDER .*/ $srcPath)) {
								wfDebug(__METHOD__.": FAILED - delete: $srcPath");
							}
						}
			} else {
				// Local file
				if(! $wgCss->putObjectFile($srcPath, $this->bucketName, $dstPath,
						($this->CSS_PUBLIC ? S3::ACL_PUBLIC_READ : S3::ACL_PRIVATE))) {
							$status->error( 'filecopyerror', $srcPath, $dstPath );
							$good = false;
						}
						if ( $flags & self::DELETE_SOURCE ) {
							unlink($srcPath);
						}
			}
			wfRestoreWarnings();
	
			if ( $good ) {
				$status->successCount++;
				wfDebug(__METHOD__.": wrote tempfile $srcPath to $dstPath\n");
			} else {
				$status->failCount++;
			}
		}
		return $status;
	}
	
	/**
	 * Checks existence of specified array of files.
	 *
	 * @param $files Array: URLs of files to check
	 * @param $flags Integer: bitwise combination of the following flags:
	 *     self::FILES_ONLY     Mark file as existing only if it is a file (not directory)
	 *     Will mark all items found on S3 as true, no directory concept exists on the S3
	 * @return Either array of files and existence flags, or false
	 */
	function fileExistsBatch( $files, $flags = 0 ) {
		global $wgCss;
		$result = array();
		foreach ( $files as $key => $file ) {
			if ( self::isVirtualUrl( $file ) ) {
				$file = $this->resolveVirtualUrl( $file );
			}
			$info = $wgCss->getObjectInfo($this->bucketName, $file);
			$result[$key] = ($info ? true : false) ;
			// if( $flags & self::FILES_ONLY ) {
			// $result[$key] = is_file( $file );
			// } else {
			// $result[$key] = file_exists( $file );
			// }
		}
	
		return $result;
	}
}

