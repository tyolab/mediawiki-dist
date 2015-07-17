<?php
/**
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *   this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright
 *   notice, this list of conditions and the following disclaimer in the
 *   documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

$file = (isset($wgGaeHome) ? $wgGaeHome : '') . 'google/appengine/api/cloud_storage/CloudStorageTools.php';

require_once $file;
use google\appengine\api\cloud_storage\CloudStorageTools;
use Cdb\Exception;

require_once 'CloudStorageService.php';

/**
 * Google Cloud Storage PHP class
 * 
 * There could be many ways to access the Cloud Storage
 * for example, run from App Engine which requires no authentication, permissions and such
 *
 * @link 
 * @version 
 */
class GoogleCloudStorageService extends CloudStorageService {

	/**
	*
	* @param string $accessKey Access key
	* @param string $secretKey Secret key
	* @param boolean $useSSL Enable SSL
	* @return void
	*/
	public function __construct($accessKey = null, $secretKey = null, $useSSL = true) {
		parent::__construct($accessKey, $secretKey, $useSSL);
	}
	
	public function getImageServingUrl($path) {
		global $wgImageNotFound, $wgRunOnNonGae;
		$object_image_file = $path;
		$object_image_url = $wgImageNotFound;
		$pos = strpos($path, 'images');
		if ($pos >= 0) {
			$imagePathFile = substr($path, $pos);
			$object_image_file = $this->getBucketUrl() . $imagePathFile;
		}
		
		if ( !$wgRunOnNonGae && !file_exists($object_image_file) ) {
			wfDebug( __METHOD__ . ": $object_image_file doesn't exist.\n" );
			
			// TODO
			// specify a 404 image here
			
		}
		else {
			try {
				$object_image_url = CloudStorageTools::getImageServingUrl($object_image_file);
			}
			catch (Exception $e) {
				$object_image_url = $path;
			}
		}
		return $object_image_url;
	}
	
	public function getBucketUrl() {
		return $this->protocol . '://' . $this->bucketName . '/';
	}
	
	/**
	* Get a list of buckets
	* 
	* TODO
	* not implements yet
	*
	* @param boolean $detailed Returns detailed bucket list when true
	* @return array | false
	*/
	public function listBuckets($detailed = false) {
		
		$results['buckets'] = array();
		$results['buckets'][] = array(
				'name' => '#default#', 'time' => ''
		);

		return $results;
	}


	/*
	* Get contents for a bucket
	*
	* If maxKeys is null this method will loop through truncated result sets
	*
	* @param string $bucket Bucket name
	* @param string $prefix Prefix
	* @param string $marker Marker (last file listed)
	* @param string $maxKeys Max keys (maximum number of keys to return)
	* @param string $delimiter Delimiter
	* @param boolean $returnCommonPrefixes Set to true to return CommonPrefixes
	* @return array | false
	*/
	public function getBucket($bucket, $prefix = null, $marker = null, $maxKeys = null, $delimiter = null, $returnCommonPrefixes = false) {
		
		$results['#default#'] = array(
				'name' => '#default#',
				'time' => '',
				'size' => '',
				'hash' => ''
		);
		

		return $results;
	}


	/**
	* Put a bucket
	*
	* @param string $bucket Bucket name
	* @param constant $acl ACL flag
	* @param string $location Set as "EU" to create buckets hosted in Europe
	* @return boolean
	*/
	public function putBucket($bucket, $acl = self::ACL_PRIVATE, $location = false) {
		return true;
	}


	/**
	* Delete an empty bucket
	*
	* @param string $bucket Bucket name
	* @return boolean
	*/
	public function deleteBucket($bucket) {
		return true;
	}


	/**
	* Create input info array for putObject()
	*
	* @param string $file Input file
	* @param mixed $md5sum Use MD5 hash (supply a string if you want to use your own)
	* @return array | false
	*/
	public function inputFile($file, $md5sum = true) {
		if (!file_exists($file) || !is_file($file) || !is_readable($file)) {
			trigger_error('inputFile(): Unable to open input file: '.$file, E_USER_WARNING);
			return false;
		}
		return array('file' => $file, 'size' => filesize($file),
		'md5sum' => $md5sum !== false ? (is_string($md5sum) ? $md5sum :
		base64_encode(md5_file($file, true))) : '');
	}


	/**
	* Create input array info for putObject() with a resource
	*
	* @param string $resource Input resource to read from
	* @param integer $bufferSize Input byte size
	* @param string $md5sum MD5 hash to send (optional)
	* @return array | false
	*/
	public function inputResource(&$resource, $bufferSize, $md5sum = '') {
		if (!is_resource($resource) || $bufferSize < 0) {
			trigger_error('inputResource(): Invalid resource or buffer size', E_USER_WARNING);
			return false;
		}
		$input = array('size' => $bufferSize, 'md5sum' => $md5sum);
		$input['fp'] =& $resource;
		return $input;
	}


	/**
	* Put an object
	*
	* @param mixed $input Input data
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param constant $acl ACL constant
	* @param array $metaHeaders Array of x-amz-meta-* headers
	* @param array $requestHeaders Array of request headers or content type as a string
	* @return boolean
	*/
	public function putObject($input, $bucket, $uri, $acl = self::ACL_PRIVATE, $metaHeaders = array(), $requestHeaders = array()) {
		return true;
	}


