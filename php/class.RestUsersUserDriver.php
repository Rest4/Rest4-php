<?php
class RestUsersUserDriver extends RestVarsDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=parent::getDrvInf(RestMethods::GET|RestMethods::DELETE);
		$drvInf->name='Users: User Driver';
		$drvInf->description='See the user informations.';
		$drvInf->usage='/users/user'.$drvInf->usage.'?type=(normal|restricted)';
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='type';
		$drvInf->methods->get->queryParams[0]->value='normal';
		$drvInf->methods->get->queryParams[0]->required=false;
		return $drvInf;
		}
	function head()
		{
		if($this->core->auth->source=='conf')
			{
			if(!isset($this->core->auth->users,
				$this->core->auth->users->{$this->request->uriNodes[1]}))
				{
				throw new RestException(RestCodes::HTTP_410,
					'This user doesn\'t exist.');
				}
			}
		else if($this->core->auth->source=='db')
			{
			$this->core->db->selectDb($this->core->database->database);
			$this->core->db->query('SELECT users.id as userid, login, firstname, lastname,'
				.' email, organization, groups.name as groupname, groups.id as groupid,'
				.' lastconnection FROM users LEFT JOIN groups ON groups.id=users.group'
				.' WHERE login="'.$this->request->uriNodes[1].'"');
			if(!$this->core->db->numRows())
				{
				throw new RestException(RestCodes::HTTP_410,
					'This user doesn\'t exist.');
				}
			}
		else if($this->core->auth->source!='none')
			{
			throw new RestException(RestCodes::HTTP_500,
				'User source has not been set or has an unsupported value.');
			}
		return new RestVarsResponse(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	function get()
		{
		$response=$this->head();
		$response->vars->user=new stdClass();
		if($this->core->auth->source=='none')
			{
			$response->vars->user->userId = 1;
			$response->vars->user->login = 'webmaster';
			$response->vars->user->firstName = 'Unknow';
			$response->vars->user->lastName = 'Unknow';
			$response->vars->user->organization = 1;
			$response->vars->user->groupName = 'webmasters';
			$response->vars->user->groupId = 1;
			}
		else if($this->core->auth->source=='conf')
			{
			$response->vars->user=
				$this->core->auth->users->{$this->request->uriNodes[1]};
			}
		else if($this->core->auth->source=='db')
			{
			$response->vars->user->userId = $this->core->db->result('userid');
			$response->vars->user->login = $this->core->db->result('login');
			$response->vars->user->firstName = $this->core->db->result('firstname');
			$response->vars->user->lastName = $this->core->db->result('lastname');
			$response->vars->user->email = $this->core->db->result('email');
			$response->vars->user->organization = $this->core->db->result('organization');
			$response->vars->user->groupName = $this->core->db->result('groupname');
			$response->vars->user->groupId = $this->core->db->result('groupid');
			if($this->queryParams->type!='restricted')
				{
				$response->vars->user->lastconnection =
					$this->core->db->result('lastconnection');
				}
			}
		return $response;
		}
	function put()
		{
		if($this->core->auth->source=='none'
			||$this->core->auth->source=='conf') {
			throw new RestException(RestCodes::HTTP_400,
				'Unable to modify the user in that source "'
				.$this->core->auth->source.'".');
			}
		else if($this->core->auth->source=='db')
			{
			$response=$this->head();
			if($response->code==RestCodes::HTTP_200)
				{
				$this->core->db->query('UPDATE users SET firstname="'.$this->request->content->user->firstName
					.'", lastname="'.$this->request->content->user->lastName
					.'", email="'.$this->request->content->user->email
					.'", `group`="'.$this->request->content->user->groupId
					.'", lastconnection=NOW() WHERE login="'.$this->request->uriNodes[1].'"');
				$response=$this->get();
				}
			else
				{
				$this->core->db->query('INSERT INTO users (login, firstname, lastname, email, group, lastconnection)'
					.' VALUES ("'.$this->request->content->user->login.'","'.$this->request->content->user->firstName
					.'","'.$this->request->content->user->lastName.'","'.$this->request->content->user->email
					.'","'.$this->request->content->user->groupId.'",NOW())');
				$response=$this->get();
				$response->vars->user->userId = $this->core->db->insertId();
				}
			}
		$response->code=RestCodes::HTTP_201;
		return $response;
		}
	function delete()
		{
		if($this->core->auth->source=='none'
			||$this->core->auth->source=='conf') {
			throw new RestException(RestCodes::HTTP_400,
				'Unable to delete the user in that source "'
				.$this->core->auth->source.'".');
			}
		else if($this->core->auth->none=='db')
			{
			$this->core->db->query('DELETE FROM users WHERE login="'
				.$this->request->uriNodes[1].'"');
			}
		else if($this->core->auth->source!='none')
			{
			throw new RestException(RestCodes::HTTP_500,
				'User source has not been set or has an unsupported value.');
			}
		return new RestVarsResponse(RestCodes::HTTP_410,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	}
