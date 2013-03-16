<?php
class RestException extends Exception
	{
	protected $code;
	protected $message;
	private $debug;
	public function __construct($code, $message, $debug='', $headers=array())
		{
		$this->code=$code;
		$this->message=$message;
		$this->debug=$debug;
		$this->headers=$headers;
		parent::__construct($code.' '.$message);
		}
	public function getDebug()
		{
		return $this->debug;
		}
	}