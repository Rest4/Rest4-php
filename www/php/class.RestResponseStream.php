<?php
class RestResponseStream extends RestResponse
	{
	public $core;
	function __construct($code=RestCodes::HTTP_200, $headers=array())
		{
		$this->core=RestServer::Instance();
		parent::__construct($code, $headers, '');
		}
	function pump()
		{
		return '';
		}
	function getContents()
		{
		while(($cnt=$this->pump())!=='')
			{
			$this->content.=$cnt;
			}
		return $this->content;
		}
	}