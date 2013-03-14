<?php
class RestDbServerDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new xcDataObject();
		$drvInf->name='Db: Server Driver';
		$drvInf->description='List each databases of the SQL server.';
		$drvInf->usage='/db(.ext)?';
		$drvInf->methods=new xcDataObject();
		$drvInf->methods->options=new xcDataObject();
		$drvInf->methods->options->outputMimes='application/internal';
		$drvInf->methods->head=$drvInf->methods->get=new xcDataObject();
		$drvInf->methods->get->outputMimes='application/internal';
		return $drvInf;
		}
	function head()
		{
		$this->core->db->query('SHOW DATABASES');
		if(!$this->core->db->numRows())
			throw new RestException(RestCodes::HTTP_410,'No databases found for this SQL server.','Check MySQL user permissions on it.');

		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'application/internal')
			);
		}
	function get()
		{
		$response=$this->head();
		$response->content=new xcDataObject();
		$response->content->databases=new xcObjectCollection();
		while ($row = $this->core->db->fetchArray())
			{
			$entry=new xcDataObject();
			$entry->database= $row['Database'];
			$response->content->databases->append($entry);
			}
		$response->setHeader('Content-Type','application/internal');
		return $response;
		}
	function post()
		{
		throw new RestException(RestCodes::HTTP_501,'Not done yet');
		// Could allow database creations but with no IF NOT EXISTS directive...
		// Could also allow to change collation or others...
		}
	}
RestDbServerDriver::$drvInf=RestDbServerDriver::getDrvInf();