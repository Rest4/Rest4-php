<?php
class RestDriver
	{
	public $request;
	public $core;
	public $className;
	public $queryParams;
	function __construct(RestRequest $request)
		{
		$this->request=$request;
		$this->core=RestServer::Instance();
		}
	static function getDrvInf($method=0)
		{
		return null;
		}
	function getResponse()
		{
		// Retrieving driver infos
		if(!$this::$drvInf)
			$this::$drvInf=$this::getDrvInf();
		// Checking request method validity
		if(!(isset($this::$drvInf->methods,$this::$drvInf->methods->{strtolower(
				RestMethods::getStringFromMethod($this->request->method))})))
			throw new RestException(RestCodes::HTTP_501,'The used method has not been documented'
				.' yet so it is disabled ('.strtolower(RestMethods::getStringFromMethod($this->request->method))
				.', '.get_class($this).').');
		if(!method_exists($this,strtolower(RestMethods::getStringFromMethod($this->request->method))))
				throw new RestException(RestCodes::HTTP_405,'The requested method is documented but not'
				.' implemented by this ressource ('.RestMethods::getStringFromMethod($this->request->method).')');
		// Processing query params if the driver supports them for this method
		if(isset($this::$drvInf->methods->{strtolower(
			RestMethods::getStringFromMethod($this->request->method))}->queryParams))
			{
			$this->queryParams=$this->getQueryStringParams();
			}
		else if($this->request->queryString)
			throw new RestException(RestCodes::HTTP_400,'This driver does not define any queryString'
				.' parameters for this method ('.strtolower(
					RestMethods::getStringFromMethod($this->request->method))
				.', '.get_class($this).').');
		// Saving asked content type
		$mime=xcUtils::getMimeFromExt($this->request->fileExt);
		// Checking requested content type
		if(!isset($this::$drvInf->methods->{strtolower(RestMethods::getStringFromMethod($this->request->method))},
			$this::$drvInf->methods->{strtolower(RestMethods::getStringFromMethod($this->request->method))}->outputMimes))
			throw new RestException(RestCodes::HTTP_500,'The output mimes for this method lacks in this driver'
				.' ('.strtolower(RestMethods::getStringFromMethod($this->request->method)).').');
		if($this::$drvInf->methods->{strtolower(RestMethods::getStringFromMethod($this->request->method))}->outputMimes!='*')
			{
			// Checking is the extensions correspond to an existing output for the resource
			if($this->request->fileExt)
				{
				if(!$mime)
					throw new RestException(RestCodes::HTTP_406,'The given file ext is not recognized by the REST server'
						.' ('.$this->request->fileExt.').');
				else if(strpos($this::$drvInf->methods->{strtolower(
					RestMethods::getStringFromMethod($this->request->method))}->outputMimes,$mime)===false)
					{
					if(strpos($this::$drvInf->methods->{strtolower(RestMethods::getStringFromMethod($this->request->method))}
						->outputMimes,'text/varstream')===false
						||($mime!='text/plain'&&$mime!='application/json'))
						throw new RestException(RestCodes::HTTP_406,'This REST driver don\'t support the file extension of your uri'
							.' (given: '.$this->request->fileExt.':'.$mime.', can serve: '
							.$this::$drvInf->methods->{strtolower(RestMethods::getStringFromMethod($this->request->method))}
								->outputMimes.').');
					}
				}
			// Trying to find a file extension according to the accept header
			else
				{
				$outMimes=explode(',',$this::$drvInf->methods->
					{strtolower(RestMethods::getStringFromMethod($this->request->method))}
					->outputMimes);
				if(strpos($this::$drvInf->methods->{strtolower(
					RestMethods::getStringFromMethod($this->request->method))}
					->outputMimes,'text/varstream')!==false)
					array_push($outMimes,'text/plain','application/json');
				$acceptLevel=10000;
				$acceptedMime='';
				while($acceptLevel!=0&&$acceptedMime==='')
					{
					foreach($outMimes as $outMime)
						{
						$lastLevel=$this->request->leveledTestAcceptHeader('Accept',$outMime,$acceptLevel);
						if($lastLevel===true)
							{
							$acceptedMime=$outMime;
							break;
							}
						}
					$acceptLevel=$lastLevel;
					}
				if(!$acceptedMime)
					throw new RestException(RestCodes::HTTP_406,'This REST driver don\'t support your request Accept'
						.' prerogatives for the given method (given: '.$this->request->getHeader('Accept').', can serve: '
						.$this::$drvInf->methods->{strtolower(
							RestMethods::getStringFromMethod($this->request->method))}->outputMimes.').');
				else // Could be 300 ?
					throw new RestException(RestCodes::HTTP_301,'Redirecting to the the found ressource corresponding'
					.' to your Accept prerogative.', '', array('Location'=>'/'
					.$this->request->controller.$this->request->filePath.$this->request->fileName.'.'
					.xcUtils::getExtFromMime($acceptedMime).($this->request->queryString?'?'.$this->request->queryString:'')));
				}
			}
			// Testing supported charset
			if(!$this->request->testAcceptHeader('Accept-Charset','utf-8'))
				{
				throw new RestException(RestCodes::HTTP_406,'This server don\'t support your request'
					.' Accept-Charset prerogatives for the given method (given: '
					.$this->request->getHeader('Accept-Charset').', can serve: utf-8 only).');
				}
		// Processing the right method for the asked resource
		switch($this->request->method)
			{
			case RestMethods::OPTIONS:
				$response=$this->options();
				break;
			case RestMethods::HEAD:
				$response=$this->head();
				break;
			case RestMethods::GET:
				$response=$this->get();
				break;
			case RestMethods::PUT:
				$response=$this->put();
				break;
			case RestMethods::POST:
				$response=$this->post();
				break;
			case RestMethods::DELETE:
				$response=$this->delete();
				break;
			default:
				throw new RestException(RestCodes::HTTP_400,'The requested method is not part of HTTP 1.1 ('
					.RestMethods::getStringFromMethod($this->request->method).')');
				break;
			}
		// Testing if mime type is correct (should be removed)
		if($response instanceof RestResponseVars&&$response->getHeader('Content-Type')!=$mime)
				throw new RestException(RestCodes::HTTP_500,'The mime type hasn\'t been set correctly'
					.' ("'.$response->getHeader('Content-Type').'" instead of "'.$mime.'").');
		return $response;
		}
	function options()
		{
		// Testing driver infos
		if(!isset($this::$drvInf->name))
			throw new RestException(RestCodes::HTTP_501,'Driver infos are currently not documented');
		// Building the allow header
		$allow='';
		if(isset($this::$drvInf->methods))
		foreach(get_object_vars($this::$drvInf->methods) as $key => $value)
			$allow.=(RestMethods::getMethodFromString($key)? // check if method exists
				($allow?', ':'').strtoupper($key):
				'');
		// Sending the options
		return new RestResponseVars(
			RestCodes::HTTP_200,
			array('Content-Type'=>xcUtils::getMimeFromExt($this->request->fileExt),
				'Allow'=>$allow),
			$this::$drvInf
			);
		}
	function head()
		{
		$response=$this->get();
		$response->content=null;
		return $response;
		}
	function get()
		{
		throw new RestException(RestCodes::HTTP_501,'Not done yet');
		}
	function put()
		{
		throw new RestException(RestCodes::HTTP_501,'Not done yet');
		}
	function post()
		{
		throw new RestException(RestCodes::HTTP_501,'Not done yet');
		}
	function delete()
		{
		throw new RestException(RestCodes::HTTP_501,'Not done yet');
		}
	function checkUriNodes()
		{
		}
	function getQueryStringParams()
		{
		// Parsing query string
		$this->request->parseQueryString();
		// Preparing values container
		$values=new stdClass();
		// Gettin' declared query params
		$queryParams=$this::$drvInf->methods->{strtolower(
			RestMethods::getStringFromMethod($this->request->method))}->queryParams;
		// Iterating throught query values
		$k=0;
		$l=$this->request->queryValues->count();
		for($i=0, $j=$queryParams->count(); $i<$j; $i++)
			{
			// Query params can't be required when it have a default value
			if(isset($queryParams[$i]->required)&&$queryParams[$i]->required&&isset($queryParams[$i]->value))
				throw new RestException(RestCodes::HTTP_500,'This parameter "'.$queryParams[$i]->name
					.'" cannot be required when it have a default value.');
			// Unrequired single query params must have a default value
			if((!isset($queryParams[$i]->value))&&(!(isset($queryParams[$i]->required)&&$queryParams[$i]->required))
				&&!(isset($queryParams[$i]->multiple)&&$queryParams[$i]->multiple))
				throw new RestException(RestCodes::HTTP_500,'This parameter "'
				.$queryParams[$i]->name.'" has no default value.');
			// Parsing values
			for($k; $k<$l; $k++)
				{
				if($this->request->queryValues[$k]->name==$queryParams[$i]->name)
					{
					// Checking if the given value is not the default value
					if(isset($queryParams[$i]->value)&&$queryParams[$i]->value==$this->request->queryValues[$k]->value)
						throw new RestException(RestCodes::HTTP_400,'The given value for the "'
							.$queryParams[$i]->name.'" parameter is the default value. Remove the parameter to use it\'s default value');
					// Filtering the given value with the declared filter
					$value=xcUtilsInput::filterValue($this->request->queryValues[$k]->value,(isset($queryParams[$i]->type)?
						$queryParams[$i]->type:'text'),(isset($queryParams[$i]->filter)?$queryParams[$i]->filter:'parameter'));
					if((!$value)&&$value===null)
						throw new RestException(RestCodes::HTTP_400,'The given value for the "'.$queryParams[$i]->name
							.'" is not matching the following type "'.(isset($queryParams[$i]->type)?$queryParams[$i]->type:'text')
							.'" (filter: '.(isset($queryParams[$i]->filter)?$queryParams[$i]->filter:'parameter').')');
					// if the value is a number, chek min and max values
					if($queryParams[$i]->filter='number'&&isset($queryParams[$i]->min)&&
						$queryParams[$i]->min>$value)
						throw new RestException(RestCodes::HTTP_400,'The given value for "'.$queryParams[$i]->name
							.'" is lower than the minimal value ('.$queryParams[$i]->min.').');
					if($queryParams[$i]->filter='number'&&isset($queryParams[$i]->max)&&
						$queryParams[$i]->max<$value)
						throw new RestException(RestCodes::HTTP_400,'The given value for "'.$queryParams[$i]->name
							.'" is greater than the maximal value ('.$queryParams[$i]->max.').');
					// if a set of values is declared, check if the value is inside the set
					if(isset($queryParams[$i]->values)&&
						array_search($value, $queryParams[$i]->values->getArrayCopy())===false)
						throw new RestException(RestCodes::HTTP_400,'The given value for the "'.$queryParams[$i]->name
							.'" is not included in the allowed set of values.');
					// If the param is multiple
					if(isset($queryParams[$i]->multiple)&&$queryParams[$i]->multiple)
						{
						// create the array to caint values
						if(!isset($values->{$queryParams[$i]->name}))
							$values->{$queryParams[$i]->name}=new MergeArrayObject();
						// Append the value
						$values->{$queryParams[$i]->name}->append($this->request->queryValues[$k]->value);
						// Check values order
						if($values->{$queryParams[$i]->name}->count()>1&&!(isset($queryParams[$i]->orderless)
							&&$queryParams[$i]->orderless))
							{
							for($m=0, $n=strlen($values->{$queryParams[$i]->name}[$values->{$queryParams[$i]->name}->count()-1]);
								$m<$n; $m++)
								{
								if(isset($values->{$queryParams[$i]->name}[$values->{$queryParams[$i]->name}->count()-2][$m]))
									{
									if(ord($values->{$queryParams[$i]->name}[$values->{$queryParams[$i]->name}->count()-1][$m])
										<ord($values->{$queryParams[$i]->name}[$values->{$queryParams[$i]->name}->count()-2][$m]))
										{
										throw new RestException(RestCodes::HTTP_400,'The value #'.$values->{$queryParams[$i]->name}
											->count().' of the parameter "'.$queryParams[$i]->name.'" is not well ordinated at char '.$m
											.' ('.$values->{$queryParams[$i]->name}[$values->{$queryParams[$i]->name}->count()-1][$m]
											.'<'.$values->{$queryParams[$i]->name}[$values->{$queryParams[$i]->name}->count()-2][$m].').');
										}
									if(ord($values->{$queryParams[$i]->name}[$values->{$queryParams[$i]->name}->count()-1][$m])
										!=ord($values->{$queryParams[$i]->name}[$values->{$queryParams[$i]->name}->count()-2][$m]))
										{
										break;
										}
									}
								}
							}
						}
					// If the param is single, just append it's value
					else
						{
						$values->{$queryParams[$i]->name}=$this->request->queryValues[$k]->value;
						$k++;
						break;
						}
					}
				else
					break;
				}
			// need to review this code, it looks strange
			if(isset($queryParams[$i]->value)&&!isset($values->{$queryParams[$i]->name}))
				{
				if(isset($queryParams[$i]->multiple)&&$queryParams[$i]->multiple)
					{
					$values->{$queryParams[$i]->name}=new MergeArrayObject();
					$values->{$queryParams[$i]->name}->append($queryParams[$i]->value);
					}
				else
					$values->{$queryParams[$i]->name}=$queryParams[$i]->value;
				}
			// checking if we're not outside the loop cause a required params broke it
			if(isset($queryParams[$i]->required)&&$queryParams[$i]->required&&!isset($values->{$queryParams[$i]->name}))
				{
				throw new RestException(RestCodes::HTTP_400,'This parameter "'.$queryParams[$i]->name
					.'" is required when using this method for this driver.');
				}
			}
		if($k<$l)
			{
			throw new RestException(RestCodes::HTTP_400,'Bad query param ('.$this->request->queryValues[$k]->name
				.'), check params list and order.');
			}
		return $values;
		}
	}
