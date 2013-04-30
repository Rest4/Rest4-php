<?php
class RestXgpsCleanupDriver extends RestVarsDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=parent::getDrvInf(RestMethods::GET|RestMethods::POST);
		$drvInf->name='Xgps: Cleanup Driver';
		$drvInf->description='Erase old GPS log files.';
		$drvInf->usage='/xgps/cleanup'.$drvInf->usage.'?old=([0-9]+)';
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='old';
		$drvInf->methods->get->queryParams[0]->type='number';
		$drvInf->methods->get->queryParams[0]->filter='int';
		$drvInf->methods->get->queryParams[0]->value=90;
		$drvInf->methods->get->queryParams[0]->description='Erase log older than n days.';
		return $drvInf;
		}
	function get()
		{
		$res=new RestResource(new RestRequest(RestMethods::GET,'/fsi/log.dat'));
		$res=$res->getResponse();
		if($res->code!=RestCodes::HTTP_200)
			return $res;
		$response=new RestVarsResponse(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		$response->vars->files=new MergeArrayObject();
		foreach($res->vars->files as $file)
			{
			if(strpos($file->name,'x1-')===0)
				{
				$name=substr($file->name,3);
				$date=substr($name,strrpos($name,'-')+1,8);
				if(mktime(0, 0, 0, $date[4].$date[5]  , $date[6].$date[7], $date[0].$date[1].$date[2].$date[3])<time()-($this->queryParams->old*24*60*60))
					{
					$entry=new stdClass();
					$entry->name=$file->name;
					$entry->date=$date[0].$date[1].$date[2].$date[3].'-'.$date[4].$date[5].'-'.$date[6].$date[7];
					$response->vars->files->append($entry);
					}
				}
			}
		return $response;
		}
	function post() // Should use delete method instead
		{
		$response=$this->get();
		foreach($response->vars->files as $file)
			{
			$res=new RestResource(new RestRequest(RestMethods::DELETE,'/fs/log/'.$file->name));
			$res=$res->getResponse();
			if($res->code!=RestCodes::HTTP_410)
				return $res;
			}
		return $response;
		}
	}
