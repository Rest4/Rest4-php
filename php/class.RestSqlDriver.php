<?php
class RestSqlDriver extends RestVarsDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=parent::getDrvInf(RestMethods::POST);
		$drvInf->name='Sql: Driver';
		$drvInf->description='See the groups list.';
		$drvInf->usage='/sql'.$drvInf->usage;
		return $drvInf;
		}
	function head()
		{
		return new RestResponseVars(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
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
			throw new RestException(RestCodes::HTTP_400,'Got a SQL error.',$e->__toString());
			}
		$response->vars=new stdClass();
		$response->vars->results=new MergeArrayObject();
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
			$response->vars->results->append($line);
			}
		$response->vars->affectedRows=new stdClass();
		$response->vars->affectedRows=$this->core->db->affectedRows();
		return $response;	
		}
	}
