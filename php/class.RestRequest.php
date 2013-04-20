<?php
class RestRequest extends RestMessage
	{
	public $method;
	public $uri;
	public $uriNodes;
	public $controller;
	public $filePath;
	public $fileName;
	public $fileExt;
	public $isFolder;
	public $queryString;
	public $queryValues;
	private $_acceptHeaders;
	function __construct($method, $uri='/', $headers=array(), $content='')
		{
		// Tracks requests
		// file_put_contents('test.txt','Method:'.$method."\n".'Uri:'.$uri."\n".print_r($content,true),FILE_APPEND);
		if(!$method)
			{
			$this->method=RestMethods::GET;
			}
		else if(is_string($method))
			{
			$this->method=RestMethods::getMethodFromString($method);
			}
		else
			{
			$this->method=$method;
			}
		$this->uri=$uri;
		$this->_acceptHeaders=array();
		parent::__construct($headers,$content);
		}
	function getHeader($name,$type='text',$filter='iparameter')
		{
		$value=parent::getHeader($name);
		if($value!==''&&!xcUtilsInput::filterValue($value,$type,$filter))
			throw new RestException(RestCodes::HTTP_400,'Header '.$name.' value do not match the right type ('.$type.','.$filter.')');
		return $value;
		}
	// URI parsing
	function parseUri()
		{
		$this->uriNodes=new MergeArrayObject();
		$this->fileName='';
		$this->fileExt='';
		$this->filePath='';
		// Format : /(controller)?(/node)*(.type)?(?(param=value)*)?
		if(strpos($this->uri,'./')===0)
			$reluri=substr($this->uri,2);
		else if(strpos($this->uri,'/')===0)
			$reluri=substr($this->uri,1);
		else
			$reluri=$this->uri;
		
		$curNode='';
		$numNodes=0;
		for($i=0; $i<strlen($reluri); $i++)
			{
			if(($reluri[$i]=='.'&&strrpos($reluri,'.',(strpos($reluri,'?')===false?0:strpos($reluri,'?')-strlen($reluri)))===$i)||$reluri[$i]=='?')
				break;
			if($reluri[$i]=='/')
				{
				if($curNode=='')
					throw new RestException(RestCodes::HTTP_400,'A uri node cannot be empty ! (Node:'.($this->uriNodes->count()+1).')');
				if($this->uriNodes->count()>0)
					$this->filePath.=$curNode;
				$this->filePath.='/';
				$this->uriNodes->append($curNode);
				$curNode='';
				continue;
				}
			$curNode.=$reluri[$i];
			}
		if($curNode!=='')
			{
			$this->uriNodes->append($curNode);
			if($this->uriNodes->count()>1)
				$this->fileName=$curNode;
			}
		else
			{
			$this->isFolder=true;
			}
		// Getting file extension
		if(isset($reluri[$i])&&$reluri[$i]=='.')
			{
			for($i=$i+1; $i<strlen($reluri); $i++)
				{
				if($reluri[$i]=='?')
					break;
				$this->fileExt.=$reluri[$i];
				}
			if(!xcUtilsInput::isIParameter($this->fileExt)) // Should be Parameter, have to change when everything is ok
				throw new RestException(RestCodes::HTTP_400,'Illegal character(s) found in the file extension (a-z/0-9 only)');
			}
		// Getting controller name
		if(isset($this->uriNodes[0])&&$this->uriNodes[0])
			{
			$this->controller=$this->uriNodes[0];
			if(!xcUtilsInput::isParameter($this->controller))
				throw new RestException(RestCodes::HTTP_400,'Illegal character(s) found in the controller name (a-z/0-9 only)');
			}
		else
			$this->controller='';
		// Getting query string
		if(strpos($reluri,'?')!==false)
			{
			$this->queryString=substr($reluri,strpos($reluri,'?')+1);
			if($this->queryString=='')
				throw new RestException(RestCodes::HTTP_400,'Found "?" character but nothing after (uri: '.$reluri.').');
			}
		else
			$this->queryString='';
		}
	// Query string parsing
	function parseQueryString()
		{
		$this->queryValues=$this->parseFormUrlEncoded($this->queryString);
		}
	// Request content parsing
	function checkContent($fields)
		{
		}
	function parseFormContent()
		{
		$content=$this->content;
		$this->content=new stdClass();
		foreach($this->parseFormUrlEncoded($content) as $param)
			{
			Varstream::set($this->content,$param->name,$param->value);
			}
		}
	function parseVarsContent()
		{
		$content=$this->content;
		$this->content=new stdClass();
		Varstream::import($this->content,$content);
		}
	function parseBase64Content()
		{
		$params=explode(';',substr($this->content,5));
		$this->setHeader('Content-Type',$params[0]);
		$cnt=explode(',',$params[1]);
		$this->content=base64_decode($cnt[1]);
		}
	function parseJsonContent()
		{
		$this->content=Json::decode($this->content);
		}
	function parseFormUrlEncoded($string)
		{
		$params=new MergeArrayObject();
		$param=new stdClass();
		$param->name='';
		$param->value='';
		for($i=0; $i<strlen($string); $i++)
			{
			if($param->value==='')
				{
				if($string[$i]=='&')
					throw new RestException(RestCodes::HTTP_400,'Unterminated query string param ('.$param->name.')');
				if($string[$i]=='=')
					{
					if($param->name=='')
						throw new RestException(RestCodes::HTTP_400,'A query string param has no name !');
					if($string[$i+1]=='&')
						throw new RestException(RestCodes::HTTP_400,'A query string param has no value ('.$param->name.')');
					if(!xcUtilsInput::isIAscii($param->name))
						throw new RestException(RestCodes::HTTP_400,'Illegal character(s) found in query string param name (a-z/0-9 only)');
					$param->value.=$string[$i+1];
					$i++;
					continue;
					}
				$param->name.=$string[$i];
				}
			else if($string[$i]=='&')
				{
				$param->value=urldecode($param->value);
				$params->append($param);
				$param=new stdClass();
				$param->name='';
				$param->value='';
				continue;
				}
			else
				{
				if($string[$i]=='=')
					throw new RestException(RestCodes::HTTP_400,'Can\'t put "=" char in a param value, please encode him (found in "'.$param->name.'" value: "'.$param->value.'").');
				$param->value.=$string[$i];
				}
			}
		if($i>0&&$string[$i-1]=='&')
			throw new RestException(RestCodes::HTTP_400,'Orphelin "&" found at the query string end.');
		if($param->name)
			{
			$param->value=urldecode($param->value);
			if($param->value==='')
				throw new RestException(RestCodes::HTTP_400,'A query string param has no value ('.$param->name.')');
			$params->append($param);
			}
		return $params;
		}
	// Request headers parsing
	function sortAcceptHeader($name)
		{
		if(!isset($this->_acceptHeaders[$name]))
			{
			$this->_acceptHeaders[$name]=array();
			if(isset($this->headers[$name]))
				{
				$masks=explode(',',$this->headers[$name]);
				$maskIndex=1;
				for($i=sizeof($masks)-1; $i>=0; $i--)
					{
					$mask=explode(';',$masks[$i]);
					$mask[0]='/'.str_replace('*','(.*)',str_replace('/','\/',trim($mask[0]))).'/';
					if(!isset($mask[1]))
						{
						$mask[1]=$maskIndex*10000;
						}
					else
						{
						$mask[1]=intval(str_replace('q','',str_replace('=','',$mask[1].$maskIndex))*10000);
						}
					$this->_acceptHeaders[$name][$mask[1]]=$mask[0];
					$maskIndex++;
					}
				krsort($this->_acceptHeaders[$name]);
				}
			else
				{
				$this->_acceptHeaders[$name][1]='(.*)';
				}
			}
		}
	function testAcceptHeader($name,$value)
		{
		$name=str_replace(' ', '-', ucwords(strtolower(str_replace('-',' ',$name))));
		$this->sortAcceptHeader($name);
		foreach($this->_acceptHeaders[$name] as $key => $mask)
			{
			if(preg_match($mask,$value))
				return true;
			}
		return false;
		}
	function leveledTestAcceptHeader($name,$value,$level=0)
		{
		$name=str_replace(' ', '-', ucwords(strtolower(str_replace('-',' ',$name))));
		$this->sortAcceptHeader($name);
		foreach($this->_acceptHeaders[$name] as $key => $mask)
			{
			if(!$level)
				$level=$key;
			if($key==$level)
				{
				if(preg_match($mask,$value))
					return true;
				}
			else if($key<$level)
				return $key;
			}
		return 0;
		}
	}
