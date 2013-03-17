<?php
class RestSqlDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='Sql: Driver';
		$drvInf->description='See the groups list.';
		$drvInf->usage='/sql(.ext)?';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='text/varstream';
		$drvInf->methods->head=new stdClass();
		$drvInf->methods->head->outputMimes='text/varstream';
		$drvInf->methods->post=new stdClass();
		$drvInf->methods->post->outputMimes='text/varstream';
		return $drvInf;
		}
	function head()
		{
		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'text/varstream')
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
		catch(Exception $e)
			{
			throw new RestException(RestCodes::HTTP_400,'Got a SQL error ('.$e->__toString().')');
			}
		$response->content=new stdClass();
		$response->content->results=new MergeArrayObject();
		while ($row = $this->core->db->fetchArray())
			{
			$line=new MergeArrayObject();
			foreach($row as $key => $value)
				{
				$row=new stdClass();
				$row->name = $key;
				$row->value = $value;
				$line->append($row);
				}
			$response->content->results->append($line);
			}
		$response->content->affectedRows=new stdClass();
		$response->content->affectedRows=$this->core->db->affectedRows();
		$response->setHeader('Content-Type','text/varstream');
		return $response;	
		}
	}
RestSqlDriver::$drvInf=RestSqlDriver::getDrvInf();