<?php
class RestDbServerDriver extends RestVarsDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=parent::getDrvInf(RestMethods::GET|RestMethods::POST);
		$drvInf->name='Db: Server Driver';
		$drvInf->description='List each databases of the SQL server.';
		$drvInf->usage='/db'.$drvInf->usage;
		return $drvInf;
		}
	function head()
		{
		$this->core->db->query('SHOW DATABASES');
		if(!$this->core->db->numRows())
			throw new RestException(RestCodes::HTTP_410,
				'No databases found for this SQL server.','Check MySQL user permissions on it.');
		return new RestResponseVars(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	function get()
		{
		$response=$this->head();
		$response->vars->databases=new MergeArrayObject(array(),
				MergeArrayObject::ARRAY_MERGE_RESET|MergeArrayObject::ARRAY_MERGE_POP);
		while ($row = $this->core->db->fetchArray())
			{
			$entry=new stdClass();
			$entry->database= $row['Database'];
			$response->vars->databases->append($entry);
			}
		return $response;
		}
	function post()
		{
		throw new RestException(RestCodes::HTTP_501,'Not done yet');
		// Could allow database creations but with no IF NOT EXISTS directive...
		// Could also allow to change collation or others...
		}
	}
