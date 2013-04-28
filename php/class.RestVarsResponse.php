<?php
// A response object able to output a data tree in several formats
// also allows direct access to the data tree (internal resources)
class RestVarsResponse extends RestResponse
	{
	// Supported mimes
	const MIMES = 'application/json,text/xml,text/html,form/urlencoded,text/varstream';
	// text/php causes bugs
	public $vars;
	function __construct($code=RestCodes::HTTP_200, $headers=array(), $vars=null)
		{
		// The content is always a stdClass instance
		if($vars instanceof stdClass)
			$this->vars=$vars;
		else if($vars)
			throw new RestException(RestCodes::HTTP_500,'Given content is not an instance of stdClass.');
		else
			$this->vars=new stdClass();
		parent::__construct($code, $headers);
		}
	function getContents()
		{
		switch($this->getHeader('Content-Type'))
			{
			case 'text/php':
				$this->content='<?php'."\n".var_export($this->vars,true);
				break;
			case 'application/json':
				$this->content=Json::encode($this->vars);
				break;
			case 'text/xml':
				throw new RestException(RestCodes::HTTP_501,'XML exports aren\'t done yet');
				$this->content=Varstream::export($this->vars);
				break;
			case 'text/html':
				throw new RestException(RestCodes::HTTP_501,'HTML exports aren\'t done yet');
				$this->content=Varstream::export($this->vars);
				break;
			case 'application/x-www-form-urlencoded':
				throw new RestException(RestCodes::HTTP_501,'URL encoded exports aren\'t done yet');
				$this->content=$this->vars;
				break;
			case 'text/varstream':
				$this->content=Varstream::export($this->vars);
				break;
			default:
				throw new RestException(RestCodes::HTTP_406,'Cannot convert datas to the asked content type (given: '
					.$this->getHeader('Content-Type').', can serve: php,json,xml,html,form,dat.');
				break;
			}
		return $this->content;
		}
	}
