<?php
class RestXgpsDirectionsDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='Xgps: User Directions Driver';
		$drvInf->description='Show GPS direction for the given user.';
		$drvInf->usage='/xgps/(username)/directions.dat?day=yyyy-mm-dd';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='application/internal';
		$drvInf->methods->head=$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->queryParams=new xcObjectCollection();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='day';
		$drvInf->methods->get->queryParams[0]->type='date';
		$drvInf->methods->get->queryParams[0]->filter='date';
		$drvInf->methods->get->queryParams[0]->required=true;
		$drvInf->methods->get->queryParams[0]->description='The day of the directions.';
		$drvInf->methods->get->outputMimes='application/internal';
		return $drvInf;
		}
	function get()
		{
		$response=new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'application/internal')
			);
		$response->content=new stdClass();
		$this->core->db->query('SELECT vehicles.device FROM users LEFT JOIN vehicles ON vehicles.user=users.id WHERE users.login="'.$this->request->user.'"');
		if(!$this->core->db->numRows())
			throw new RestException(RestCodes::HTTP_400,'User "'.$this->request->user.'" does not exist.');
		if(!$response->content->device=$this->core->db->result('device'))
			throw new RestException(RestCodes::HTTP_400,'User "'.$this->request->user.'" have no device to ear.');
		$vals=explode('-',$this->queryParams->day);
		$filename='../log/x1-'.$response->content->device.'-'.date("Ymd",mktime(0, 0, 0, $vals[1] , $vals[2], $vals[0])).'.log';
		$response->content->gps=new xcObjectCollection();
			// vals : hour(0),device(1),lng(2),lat(3),speed(4),heading(5),(6),sats(7)
		if(file_exists($filename))
			{
			$content=@file_get_contents($filename);
			$lines=explode("\n",$content);
			$j=sizeof($lines);
			$i=0;
			// Seaching for the start point
			while($i<$j)
				{
				$vals=explode(',',$lines[$i]);
				if($vals[7]==0||$vals[4]==0)
					{
					$i++;
					continue;
					}
				$entry=new stdClass();
				$entry->type='start';
				$entry->h=$vals[0];
				$entry->lat=$vals[3];
				$entry->lng=$vals[2];
				$entry->speed=$vals[4];
				$entry->head=$vals[5];
				$entry->alt=$vals[6];
				$entry->sat=$vals[7];
				$entry->line=$i;
				$response->content->gps->append($entry);
				$i++;
				break;
				}
			// Treating all points
			while($i<$j&&$lines[$i]!='')
				{
				$vals=explode(',',$lines[$i]);
				// Ignoring bad points
				if($vals[7]==0||$vals[2]==''||$vals[3]==''||(isset($lastentry)&&$lastentry->lat==$vals[3]&&$lastentry->lng==$vals[2]))
					{
					$i++; continue;
					}
				$lastentry=$entry;
				$entry=new stdClass();
				// Detecting stops
				if($vals[4]==0)
					{
					if($lastentry->type=='stop')
						{
						$ts=explode(':',$vals[0]);
						$ts2=explode(':',$lastentry->rh);
						$d=($ts[0]*3600+$ts[1]*60+$ts[2])-($ts2[0]*3600+$ts2[1]*60+$ts2[2]);
						$lastentry->d+=$d;
						$lastentry->rh=$vals[0];
						$entry=$lastentry;
						$i++; continue;
						}
					$entry->type='stop';
					$ts=explode(':',$vals[0]);
					$ts2=explode(':',$lastentry->h);
					$d=($ts[0]*3600+$ts[1]*60+$ts[2])-($ts2[0]*3600+$ts2[1]*60+$ts2[2]);
					$entry->d=$d;
					$entry->h=$lastentry->h;
					$entry->rh=$vals[0];
					}
				else
					{
					// Flagging hi-speed points
					if($vals[4]>110)
						$entry->type='speeding';
					else
						$entry->type='running';
					$entry->h=$vals[0];
					// Ignoring stops during less than 3 minutes
					if($lastentry->type=='stop')
						{
						$ts=explode(':',$vals[0]);
						$ts2=explode(':',$lastentry->rh);
						$d=($ts[0]*3600+$ts[1]*60+$ts[2])-($ts2[0]*3600+$ts2[1]*60+$ts2[2]);
						$lastentry->d+=$d;
						if($lastentry->d<180)
							$lastentry->type='running';
						}
					}
				$entry->lat=$vals[3];
				$entry->lng=$vals[2];
				$entry->speed=$vals[4];
				$entry->head=$vals[5];
				$entry->alt=$vals[6];
				$entry->sat=$vals[7];
				$entry->line=$i;
				$response->content->gps->append($entry);
				$i++;
				}
			}
		return $response;
		}
	}
RestXgpsDirectionsDriver::$drvInf=RestXgpsDirectionsDriver::getDrvInf();