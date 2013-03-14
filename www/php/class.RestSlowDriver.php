<?php
class RestSlowDriver extends RestDriver
	{
	private $_uri;
	private $_c;
	private $_c_headers;
	private $_c_content;
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new xcDataObject();
		$drvInf->name='Slow: Driver';
		$drvInf->description='Slow down the response output by the given delay.';
		$drvInf->usage='/slow?uri=uri&delay=([0-9]+)';
		$drvInf->methods=new xcDataObject();
		$drvInf->methods->options=new xcDataObject();
		$drvInf->methods->options->outputMimes='application/internal';
		$drvInf->methods->head=$drvInf->methods->get=new xcDataObject();
		$drvInf->methods->get->outputMimes='*';
		$drvInf->methods->get->queryParams=new xcObjectCollection();
		$drvInf->methods->get->queryParams[0]=new xcDataObject();
		$drvInf->methods->get->queryParams[0]->name='uri';
		$drvInf->methods->get->queryParams[0]->filter='uri';
		$drvInf->methods->get->queryParams[0]->required=true;
		$drvInf->methods->get->queryParams[1]=new xcDataObject();
		$drvInf->methods->get->queryParams[1]->name='delay';
		$drvInf->methods->get->queryParams[1]->type='number';
		$drvInf->methods->get->queryParams[1]->filter='int';
		$drvInf->methods->get->queryParams[1]->value=1000;
		$drvInf->methods->post=$drvInf->methods->get;
		$drvInf->methods->put=$drvInf->methods->get;
		$drvInf->methods->delete=$drvInf->methods->get;
		return $drvInf;
		}
	function head()
		{
		$this->request->uri=$this->queryParams->uri;
		//$this->request->parseUri();
		$resource=new RestResource($this->request);
		return $ressource->getResponse();
		}
	function get()
		{
		$this->request->uri=$this->queryParams->uri;
		//$this->request->parseUri();
		$resource=new RestResource($this->request);
		return new RestResponseSlow($resource->getResponse(),$this->queryParams->delay);
		}
	}
RestSlowDriver::$drvInf=RestSlowDriver::getDrvInf();