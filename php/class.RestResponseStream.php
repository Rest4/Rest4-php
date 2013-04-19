<?php
// this class is designed to be extended it's purpose is to output
// the resource content progressively
class RestResponseStream extends RestResponse
	{
	public $core;
	function __construct($code=RestCodes::HTTP_200, $headers=array())
		{
		$this->core=RestServer::Instance();
		parent::__construct($code, $headers, '');
		}
	// called to get the next chunk of data to send
	function pump()
		{
		return '';
		}
	// used to access the whole datas can be accessed multiple times
	function getContents()
		{
		while(($cnt=$this->pump())!=='')
			{
			$this->content.=$cnt;
			}
		return $this->content;
		}
	}