<?php
class RestSqlToCsvResponse extends RestStreamedResponse
	{
	private $query;
	private $labels;
	private $i=-1;
	function __construct($code=RestCodes::HTTP_200, $headers=array(), $request, $labels,$filesPath='',$downloadFilename='')
		{
		$headers['Content-Type']='text/csv';
		if($downloadFilename)
			$headers['Content-Disposition']='attachment; filename="'.$downloadFilename.'"';
		parent::__construct($code, $headers);
		$this->labels=$labels;
		$this->filesPath=$filesPath;
		$this->core->db->selectDb($this->core->database->database);
		$this->query=$this->core->db->query($request);
		if(!$this->core->db->numRows())
			throw new RestException(RestCodes::HTTP_410,'No datas available with those criterias.');
		}
	function pump()
		{
		if($this->i===-1)
			{
			$line=implode(';',$this->labels).($this->filesPath?';files':'')."\r\n";
			}
		else
			{
			$line='';
			if($row=$this->core->db->fetchArray($this->query))
				{
				foreach($this->labels as $label)
					{
					if(!isset($row[$label]))
						$line.=';';
					else if(is_numeric($row[$label]))
						$line.=$row[$label].';';
					else
						$line.='"'.str_replace('"','\"',html_entity_decode($row[$label],ENT_QUOTES,'UTF-8')).'"'.';'; //|ENT_HTML401
					}
				if($this->filesPath&&isset($row['id']))
					{
					$res=new RestResource(new RestRequest(RestMethods::GET,'/fsi/db/'.$this->filesPath.'/'.$row['id'].'/files.dat?mode=light'));
					$content=$res->getResponse()->getContents();
					$line.=($res->code==RestCodes::HTTP_200&&$content->files?$content->files->count():'0').';';
					}
				$line.="\r\n";
				}
			}
		$this->i++;
		return $line;
		}
	}
