<?php
class RestXgpsNearDriver extends RestVarsDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=parent::getDrvInf(RestMethods::GET|RestMethods::POST);
		$drvInf->name='Xgps: Near user Driver';
		$drvInf->description='Show the username\'s nearest devices.';
		$drvInf->usage='/xgps/(username)/near'.$drvInf->usage;
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='limit';
		$drvInf->methods->get->queryParams[0]->type='number';
		$drvInf->methods->get->queryParams[0]->filter='int';
		$drvInf->methods->get->queryParams[0]->value='5';
		$drvInf->methods->get->queryParams[0]->description='Numbers of collegues returned.';
		return $drvInf;
		}
	function get()
		{
		$res=new RestResource(new RestRequest(RestMethods::GET,'/xgps/all.dat'));
		$res=$res->getResponse();
		if($res->code!=RestCodes::HTTP_200)
			return $res;
		$response=new RestResponseVars(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		$this->lat=0;
		$this->lng=0;
		$resEntries=$res->vars->entries;
		foreach($resEntries as $entry)
			{
			if($entry->login==$this->request->user)
				{
				$vals=explode(',',$entry->gps);
				$this->lat=$vals[3];
				$this->lng=$vals[2];
				}
			}
		if(!($this->lat&&$this->lng))
			{
			throw new RestException(RestCodes::HTTP_400,'User "'
				.$this->request->user.'" have no recent position to use.');
			}
		$resEntries->uasort(array($this, 'sort'));
		$response->vars->entries=new MergeArrayObject();
		$i=0;
		foreach($resEntries as $entry)
			{
			if($i>$this->queryParams->limit)
				break;
			if($entry->login==$this->request->user)
				{
				$this->queryParams->limit++;
				continue;
				}
			$response->vars->entries->append($entry);
			$i++;
			}
		return $response;
		}
	function sort($a, $b)
		{
		if(((!isset($a->gps))||!$a->gps)&&((!isset($b->gps))||!$b->gps))
			{
			return 0;
			}
		else if($a->gps&&((!isset($b->gps))||!$b->gps))
			{
			return -1;
			}
		else if($b->gps&&((!isset($a->gps))||!$a->gps))
			{
			return 1;
			}
		else
			{
			$a=explode(',',$a->gps);
			$b=explode(',',$b->gps);
			if(abs($a[3]-$this->lat)+abs($a[2]-$this->lng)<abs($b[3]-$this->lat)+abs($b[2]-$this->lng))
				return -1;
			else
				return 1;
			}
		}
	}
RestXgpsNearDriver::$drvInf=RestXgpsNearDriver::getDrvInf();