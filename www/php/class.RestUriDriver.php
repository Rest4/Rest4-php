<?php
class RestUriDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='Uri: Diag';
		$drvInf->description='Show how the uri is decomposed by the request object, helps for unit tests.';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='application/internal';
		$drvInf->methods->head=$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='application/internal';
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='param1';
		$drvInf->methods->get->queryParams[0]->value='value';
		$drvInf->methods->get->queryParams[1]=new stdClass();
		$drvInf->methods->get->queryParams[1]->name='param2';
		$drvInf->methods->get->queryParams[1]->type='number';
		$drvInf->methods->get->queryParams[1]->filter='int';
		$drvInf->methods->get->queryParams[1]->value='0';
		$drvInf->methods->get->queryParams[1]=new stdClass();
		$drvInf->methods->get->queryParams[1]->name='param3';
		$drvInf->methods->get->queryParams[1]->type='number';
		$drvInf->methods->get->queryParams[1]->filter='int';
		$drvInf->methods->get->queryParams[1]->value='5';
		$drvInf->methods->get->queryParams[2]=new stdClass();
		$drvInf->methods->get->queryParams[2]->name='multiparam';
		$drvInf->methods->get->queryParams[2]->type='number';
		$drvInf->methods->get->queryParams[2]->filter='int';
		$drvInf->methods->get->queryParams[2]->multiple=true;
		$drvInf->methods->get->queryParams[3]=new stdClass();
		$drvInf->methods->get->queryParams[3]->name='unordmultiparam';
		$drvInf->methods->get->queryParams[3]->type='number';
		$drvInf->methods->get->queryParams[3]->filter='int';
		$drvInf->methods->get->queryParams[3]->multiple=true;
		$drvInf->methods->get->queryParams[3]->orderless=true;
		$drvInf->methods->get->queryParams[3]->value=new MergeArrayObject();
		$drvInf->methods->get->queryParams[4]=new stdClass();
		$drvInf->methods->get->queryParams[4]->name='param4';
		$drvInf->methods->get->queryParams[4]->type='number';
		$drvInf->methods->get->queryParams[4]->filter='int';
		$drvInf->methods->get->queryParams[4]->value=1;
		$drvInf->methods->get->queryParams[5]=new stdClass();
		$drvInf->methods->get->queryParams[5]->name='param5';
		$drvInf->methods->get->queryParams[5]->type='text';
		$drvInf->methods->get->queryParams[5]->filter='cdata';
		$drvInf->methods->get->queryParams[5]->value='';
		return $drvInf;
		}
	function get()
		{
		$response=new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'application/internal')
			);
		$obj=new stdClass();
		$obj->nodes=$this->request->uriNodes;
		$obj->controller=$this->request->controller;
		$obj->filePath=$this->request->filePath;
		$obj->fileName=$this->request->fileName;
		$obj->isFolder=$this->request->isFolder;
		$obj->fileExt=$this->request->fileExt;
		$obj->queryString=$this->request->queryString;
		$obj->queryParams=new MergeArrayObject();
		foreach($this::$drvInf->methods->get->queryParams as $queryParam)
			{
			if(isset($this->queryParams->{$queryParam->name}))
				{
				$qP=new stdClass();
				$qP->name=$queryParam->name;
				$qP->value=$this->queryParams->{$queryParam->name};
				$obj->queryParams->append($qP);
				}
			}
		$response=new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'application/internal')
			);
		$response->content=$obj;
		return $response;
		}
	}
RestUriDriver::$drvInf=RestUriDriver::getDrvInf();