<?php

class UploadFromUrlToGoogleCloudStorage extends UploadFromUrl {
	
	/**
	 * Create a new temporary file in the URL subdirectory of wfTempDir().
	 *
	 * @return string Path to the file
	 */
	protected function makeTemporaryFile() {
		global $wgCss;
		$tmpFile = TempFSFile::factory( 'URL' );
		$tmpFile->bind( $this );
		$tmpFileStr = $wgCss->getBucketUrl() . 'tmp/'. $tmpFile->getSha1Base36();
		return $tmpFileStr;
	}
	
	/**
	 * Callback: save a chunk of the result of a HTTP request to the temporary file
	 *
	 * @param mixed $req
	 * @param string $buffer
	 * @return int Number of bytes handled
	 */
	public function saveTempFileChunk( $req, $buffer ) {
		wfDebugLog( 'fileupload', 'Received chunk of ' . strlen( $buffer ) . ' bytes' );
		$nbytes = fwrite( $this->mTmpHandle, $buffer );
	
		if ( $nbytes == strlen( $buffer ) ) {
			$this->mFileSize += $nbytes;
		} else {
			// Well... that's not good!
			wfDebugLog(
					'fileupload',
					'Short write ' . $this->nbytes . '/' . strlen( $buffer ) .
					' bytes, aborting with '  . $this->mFileSize . ' uploaded so far'
			);
			fclose( $this->mTmpHandle );
			$this->mTmpHandle = false;
		}
	
		return $nbytes;
	}
}