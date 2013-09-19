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
		if($this->core->auth->source=='db')
			{
			$this->core->db->selectDb($this->core->database->database);
			$this->core->db->query('SELECT login FROM users');
			}
		return new RestVarsResponse(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	function get()
		{
		$response=$this->head();
		$vars=new stdClass();
		$vars->users=new MergeArrayObject();
		if($this->core->auth->source=='none')
			{
			$entry=new stdClass();
			$entry->login = 'webmaster';
			$vars->users->append($entry);
			}
		else if($this->core->auth->source=='conf')
			{
			if(isset($this->core->auth->users))
				{
				$vars->users=$this->core->auth->users;
				}
			}
		else if($this->core->auth->source=='db')
			{
			while ($row = $this->core->db->fetchArray())
				{
				$entry=new stdClass();
				$entry->login = $row['login'];
				$vars->users->append($entry);
				}
			}
		else
			{
			throw new RestException(RestCodes::HTTP_500,
				'User source has not been set or has an unsupported value.');
			}
		return $response;
		}
	}
