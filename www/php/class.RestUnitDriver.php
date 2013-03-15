<?php
class RestUnitDriver extends RestDriver
	{
	static $drvInf;
	function __construct(RestRequest $request)
		{
		set_time_limit(0);
		parent::__construct($request);
		}
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='Unit: Driver';
		$drvInf->description='Give the list of unpassed unit tests.';
		$drvInf->usage='/unit.ext?filter=filenamestart&verbose=0/1&multiple=0/1';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='text/plain,application/internal';
		$drvInf->methods->head=$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='application/internal,text/plain';
		$drvInf->methods->get->queryParams=new xcObjectCollection();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='filter';
		$drvInf->methods->get->queryParams[0]->filter='iparameter';
		$drvInf->methods->get->queryParams[0]->value='';
		$drvInf->methods->get->queryParams[1]=new stdClass();
		$drvInf->methods->get->queryParams[1]->name='verbose';
		$drvInf->methods->get->queryParams[1]->type='number';
		$drvInf->methods->get->queryParams[1]->filter='int';
		$drvInf->methods->get->queryParams[1]->value=0;
		$drvInf->methods->get->queryParams[2]=new stdClass();
		$drvInf->methods->get->queryParams[2]->name='multiple';
		$drvInf->methods->get->queryParams[2]->type='number';
		$drvInf->methods->get->queryParams[2]->filter='int';
		$drvInf->methods->get->queryParams[2]->value=0;
		return $drvInf;
		}
	function get()
		{
		$response=new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'application/internal')
			);
		$response->content=new stdClass();
		$response->content->title='Rest Unit Tests result';
		$response->content->tests=new xcObjectCollection();
		$tests=new RestResource(new RestRequest(RestMethods::GET,'/'.($this->queryParams->multiple?'mp':'').'fsi/tests.dat?mode=light'));
		$tests=$tests->getResponse();
		if($tests->code!=RestCodes::HTTP_200)
			{
			throw new RestException(RestCodes::HTTP_500,'Can\'t access the tests list/'.($this->queryParams->multiple?'mp':'').'fsi/tests.dat?mode=light');
			}
		else
			{
			foreach($tests->content->files as $file)
				{
				if((!(isset($file->isDir)&&$file->isDir))&&($this->queryParams->filter===''||strpos($file->name,$this->queryParams->filter)===0))
					{
					$test=new RestResource(new RestRequest(RestMethods::GET,'/'.($this->queryParams->multiple?'mp':'').'fs/tests/'.$file->name.($this->queryParams->multiple?'?mode=merge':''),array('X-Rest-Local-Cache'=>'disabled')));
					$test=$test->getResponse();
					if($test->code!=RestCodes::HTTP_200)
						{
						throw new RestException(RestCodes::HTTP_500,'Can\'t access the test: '.$file->name);
						}
					else
						{
						$entry=new stdClass();
						$req=new RestRequest(RestMethods::getMethodFromString($test->content->request->method),$test->content->request->uri,array('X-Rest-Local-Cache'=>'disabled'));
						if(isset($test->content->request->headers))
							{
							foreach($test->content->request->headers as $header)
								{
								$req->setHeader($header->name,$header->value);
								}
							}
						if(isset($test->content->request->content))
							$req->content=$test->content->request->content;
						$res=new RestResource($req);
						$res=$res->getResponse();
						$entry->title=$test->content->title;
						$entry->file=$file->name;
						$entry->result=$test->content->request->method.' '.$test->content->request->uri .' : '.$res->code.' '.constant('RestCodes::HTTP_'.$res->code.'_MSG');
						$entry->errors=new xcObjectCollection();
						if($res->code!=$test->content->response->code)
							{
							$entry->errors->append('Unexpected result : HTTP response code is '.$res->code.', '.$test->content->response->code.' expected.');
							}
						if(isset($test->content->response->headers))
							{
							foreach($test->content->response->headers as $header)
								{
								if($res->getHeader($header->name)&&$res->getHeader($header->name)!=$header->value)
									$entry->errors->append('Unexpected result : HTTP response header '.$header->name.' value is "'.$res->getHeader($header->name).'". Expected: "'.$header->value.'"');
								}
							}
						if(isset($test->content->response->content)&&$test->content->response->content!==''&&$res->content!=$test->content->response->content)
							{
							if(!$test->content->response->content instanceof stdClass) // Should create a comparison function for dataobjects
								$entry->errors->append('Unexpected result : HTTP response content differs.');
							}
						if($this->queryParams->verbose||$entry->errors->count())
							$response->content->tests->append($entry);
						}
					}
				}
			}
		$response->setHeader('Content-Type','application/internal');
		return $response;
		}
	}
RestUnitDriver::$drvInf=RestUnitDriver::getDrvInf();