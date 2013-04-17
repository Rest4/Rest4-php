<?php
class RestResponseFilesStream extends RestResponseStream
	{
	private $_filePathes;
	private $_handle;
	private $_i=-1;
	function __construct($code=RestCodes::HTTP_200, $headers=array(),
		$filePathes,$downloadFilename='')
		{
		if($downloadFilename)
			{
			$headers['Content-Disposition']='attachment; filename="'.$downloadFilename.'"';
			$headers['X-Rest-Cache']='None';
			}
		parent::__construct($code, $headers);
		$this->_filePathes=$filePathes;
		}
	function pump()
		{
		// Opening the next file if none open
		if(!$this->_handle)
			$this->_handle=fopen($this->_filePathes[++$this->_i], 'r');
		// Getting the next line
		$buffer = fgets($this->_handle, 4096);
		// Returning the buffer content
		if($buffer !== false)
			return $buffer;
		// Managing end of file
		fclose($this->_handle);
		$this->_handle=null;
		// Trying to open another file
		if(isset($this->_filePathes[$this->_i+1]))
			return $this->pump();
		return '';
		}
	}