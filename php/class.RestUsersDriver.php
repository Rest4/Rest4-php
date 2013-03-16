<?php
class RestUsersDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='Users: Users Driver';
		$drvInf->description='See the users list.';
		$drvInf->usage='/users(.ext)?';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='application/internal';
		$drvInf->methods->head=$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='application/internal';
		return $drvInf;
		}
	function head()
		{
		$this->core->db->selectDb($this->core->database->database);
		$this->core->db->query('SELECT login FROM users');
		if(!$this->core->db->numRows())
			throw new RestException(RestCodes::HTTP_410,'There\'s no users, uh ?');
		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'text/plain')
			);
		}
	function get()
		{
		$response=$this->head();
		$response->content=new stdClass();
		$response->content->users=new MergeArrayObject();
		while ($row = $this->core->db->fetchArray())
			{
			$entry=new stdClass();
			$entry->login = $row['login'];
			$response->content->users->append($entry);
			}
		$response->setHeader('Content-Type','application/internal');
		return $response;
		}
	}
RestUsersDriver::$drvInf=RestUsersDriver::getDrvInf();