<?php

class AmazonS3File extends CloudStorageFile {
	
// 	protected $repoClass = 'AmazonS3Repo';
	
	public function __construct($title, $repo) {
		parent::__construct($title, $repo);
		$repoClass = 'AmazonS3Repo';
	}
	
	public static function getAuthenticatedURL($bucket, $uri, $lifetime, $hostBucket = false, $https = false) {
		global $s3;
		return $s3->getAuthenticatedURL($bucket, $uri, $lifetime, $hostBucket, $https);
	}
	
	/**
	 * Return the complete URL of the file
	 */
	public function getUrl() {
		return $this->url;
	}
	
	public function getPath( $forceExist=true ) {
		return $this->tempPath;
	}
	
	function getThumbPath( $suffix = false ) {
		return parent::getThumbPath($suffix);
	}
	
	/** Get the URL of the archive directory, or a particular file if $suffix is specified */
	function getArchiveUrl( $suffix = false ) {
		wfDebug( __METHOD__ . " suffix: $suffix, url:".print_r($this->url, true)."\n" );
		if ( $suffix === false ) {
			$path = $this->repo->getZoneUrl('public') . '/archive/' . $this->getHashPath();
			$path = substr( $path, 0, -1 );
		} else {
			$path = '/archive/' . $this->getHashPath($suffix) . $suffix;
			if(! $this->repo->AWS_S3_PUBLIC) {
				$path = self::getAuthenticatedURL($this->repo->AWS_S3_BUCKET, $this->repo->getZonePath('public') . $path, 60*60*24*7 /*week*/, false, $this->repo->AWS_S3_SSL);
			} else {
				$path = $this->repo->url . $path;
			}
		}
		wfDebug( __METHOD__ . " return: $path \n".print_r($this,true)."\n" );
		return $path;
	}
	
	function transform( $params, $flags = 0 ) {
		global $wgUseSquid, $wgIgnoreImageErrors;
		global $s3;
		wfDebug( __METHOD__ . ": ".print_r($params,true)."\n" );
		
		wfProfileIn( __METHOD__ );
		do {
			if ( !$this->canRender() ) {
				// not a bitmap or renderable image, don't try.
				$thumb = $this->iconThumb();
				break;
			}
		
			$script = $this->getTransformScript();
			if ( $script && !($flags & self::RENDER_NOW) ) {
				// Use a script to transform on client request, if possible
				$thumb = $this->handler->getScriptedTransform( $this, $script, $params );
				if( $thumb ) {
					break;
				}
			}
		
			$normalisedParams = $params;
			$this->handler->normaliseParams( $this, $normalisedParams );
			$thumbName = $this->thumbName( $normalisedParams );
			$thumbPath = $this->getThumbPath( $thumbName );
			$thumbUrl = $this->getThumbUrl( $thumbPath );
			wfDebug( __METHOD__.": thumbName: $thumbName, thumbPath: $thumbPath\n  thumbUrl: $thumbUrl\n" );
		
			if ( $this->repo->canTransformVia404() && !($flags & self::RENDER_NOW ) ) {
				$thumb = $this->handler->getTransform( $this, $thumbPath, $thumbUrl, $params );
				break;
			}
		
			wfDebug( __METHOD__.": Doing stat for $thumbPath\n  ($thumbUrl)\n" );
			$this->migrateThumbFile( $thumbName );
			$info = $s3->getObjectInfo($this->repo->AWS_S3_BUCKET, $thumbPath);
			wfDebug(__METHOD__." thumbPath: $thumbPath\ninfo:".print_r($info,true)."\n");
			if ( $info /*file_exists( $thumbPath )*/ ) {
				$thumb = $this->handler->getTransform( $this, $thumbPath, $thumbUrl, $params );
				break;
			}
			$thumbTempPath = tempnam(wfTempDir(), "s3thumb-");
			$thumb = $this->handler->doTransform( $this, $thumbTempPath, $thumbUrl, $params );
			wfDebug( __METHOD__. " thumb: ".print_r($thumb->url,true)."\n" );
			$s3path = $thumbPath;
			$info = $s3->putObjectFile($thumbTempPath, $this->repo->AWS_S3_BUCKET, $s3path,
					($this->repo->AWS_S3_PUBLIC ? S3::ACL_PUBLIC_READ : S3::ACL_PRIVATE));
			wfDebug(__METHOD__." thumbTempPath: $thumbTempPath, dest: $s3path\ninfo:".print_r($info,true)."\n");
		
			// Ignore errors if requested
			if ( !$thumb ) {
				$thumb = null;
			} elseif ( $thumb->isError() ) {
				$this->lastError = $thumb->toText();
				if ( $wgIgnoreImageErrors && !($flags & self::RENDER_NOW) ) {
					$thumb = $this->handler->getTransform( $this, $thumbPath, $thumbUrl, $params );
				}
			}
				
			// Purge. Useful in the event of Core -> Squid connection failure or squid
			// purge collisions from elsewhere during failure. Don't keep triggering for
			// "thumbs" which have the main image URL though (bug 13776)
			if ( $wgUseSquid && ( !$thumb || $thumb->isError() || $thumb->getUrl() != $this->getURL()) ) {
			SquidUpdate::purge( array( $thumbUrl ) );
			}
			} while (false);
		
			wfProfileOut( __METHOD__ );
			wfDebug( __METHOD__. " return thumb: ".print_r($thumb,true)."\n" );
			return is_object( $thumb ) ? $thumb : false;
	}
	
	/** Get the URL of the thumbnail directory, or a particular file if $suffix is specified.
	 *  $suffix is a path relative to the S3 bucket, and includes the upload directory
	 */
	function getThumbUrl( $suffix = false ) {
		$path = $this->repo->getUrlBase() . "/$suffix";
		if(! $this->repo->AWS_S3_PUBLIC)
			$this->url = self::getAuthenticatedURL($this->repo->AWS_S3_BUCKET,
					$suffix, 60*60*24*7 /*week*/, false,
					$this->repo->AWS_S3_SSL);
		return $path;
	}
	
	public function getPath( $forceExist=true ) {
		global $s3;
		if ( !isset( $this->tempPath ) ) {
			$this->tempPath = tempnam(wfTempDir(), "s3file-");
			$info = $s3->getObject($this->repo->AWS_S3_BUCKET,
					$this->repo->directory . '/'  . $this->getUrlRel(), $this->tempPath);
			if(!$info) $this->tempPath = false;
		}
		return $this->tempPath;
	}
	
	public function getUrl() {
		if ( !isset( $this->url ) ) {
			$this->url = $this->repo->getZoneUrl( 'public' ) . '/' . $this->getUrlRel();
			if(! $this->repo->AWS_S3_PUBLIC)
				$this->url = self::getAuthenticatedURL($this->repo->AWS_S3_BUCKET, $this->repo->directory . '/'  . $this->getUrlRel(), 60*60*24*7 /*week*/, false, $this->repo->AWS_S3_SSL);
		}
		//echo "getUrl(): $this->url <br />";
		wfDebug( __METHOD__ . ": ".print_r($this->url, true)."\n" );
		return $this->url;
	}
}