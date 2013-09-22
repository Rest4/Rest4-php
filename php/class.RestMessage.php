<?php
class RestMessage
	{
	public $headers;
	public $content;
	function __construct($headers=array(), $content='')
		{
		$this->headers=array();
		foreach($headers as $name=>$value)
			{
			if(!isset($value))
				throw new RestException(RestCodes::HTTP_500,'No value transmitted for the header '
					.$name.' in the RestMessage constructor.');
			$this->setHeader($name,$value);
			}
		$this->content=$content;
		}
	function headerIsset($name)
		{
		if(!isset($this->headers[str_replace(' ', '-', ucwords(strtolower(str_replace('-',' ',$name))))]))
			return false;
		return true;
		}
	function setHeader($name, $value)
		{
		if(!isset($value))
			throw new RestException(RestCodes::HTTP_500,'No value transmitted for the header '.$name.'.');
		$this->headers[str_replace(' ', '-', ucwords(strtolower(str_replace('-',' ',$name))))] = $value;
		}
	function appendToHeader($name, $value)
		{
		if(!isset($value))
			throw new RestException(RestCodes::HTTP_500,'No value transmitted for the header '.$name.'.');
		if(!isset($this->headers[str_replace(' ', '-', ucwords(strtolower(str_replace('-',' ',$name))))]))
			$this->headers[str_replace(' ', '-', ucwords(strtolower(str_replace('-',' ',$name))))]='';
		$this->headers[str_replace(' ', '-', ucwords(strtolower(str_replace('-',' ',$name))))] .=
			($this->headers[str_replace(' ', '-', ucwords(strtolower(str_replace('-',' ',$name))))]?'|':'').$value;
		}
	function getHeader($name)
		{
		$name=str_replace(' ', '-', ucwords(strtolower(str_replace('-',' ',$name))));
		if(isset($this->headers[$name]))
			return $this->headers[$name];
		return '';
		}
	function unsetHeader($name)
		{
		$name=str_replace(' ', '-', ucwords(strtolower(str_replace('-',' ',$name))));
		if(isset($this->headers[$name]))
			{
			unset($this->headers[$name]);
			}
		}
	}
