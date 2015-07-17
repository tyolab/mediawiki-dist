<?php

class CloudStorageService {
	
	// ACL flags
	const ACL_PRIVATE = 'private';
	const ACL_PUBLIC_READ = 'public-read';
	const ACL_PUBLIC_READ_WRITE = 'public-read-write';
	const ACL_AUTHENTICATED_READ = 'authenticated-read';
	
	public $useSSL = true;
	
	protected $bucketName;
	protected $protocol;
	
	private $__accessKey; // Access key
	private $__secretKey; // Secret key
	
	
	/**
	 * Constructor - if you're not using the class statically
	 *
	 * @param string $accessKey Access key
	 * @param string $secretKey Secret key
	 * @param boolean $useSSL Enable SSL
	 * @return void
	 */
	public function __construct($accessKey = null, $secretKey = null, $useSSL = true) {
		if ($accessKey !== null && $secretKey !== null)
			setAuth($accessKey, $secretKey);
		$this->useSSL = $useSSL;
	}
	
	public function setProtocol($protocol) {
		$this->protocol = $protocol;
	}
	
	public function setBucketName($bucketName) {
		$this->bucketName = $bucketName;
	}
	
	/**
	 * Set AWS access key and secret key
	 *
	 * @param string $accessKey Access key
	 * @param string $secretKey Secret key
	 * @return void
	 */
	public function setAuth($accessKey, $secretKey) {
		$this->__accessKey = $accessKey;
		$this->__secretKey = $secretKey;
	}
	
	public function getImageServingUrlFromImageFile($imageFile) {
		// virtual
		return getImageServingUrl($imageFile.getPath());
	}
	
	public function getImageServingUrl($path) {
		// virtual
		return path;
	}
	
	public function getBucketUrl() {
		return __METHOD__ . " is a virtual function";
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
		return false;
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
		$results = array();
	
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
		return false;
	}
	
	
	/**
	 * Delete an empty bucket
	 *
	 * @param string $bucket Bucket name
	 * @return boolean
	 */
	public function deleteBucket($bucket) {
		return false;
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
		return false;
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
		return $rest->response;
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
		return false;
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
		return false;
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
		return false;
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
		return 'getAuthenticatedURL() not implemented.';
	}
	
