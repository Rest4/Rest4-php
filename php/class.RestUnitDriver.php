<?php
class RestUnitDriver extends RestVarsDriver
	{
	static $drvInf;
	function __construct(RestRequest $request)
		{
		set_time_limit(0);
		parent::__construct($request);
		}
	static function getDrvInf($methods=0)
		{
		$drvInf=parent::getDrvInf(RestMethods::GET);
		$drvInf->name='Unit: Driver';
		$drvInf->description='Give the list of unpassed unit tests.';
		$drvInf->usage='/unit'.$drvInf->usage
			.'?filter=filenamestart&verbose=(yes|no)&multiple=(yes|no)';
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='filter';
		$drvInf->methods->get->queryParams[0]->filter='iparameter';
		$drvInf->methods->get->queryParams[0]->value='';
		$drvInf->methods->get->queryParams[1]=new stdClass();
		$drvInf->methods->get->queryParams[1]->name='verbose';
		$drvInf->methods->get->queryParams[1]->values=new MergeArrayObject();
		$drvInf->methods->get->queryParams[1]->values[0]=
			$drvInf->methods->get->queryParams[1]->value='no';
		$drvInf->methods->get->queryParams[1]->values[1]='yes';
		$drvInf->methods->get->queryParams[2]=new stdClass();
		$drvInf->methods->get->queryParams[2]->name='multiple';
		$drvInf->methods->get->queryParams[2]->values=new MergeArrayObject();
		$drvInf->methods->get->queryParams[2]->values[0]=
			$drvInf->methods->get->queryParams[2]->value='no';
		$drvInf->methods->get->queryParams[2]->values[1]='yes';
		$drvInf->methods->get->queryParams[3]=new stdClass();
		$drvInf->methods->get->queryParams[3]->name='showcontent';
		$drvInf->methods->get->queryParams[3]->values=new MergeArrayObject();
		$drvInf->methods->get->queryParams[3]->values[0]=
			$drvInf->methods->get->queryParams[3]->value='no';
		$drvInf->methods->get->queryParams[3]->values[1]='yes';
		return $drvInf;
		}
	function get()
		{
		$vars=new stdClass();
		$vars->title='Rest Unit Tests result';
		$vars->tests=new MergeArrayObject();
		$tests=new RestResource(new RestRequest(RestMethods::GET,
			'/'.($this->queryParams->multiple=='yes'?'mp':'').'fsi/tests.dat?mode=light'));
		$tests=$tests->getResponse();
		if($tests->code!=RestCodes::HTTP_200)
			{
			throw new RestException(RestCodes::HTTP_500,'Can\'t access the tests list/'
				.' ('.($this->queryParams->multiple=='yes'?'mp':'').'fsi/tests.dat?mode=light).');
			}
		else
			{
			foreach($tests->vars->files as $file)
				{
				if((!(isset($file->isDir)&&$file->isDir))&&($this->queryParams->filter===''
					||strpos($file->name,$this->queryParams->filter)===0))
					{
					$test=new RestResource(new RestRequest(RestMethods::GET,'/'.($this->queryParams->multiple=='yes'?'mp':'')
						.'fs/tests/'.$file->name.($this->queryParams->multiple=='yes'?'?mode=merge':''),
							array('X-Rest-Local-Cache'=>'disabled')));
					$test=$test->getResponse();
					
					if($test->code!=RestCodes::HTTP_200)
						{
						throw new RestException(RestCodes::HTTP_500,'Can\'t access the test: '.$file->name);
						}
					else
						{
						Varstream::import($testContent=new stdClass(),$test->getContents());
						$entry=new stdClass();
						$req=new RestRequest(RestMethods::getMethodFromString($testContent->request->method),
							$testContent->request->uri,array('X-Rest-Local-Cache'=>'disabled'));
						if(isset($testContent->request->headers))
							{
							foreach($testContent->request->headers as $header)
								{
								$req->setHeader($header->name,$header->value);
								}
							}
						if(isset($testContent->request->content))
							$req->content=$testContent->request->content;
						$res=new RestResource($req);
						$res=$res->getResponse();
						$entry->title=$testContent->title;
						$entry->file=$file->name;
						$entry->result=$testContent->request->method.' '.$testContent->request->uri
							.' : '.$res->code.' '.constant('RestCodes::HTTP_'.$res->code.'_MSG');
						$entry->errors=new MergeArrayObject();
						if($res->code!=$testContent->response->code)
							{
							$entry->errors->append('Unexpected result : HTTP response code is '.$res->code
								.', '.$testContent->response->code.' expected.');
							}
						if(isset($testContent->response->headers))
							{
							foreach($testContent->response->headers as $header)
								{
								if($res->getHeader($header->name)&&$res->getHeader($header->name)!=$header->value)
									$entry->errors->append('Unexpected result : HTTP response header '.$header->name
										.' value is "'.$res->getHeader($header->name).'". Expected: "'.$header->value.'"');
								}
							}
						if(isset($res->vars))
							{
							 // Should create a comparison function for stdClass objects
							 // if error, showing content
							if($this->queryParams->showcontent=='yes'||$entry->errors->count())
								$entry->content=$res->vars;
							}
						else
							{
							if(isset($testContent->response->content)&&$testContent->response->content!==''
								&&$res->getContents()!=$testContent->response->content)
								$entry->errors->append('Unexpected result : HTTP response content differs.');
							if($this->queryParams->showcontent=='yes'||$entry->errors->count())
								$entry->content=$res->content;
							}
						if($this->queryParams->verbose=='yes'||$entry->errors->count())
							$vars->tests->append($entry);
						}
					}
				}
			}
		return new RestVarsResponse(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)),
			$vars);
		}
	}
