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
		$drvInf->methods->options->outputMimes='text/varstream';
		$drvInf->methods->head=$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='text/varstream';
		return $drvInf;
		}
	function head()
		{
		if($this->core->auth!='none')
			{
			$this->core->db->selectDb($this->core->database->database);
			$this->core->db->query('SELECT login FROM users');
			if(!$this->core->db->numRows())
				throw new RestException(RestCodes::HTTP_410,'There\'s no users, uh ?');
			}
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
		if($this->core->auth=='none')
			{
			$entry=new stdClass();
			$entry->login = 'webmaster';
			$response->content->users->append($entry);
			}
		else
			{
			while ($row = $this->core->db->fetchArray())
				{
				$entry=new stdClass();
				$entry->login = $row['login'];
				$response->content->users->append($entry);
				}
			}
		$response->setHeader('Content-Type','text/varstream');
		return $response;
		}
	}
RestUsersDriver::$drvInf=RestUsersDriver::getDrvInf();