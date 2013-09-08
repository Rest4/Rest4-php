<?php
class RestUsersDriver extends RestVarsDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=parent::getDrvInf(RestMethods::GET);
		$drvInf->name='Users: Users Driver';
		$drvInf->description='See the users list.';
		$drvInf->usage='/users'.$drvInf->usage;
		return $drvInf;
		}
	function head()
		{
		if($this->core->server->auth!='none'&&$this->core->server->auth!='default')
			{
			$this->core->db->selectDb($this->core->database->database);
			$this->core->db->query('SELECT login FROM users');
			if(!$this->core->db->numRows())
				throw new RestException(RestCodes::HTTP_410,'There\'s no users, uh ?');
			}
		return new RestVarsResponse(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	function get()
		{
		$response=$this->head();
		$vars=new stdClass();
		$vars->users=new MergeArrayObject();
		if($this->core->server->auth=='none')
			{
			$entry=new stdClass();
			$entry->login = 'webmaster';
			$vars->users->append($entry);
			}
		else if($this->core->server->auth=='default')
			{
			$vars->users=$this->core->auth;
			}
		else
			{
			while ($row = $this->core->db->fetchArray())
				{
				$entry=new stdClass();
				$entry->login = $row['login'];
				$vars->users->append($entry);
				}
			}
		return $response;
		}
	}
