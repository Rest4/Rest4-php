<?php
class RestUsersUserDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='Users: User Driver';
		$drvInf->description='See the user informations.';
		$drvInf->usage='/users/user(.ext)?type=(normal|restricted)';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='text/varstream';
		$drvInf->methods->head=$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='text/varstream';
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='type';
		$drvInf->methods->get->queryParams[0]->value='normal';
		$drvInf->methods->get->queryParams[0]->required=false;
		$drvInf->methods->put=$drvInf->methods->get;
		$drvInf->methods->delete=$drvInf->methods->get;
		return $drvInf;
		}
	function head()
		{
		if($this->core->server->auth!='none')
			{
			$this->core->db->selectDb($this->core->database->database);
			$this->core->db->query('SELECT users.id as userid, login, firstname, lastname,'
				.' email, organization, groups.name as groupname, groups.id as groupid,'
				.' lastconnection FROM users LEFT JOIN groups ON groups.id=users.group'
				.' WHERE login="'.$this->request->uriNodes[1].'"');
			if(!$this->core->db->numRows())
				throw new RestException(RestCodes::HTTP_410,'This user doesn\'t exist, uh ?');
			}
		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'text/varstream')
			);
		}
	function get()
		{
		$response=$this->head();
		$response->content=new stdClass();
		$response->content->user=new stdClass();
		if($this->core->server->auth=='none')
			{
			$response->content->user->userId = 1;
			$response->content->user->login = 'webmaster';
			$response->content->user->firstName = 'Unknow';
			$response->content->user->lastName = 'Unknow';
			$response->content->user->organization = 1;
			$response->content->user->groupName = 'webmasters';
			$response->content->user->groupId = 1;
			}
		else
			{
			$response->content->user->userId = $this->core->db->result('userid');
			$response->content->user->login = $this->core->db->result('login');
			$response->content->user->firstName = $this->core->db->result('firstname');
			$response->content->user->lastName = $this->core->db->result('lastname');
			$response->content->user->email = $this->core->db->result('email');
			$response->content->user->organization = $this->core->db->result('organization');
			$response->content->user->groupName = $this->core->db->result('groupname');
			$response->content->user->groupId = $this->core->db->result('groupid');
			if($this->queryParams->type!='restricted')
				$response->content->user->lastconnection = $this->core->db->result('lastconnection');
			}
		$response->setHeader('Content-Type','text/varstream');
		return $response;
		}
	function put()
		{
		if($this->core->server->auth=='none')
			throw new RestException(RestCodes::HTTP_400,'Unable to modify the default user');
		try
			{
			$response=$this->head();
			}
		catch(RestException $e)
			{
			if($e->code==RestCodes::HTTP_410)
				$response=new RestResponse(RestCodes::HTTP_410);
			else
				throw $e;
			}
		try
			{
			if($response->code==RestCodes::HTTP_200)
				{
				$this->core->db->query('UPDATE users SET firstname="'.$this->request->content->user->firstName
					.'", lastname="'.$this->request->content->user->lastName
					.'", email="'.$this->request->content->user->email
					.'", `group`="'.$this->request->content->user->groupId
					.'", lastconnection=NOW() WHERE login="'.$this->request->uriNodes[1].'"');
				}
			else
				{
				$this->core->db->query('INSERT INTO users (login, firstname, lastname, email, group, lastconnection)'
					.' VALUES ("'.$this->request->content->user->login.'","'.$this->request->content->user->firstName
					.'","'.$this->request->content->user->lastName.'","'.$this->request->content->user->email
					.'","'.$this->request->content->user->groupId.'",NOW())');
				$response->content->user->userId = $this->core->db->insertId();
				}
			}
		catch(Exception $e)
			{
			throw new RestException(RestCodes::HTTP_500,'Got a database error',$e->__toString());
			}
		$response=$this->get();
		$response->code=RestCodes::HTTP_201;
		return $response;
		}
	function delete()
		{
		if($this->core->server->auth=='none')
			throw new RestException(RestCodes::HTTP_400,'Unable to delete the default user');
		$this->core->db->query('DELETE FROM users WHERE login="'.$this->request->uriNodes[1].'"');
		return new RestResponse(RestCodes::HTTP_200,array('Content-Type','text/varstream'));
		}
	}
RestUsersUserDriver::$drvInf=RestUsersUserDriver::getDrvInf();