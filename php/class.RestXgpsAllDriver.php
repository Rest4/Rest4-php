<?php
class RestXgpsAllDriver extends RestVarsDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=parent::getDrvInf(RestMethods::GET);
		$drvInf->name='Xgps: All Driver';
		$drvInf->description='Show last positions of each devices.';
		$drvInf->usage='/xgps/all'.$drvInf->usage.'?limit=([0-9]+)';
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='limit';
		$drvInf->methods->get->queryParams[0]->type='number';
		$drvInf->methods->get->queryParams[0]->filter='int';
		$drvInf->methods->get->queryParams[0]->value='10';
		$drvInf->methods->get->queryParams[0]->description='Numbers of days back'
			+' the last position is searched for.';
		return $drvInf;
		}
	function get()
		{
		$res=new RestResource(new RestRequest(RestMethods::GET,
			'/db/vigisystem/vehicles/list.dat?field=*&field=userLinkUsersId.label'
			.'&field=userLinkUsersId.login&limit=0'));
		$res=$res->getResponse();
		if($res->code!=RestCodes::HTTP_200)
			return $res;
		$response=new RestVarsResponse(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		$response->vars->entries=new MergeArrayObject();
		foreach($res->vars->entries as $value)
			{
			$entry=new stdClass();
			$entry->label=$value->user->firstname.' '.$value->user->lastname;
			$entry->login=$value->user->login;
			$entry->muted=$value->muted;
			$filename='./log/x1-'.$value->device.'-'.date("Ymd").'.log';
			$i=0;
			while($i<$this->queryParams->limit&&!@file_exists($filename))
				{
				$i++;
				$filename='./log/x1-'.$value->device.'-'
					.date("Ymd",mktime(0, 0, 0, date("m")  , date("d")-$i, date("Y"))).'.log';
				}
			if($i<$this->queryParams->limit)
				{
				$entry->date=date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-$i, date("Y")));
				$handle = @fopen($filename, 'r');
				if($handle)
				while (($buffer = fgets($handle)) !== false)
					{
					if(strlen($buffer)>40)
						$entry->gps=$buffer;
					}
				fclose($handle);
				}
			$entry->log=$filename;
			$response->vars->entries->append($entry);
			}
		return $response;
		}
	}