	/**
	 * Get upload POST parameters for form uploads
	 *
	 * @param string $bucket Bucket name
	 * @param string $uriPrefix Object URI prefix
	 * @param constant $acl ACL constant
	 * @param integer $lifetime Lifetime in seconds
	 * @param integer $maxFileSize Maximum filesize in bytes (default 5MB)
	 * @param string $successRedirect Redirect URL or 200 / 201 status code
	 * @param array $amzHeaders Array of x-amz-meta-* headers
	 * @param array $headers Array of request headers or content type as a string
	 * @param boolean $flashVars Includes additional "Filename" variable posted by Flash
	 * @return object
	 */
	public function getHttpUploadPostParams($bucket, $uriPrefix = '', $acl = self::ACL_PRIVATE, $lifetime = 3600, $maxFileSize = 5242880, $successRedirect = "201", $amzHeaders = array(), $headers = array(), $flashVars = false) {
		// Create policy object
		$policy = new stdClass;
		$policy->expiration = gmdate('Y-m-d\TH:i:s\Z', (time() + $lifetime));
		$policy->conditions = array();
		$obj = new stdClass; $obj->bucket = $bucket; array_push($policy->conditions, $obj);
		$obj = new stdClass; $obj->acl = $acl; array_push($policy->conditions, $obj);
	
		$obj = new stdClass; // 200 for non-redirect uploads
		if (is_numeric($successRedirect) && in_array((int)$successRedirect, array(200, 201)))
			$obj->success_action_status = (string)$successRedirect;
		else // URL
			$obj->success_action_redirect = $successRedirect;
		array_push($policy->conditions, $obj);
	
		array_push($policy->conditions, array('starts-with', '$key', $uriPrefix));
		if ($flashVars) array_push($policy->conditions, array('starts-with', '$Filename', ''));
		foreach (array_keys($headers) as $headerKey)
			array_push($policy->conditions, array('starts-with', '$'.$headerKey, ''));
		foreach ($amzHeaders as $headerKey => $headerVal) {
			$obj = new stdClass; $obj->{$headerKey} = (string)$headerVal; array_push($policy->conditions, $obj);
		}
		array_push($policy->conditions, array('content-length-range', 0, $maxFileSize));
		$policy = base64_encode(str_replace('\/', '/', json_encode($policy)));
	
		// Create parameters
		$params = new stdClass;
		$params->CSSAccessKeyId = self::$__accessKey;
		$params->key = $uriPrefix.'${filename}';
		$params->acl = $acl;
		$params->policy = $policy; unset($policy);
		$params->signature = self::__getHash($params->policy);
		if (is_numeric($successRedirect) && in_array((int)$successRedirect, array(200, 201)))
			$params->success_action_status = (string)$successRedirect;
		else
			$params->success_action_redirect = $successRedirect;
		foreach ($headers as $headerKey => $headerVal) $params->{$headerKey} = (string)$headerVal;
		foreach ($amzHeaders as $headerKey => $headerVal) $params->{$headerKey} = (string)$headerVal;
		return $params;
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
	
	
	/**
	 * Get a DistributionConfig DOMDocument
	 *
	 * @internal Used to create XML in createDistribution() and updateDistribution()
	 * @param string $bucket Origin bucket
	 * @param boolean $enabled Enabled (true/false)
	 * @param string $comment Comment to append
	 * @param string $callerReference Caller reference
	 * @param array $cnames Array of CNAME aliases
	 * @return string
	 */
	public function getCloudFrontDistributionConfigXML($bucket, $enabled, $comment, $callerReference = '0', $cnames = array()) {
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;
		return $dom->saveXML();
	}
	
	
	/**
	 * Parse a CloudFront distribution config
	 *
	 * @internal Used to parse the CloudFront DistributionConfig node to an array
	 * @param object &$node DOMNode
	 * @return array
	 */
	function parseCloudFrontDistributionConfig(&$node) {
		$dist = array();
		return $dist;
	}
	
	
	/**
	 * Grab CloudFront response
	 *
	 * @internal Used to parse the CloudFront S3Request::getResponse() output
	 * @param object &$rest S3Request instance
	 * @return object
	 */
	function getCloudFrontResponse(&$rest) {
		return $rest->response;
	}
	
	
	/**
	 * Get MIME type for file
	 *
	 * @internal Used to get mime types
	 * @param string &$file File path
	 * @return string
	 */
	public function __getMimeType(&$file) {
		$type = false;
		// Fileinfo documentation says fileinfo_open() will use the
		// MAGIC env var for the magic file
		if (extension_loaded('fileinfo') && isset($_ENV['MAGIC']) &&
				($finfo = finfo_open(FILEINFO_MIME, $_ENV['MAGIC'])) !== false) {
					if (($type = finfo_file($finfo, $file)) !== false) {
						// Remove the charset and grab the last content-type
						$type = explode(' ', str_replace('; charset=', ';charset=', $type));
						$type = array_pop($type);
						$type = explode(';', $type);
						$type = trim(array_shift($type));
					}
					finfo_close($finfo);
	
					// If anyone is still using mime_content_type()
				} elseif (function_exists('mime_content_type'))
				$type = trim(mime_content_type($file));
	
				if ($type !== false && strlen($type) > 0) return $type;
	
				// Otherwise do it the old fashioned way
				$exts = array(
						'jpg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png',
						'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'ico' => 'image/x-icon',
						'swf' => 'application/x-shockwave-flash', 'pdf' => 'application/pdf',
						'zip' => 'application/zip', 'gz' => 'application/x-gzip',
						'tar' => 'application/x-tar', 'bz' => 'application/x-bzip',
						'bz2' => 'application/x-bzip2', 'txt' => 'text/plain',
						'asc' => 'text/plain', 'htm' => 'text/html', 'html' => 'text/html',
						'css' => 'text/css', 'js' => 'text/javascript',
						'xml' => 'text/xml', 'xsl' => 'application/xsl+xml',
						'ogg' => 'application/ogg', 'mp3' => 'audio/mpeg', 'wav' => 'audio/x-wav',
						'avi' => 'video/x-msvideo', 'mpg' => 'video/mpeg', 'mpeg' => 'video/mpeg',
						'mov' => 'video/quicktime', 'flv' => 'video/x-flv', 'php' => 'text/x-php'
				);
				$ext = strtolower(pathInfo($file, PATHINFO_EXTENSION));
				return isset($exts[$ext]) ? $exts[$ext] : 'application/octet-stream';
	}
	
	
	/**
	 * Generate the auth string: "AWS AccessKey:Signature"
	 *
	 * @internal Used by S3Request::getResponse()
	 * @param string $string String to sign
	 * @return string
	 */
	public function __getSignature($string) {
		return 'AWS '.self::$__accessKey.':'.self::__getHash($string);
	}
	
	
	/**
	 * Creates a HMAC-SHA1 hash
	 *
	 * This uses the hash extension if loaded
	 *
	 * @internal Used by __getSignature()
	 * @param string $string String to sign
	 * @return string
	 */
	private function __getHash($string) {
		return base64_encode(extension_loaded('hash') ?
				hash_hmac('sha1', $string, self::$__secretKey, true) : pack('H*', sha1(
						(str_pad(self::$__secretKey, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) .
						pack('H*', sha1((str_pad(self::$__secretKey, 64, chr(0x00)) ^
								(str_repeat(chr(0x36), 64))) . $string)))));
	}
}

class CloudStorageSerivceRequest {
	
	protected $verb, $bucket, $uri, $resource = '', $parameters = array(),
	$headers = array(
			'Host' => '', 'Date' => '', 'Content-MD5' => '', 'Content-Type' => ''
	);
	
	public $fp = false, $size = 0, $data = false, $response;


	/**
	 * Constructor
	 *
	 * @param string $verb Verb
	 * @param string $bucket Bucket name
	 * @param string $uri Object URI
	 * @return mixed
	 */
	function __construct($verb, $bucket = '', $uri = '', $defaultHost = 's3.amazonaws.com') {
		$this->verb = $verb;
		$this->bucket = strtolower($bucket);
		$this->uri = $uri !== '' ? '/'.str_replace('%2F', '/', rawurlencode($uri)) : '/';

		if ($this->bucket !== '') {
			$this->headers['Host'] = $this->bucket.'.'.$defaultHost;
			$this->resource = '/'.$this->bucket.$this->uri;
		} else {
			$this->headers['Host'] = $defaultHost;
			//$this->resource = strlen($this->uri) > 1 ? '/'.$this->bucket.$this->uri : $this->uri;
			$this->resource = $this->uri;
		}
		$this->headers['Date'] = gmdate('D, d M Y H:i:s T');

		$this->response = new STDClass;
		$this->response->error = false;
	}


	/**
	 * Set request parameter
	 *
	 * @param string $key Key
	 * @param string $value Value
	 * @return void
	 */
	public function setParameter($key, $value) {
		$this->parameters[$key] = $value;
	}


	/**
	 * Set request header
	 *
	 * @param string $key Key
	 * @param string $value Value
	 * @return void
	 */
	public function setHeader($key, $value) {
		$this->headers[$key] = $value;
	}

	/**
	 * Get the S3 response
	 *
	 * @return object | false
	 */
	public function getResponse() {
		$query = '';
		if (sizeof($this->parameters) > 0) {
			$query = substr($this->uri, -1) !== '?' ? '?' : '&';
			foreach ($this->parameters as $var => $value)
				if ($value == null || $value == '') $query .= $var.'&';
			// Parameters should be encoded (thanks Sean O'Dea)
			else $query .= $var.'='.rawurlencode($value).'&';
			$query = substr($query, 0, -1);
			$this->uri .= $query;

			if (array_key_exists('acl', $this->parameters) ||
					array_key_exists('location', $this->parameters) ||
					array_key_exists('torrent', $this->parameters) ||
					array_key_exists('logging', $this->parameters))
						$this->resource .= $query;
		}
		$url = ((S3::$useSSL && extension_loaded('openssl')) ?
				'https://':'http://').$this->headers['Host'].$this->uri;
		//var_dump($this->bucket, $this->uri, $this->resource, $url);

		// Basic setup
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERAGENT, 'S3/php');

		if (S3::$useSSL) {
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
		}

		curl_setopt($curl, CURLOPT_URL, $url);

		// Headers
		$headers = array(); $amz = array();
		foreach ($this->amzHeaders as $header => $value)
			if (strlen($value) > 0) $headers[] = $header.': '.$value;
		foreach ($this->headers as $header => $value)
			if (strlen($value) > 0) $headers[] = $header.': '.$value;

		// Collect AMZ headers for signature
		foreach ($this->amzHeaders as $header => $value)
			if (strlen($value) > 0) $amz[] = strtolower($header).':'.$value;

		// AMZ headers must be sorted
		if (sizeof($amz) > 0) {
			sort($amz);
			$amz = "\n".implode("\n", $amz);
		} else $amz = '';

		// Authorization string (CloudFront stringToSign should only contain a date)
		$headers[] = 'Authorization: ' . S3::__getSignature(
				$this->headers['Host'] == 'cloudfront.amazonaws.com' ? $this->headers['Date'] :
				$this->verb."\n".$this->headers['Content-MD5']."\n".
				$this->headers['Content-Type']."\n".$this->headers['Date'].$amz."\n".$this->resource
		);

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($curl, CURLOPT_WRITEFUNCTION, array(&$this, '__responseWriteCallback'));
		curl_setopt($curl, CURLOPT_HEADERFUNCTION, array(&$this, '__responseHeaderCallback'));
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		// Request types
		switch ($this->verb) {
			case 'GET': break;
			case 'PUT': case 'POST': // POST only used for CloudFront
				if ($this->fp !== false) {
					curl_setopt($curl, CURLOPT_PUT, true);
					curl_setopt($curl, CURLOPT_INFILE, $this->fp);
					if ($this->size >= 0)
						curl_setopt($curl, CURLOPT_INFILESIZE, $this->size);
				} elseif ($this->data !== false) {
					curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->verb);
					curl_setopt($curl, CURLOPT_POSTFIELDS, $this->data);
					if ($this->size >= 0)
						curl_setopt($curl, CURLOPT_BUFFERSIZE, $this->size);
				} else
					curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->verb);
				break;
			case 'HEAD':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'HEAD');
				curl_setopt($curl, CURLOPT_NOBODY, true);
				break;
			case 'DELETE':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
				break;
			default: break;
		}

		// Execute, grab errors
		if (curl_exec($curl))
			$this->response->code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		else
			$this->response->error = array(
					'code' => curl_errno($curl),
					'message' => curl_error($curl),
					'resource' => $this->resource
			);

			@curl_close($curl);

			// Parse body into XML
			if ($this->response->error === false && isset($this->response->headers['type']) &&
					$this->response->headers['type'] == 'application/xml' && isset($this->response->body)) {
						$this->response->body = simplexml_load_string($this->response->body);

						// Grab S3 errors
						if (!in_array($this->response->code, array(200, 204)) &&
								isset($this->response->body->Code, $this->response->body->Message)) {
									$this->response->error = array(
											'code' => (string)$this->response->body->Code,
											'message' => (string)$this->response->body->Message
									);
									if (isset($this->response->body->Resource))
										$this->response->error['resource'] = (string)$this->response->body->Resource;
									unset($this->response->body);
								}
					}

					// Clean up file resources
					if ($this->fp !== false && is_resource($this->fp)) fclose($this->fp);

					return $this->response;
	}


	/**
	 * CURL write callback
	 *
	 * @param resource &$curl CURL resource
	 * @param string &$data Data
	 * @return integer
	 */
	function __responseWriteCallback(&$curl, &$data) {
		if ($this->response->code == 200 && $this->fp !== false)
			return fwrite($this->fp, $data);
		else
			$this->response->body .= $data;
		return strlen($data);
	}


	/**
	 * CURL header callback
	 *
	 * @param resource &$curl CURL resource
	 * @param string &$data Data
	 * @return integer
	 */
	function __responseHeaderCallback(&$curl, &$data) {
		if (($strlen = strlen($data)) <= 2) return $strlen;
		if (substr($data, 0, 4) == 'HTTP')
			$this->response->code = (int)substr($data, 9, 3);
		else {
			list($header, $value) = explode(': ', trim($data), 2);
			if ($header == 'Last-Modified')
				$this->response->headers['time'] = strtotime($value);
			elseif ($header == 'Content-Length')
			$this->response->headers['size'] = (int)$value;
			elseif ($header == 'Content-Type')
			$this->response->headers['type'] = $value;
			elseif ($header == 'ETag')
			$this->response->headers['hash'] = $value{0} == '"' ? substr($value, 1, -1) : $value;
			elseif (preg_match('/^x-amz-meta-.*$/', $header))
			$this->response->headers[$header] = is_numeric($value) ? (int)$value : $value;
		}
		return $strlen;
	}

}

