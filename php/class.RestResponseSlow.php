<?php
class RestResponseSlow extends RestResponseStream
	{
	private $_response;
	private $_delay;
	private $_i=0;
	private $_chunks;
	function __construct($response, $delay)
		{
		parent::__construct($response->code, $response->headers);
		$response->setHeader('Content-Type',$response->getHeader('Content-Type'));
		if(!($response instanceof RestResponseStream))
			{
			if($response->getHeader('Content-Type')=='text/varstream'
				||$response->getHeader('Content-Type')=='text/lang')
				{
				$response->setHeader('Content-Type','text/plain');
				if($response->content instanceof MergeArrayObject
					||$response->content instanceof stdClass)
					{
					$response->content=Varstream::export($response->content);
					}
				else
					$response->content=xcUtilsInput::filterAsCdata(
						utf8_encode(print_r($response->content,true)));
				}
			$this->_chunks=explode("\n",$response->getContents());
			}
		$this->_response=$response;
		$this->_delay=$delay;
		}
	function pump()
		{
		flush(); ob_flush(); usleep($this->_delay);
		if($this->_response instanceof RestResponseStream)
			{
			return $this->_response->pump();
			}
		else if(isset($this->_chunks[$this->_i]))
			{
			$this->_i++;
			return $this->_chunks[$this->_i-1]."\n";
			}
		return '';
		}
	}