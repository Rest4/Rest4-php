<?php
class RestSqlDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new xcDataObject();
		$drvInf->name='Sql: Driver';
		$drvInf->description='See the groups list.';
		$drvInf->usage='/sql(.ext)?';
		$drvInf->methods=new xcDataObject();
		$drvInf->methods->options=new xcDataObject();
		$drvInf->methods->options->outputMimes='application/internal';
		$drvInf->methods->head=new xcDataObject();
		$drvInf->methods->head->outputMimes='application/internal';
		$drvInf->methods->post=new xcDataObject();
		$drvInf->methods->post->outputMimes='application/internal';
		return $drvInf;
		}
	function head()
		{
		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'application/internal')
			);
		}
	function post()
		{
		$response=$this->head();
		$this->core->db->selectDb($this->core->database->database);
		try
			{
			$this->core->db->query($this->request->content);
			}
		catch(xcException $e)
			{
			throw new RestException(RestCodes::HTTP_400,'Got a SQL error ('.$e->__toString().')');
			}
		$response->content=new xcDataObject();
		$response->content->results=new xcObjectCollection();
		while ($row = $this->core->db->fetchArray())
			{
			$line=new xcObjectCollection();
			foreach($row as $key => $value)
				{
				$row=new xcDataObject();
				$row->name = $key;
				$row->value = $value;
				$line->append($row);
				}
			$response->content->results->append($line);
			}
		$response->content->affectedRows=new xcDataObject();
		$response->content->affectedRows=$this->core->db->affectedRows();
		$response->setHeader('Content-Type','application/internal');
		return $response;	
		}
	}
RestSqlDriver::$drvInf=RestSqlDriver::getDrvInf();