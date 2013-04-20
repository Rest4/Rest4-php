<?php
class RestResponse extends RestMessage
	{
	public $code;
	function __construct($code=RestCodes::HTTP_200, $headers=array(), $content='')
		{
		$this->code=$code;
		parent::__construct($headers,$content);
		}
	function getContents()
		{
		return $this->content;
		}
	}
