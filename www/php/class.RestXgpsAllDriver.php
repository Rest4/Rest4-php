<?php
class RestXgpsAllDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='Xgps: All Driver';
		$drvInf->description='Show last positions of each devices.';
		$drvInf->usage='/xgps/all.dat?limit=([0-9]+)';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='application/internal';
		$drvInf->methods->head=$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='application/internal';
		$drvInf->methods->get->queryParams=new xcObjectCollection();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='limit';
		$drvInf->methods->get->queryParams[0]->type='number';
		$drvInf->methods->get->queryParams[0]->filter='int';
		$drvInf->methods->get->queryParams[0]->value='10';
		$drvInf->methods->get->queryParams[0]->description='Numbers of days back the last position is searched for.';
		return $drvInf;
		}
	function get()
		{
		$res=new RestResource(new RestRequest(RestMethods::GET,'/db/vigisystem/vehicles/list.dat?mode=extend&limit=0'));
		$res=$res->getResponse();
		if($res->code!=RestCodes::HTTP_200)
			return $res;
		$response=new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'application/internal')
			);
		$response->content=new stdClass();
		$response->content->entries=new xcObjectCollection();
		foreach($res->content->entries as $value)
			{
			$entry=new stdClass();
			$entry->label=$value->user_firstname.' '.$value->user_lastname;
			$entry->login=$value->user_login;
			$filename='../log/x1-'.$value->device.'-'.date("Ymd").'.log';
			$i=0;
			while($i<$this->queryParams->limit&&!@file_exists($filename))
				{
				$i++;
				$filename='../log/x1-'.$value->device.'-'.date("Ymd",mktime(0, 0, 0, date("m")  , date("d")-$i, date("Y"))).'.log';
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
			$response->content->entries->append($entry);
			}
		return $response;
		}
	}
RestXgpsAllDriver::$drvInf=RestXgpsAllDriver::getDrvInf();