	/**
	* Put an object from a file (legacy function)
	*
	* @param string $file Input file path
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param constant $acl ACL constant
	* @param array $metaHeaders Array of x-amz-meta-* headers
	* @param string $contentType Content type
	* @return boolean
	*/
	public function putObjectFile($file, $bucket, $uri, $acl = self::ACL_PRIVATE, $metaHeaders = array(), $contentType = null) {
		return self::putObject(self::inputFile($file), $bucket, $uri, $acl, $metaHeaders, $contentType);
	}


	/**
	* Put an object from a string (legacy function)
	*
	* @param string $string Input data
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param constant $acl ACL constant
	* @param array $metaHeaders Array of x-amz-meta-* headers
	* @param string $contentType Content type
	* @return boolean
	*/
	public function putObjectString($string, $bucket, $uri, $acl = self::ACL_PRIVATE, $metaHeaders = array(), $contentType = 'text/plain') {
		return self::putObject($string, $bucket, $uri, $acl, $metaHeaders, $contentType);
	}


	/**
	* Get an object
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param mixed $saveTo Filename or resource to write to
	* @return mixed
	*/
	public function getObject($bucket, $uri, $saveTo = false) {
		return false;
	}


	/**
	* Get object information
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param boolean $returnInfo Return response information
	* @return mixed | false
	*/
	public function getObjectInfo($bucket, $uri, $returnInfo = true) {
		return false;
	}


	/**
	* Copy an object
	*
	* @param string $bucket Source bucket name
	* @param string $uri Source object URI
	* @param string $bucket Destination bucket name
	* @param string $uri Destination object URI
	* @param constant $acl ACL constant
	* @param array $metaHeaders Optional array of x-amz-meta-* headers
	* @param array $requestHeaders Optional array of request headers (content type, disposition, etc.)
	* @return mixed | false
	*/
	public function copyObject($srcBucket, $srcUri, $bucket, $uri, $acl = self::ACL_PRIVATE, $metaHeaders = array(), $requestHeaders = array()) {
		return false;
	}


	/**
	* Set logging for a bucket
	*
	* @param string $bucket Bucket name
	* @param string $targetBucket Target bucket (where logs are stored)
	* @param string $targetPrefix Log prefix (e,g; domain.com-)
	* @return boolean
	*/
	public function setBucketLogging($bucket, $targetBucket, $targetPrefix = null) {
		return true;
	}


	/**
	* Get logging status for a bucket
	*
	* This will return false if logging is not enabled.
	* Note: To enable logging, you also need to grant write access to the log group
	*
	* @param string $bucket Bucket name
	* @return array | false
	*/
	public function getBucketLogging($bucket) {
		return array();
	}


	/**
	* Disable bucket logging
	*
	* @param string $bucket Bucket name
	* @return boolean
	*/
	public function disableBucketLogging($bucket) {
		return self::setBucketLogging($bucket, null);
	}


	/**
	* Get a bucket's location
	*
	* @param string $bucket Bucket name
	* @return string | false
	*/
	public function getBucketLocation($bucket) {
		return 'US';
	}


	/**
	* Set object or bucket Access Control Policy
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param array $acp Access Control Policy Data (same as the data returned from getAccessControlPolicy)
	* @return boolean
	*/
	public function setAccessControlPolicy($bucket, $uri = '', $acp = array()) {
		return true;
	}


	/**
	* Get object or bucket Access Control Policy
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @return mixed | false
	*/
	public function getAccessControlPolicy($bucket, $uri = '') {
		$acp = array();
		return $acp;
	}


	/**
	* Delete an object
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @return boolean
	*/
	public function deleteObject($bucket, $uri) {
		return true;
	}


	/**
	* Get a query string authenticated URL
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param integer $lifetime Lifetime in seconds
	* @param boolean $hostBucket Use the bucket name as the hostname
	* @param boolean $https Use HTTPS ($hostBucket should be false for SSL verification)
	* @return string
	*/
	public function getAuthenticatedURL($bucket, $uri, $lifetime, $hostBucket = false, $https = false) {
		return __METHOD__ . " not implemented yet.";
	}

	/**
	* Create a CloudFront distribution
	*
	* @param string $bucket Bucket name
	* @param boolean $enabled Enabled (true/false)
	* @param array $cnames Array containing CNAME aliases
	* @param string $comment Use the bucket name as the hostname
	* @return array | false
	*/
	public function createDistribution($bucket, $enabled = true, $cnames = array(), $comment = '') {
		return false;
	}


	/**
	* Get CloudFront distribution info
	*
	* @param string $distributionId Distribution ID from listDistributions()
	* @return array | false
	*/
	public function getDistribution($distributionId) {
		return false;
	}


	/**
	* Update a CloudFront distribution
	*
	* @param array $dist Distribution array info identical to output of getDistribution()
	* @return array | false
	*/
	public function updateDistribution($dist) {
		return false;
	}


	/**
	* Delete a CloudFront distribution
	*
	* @param array $dist Distribution array info identical to output of getDistribution()
	* @return boolean
	*/
	public function deleteDistribution($dist) {
		return true;
	}


	/**
	* Get a list of CloudFront distributions
	*
	* @return array
	*/
	public function listDistributions() {
		return array();
	}

}
