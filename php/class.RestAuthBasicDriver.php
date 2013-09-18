<?php
class RestAuthBasicDriver extends RestVarsDriver
	{
	static $drvInf;
	public static function getDrvInf($methods=0)
		{
		$drvInf=parent::getDrvInf(RestMethods::GET|RestMethods::POST);
		$drvInf->name='Auth: Basic Auth Driver';
		$drvInf->description='Authentifies users with the basic method'
			.' and show their rights.';
		$drvInf->usage='/auth/basic'.$drvInf->usage
			.'?method=(request_method)&authorization=(basic_auth_string)';
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='method';
		$drvInf->methods->get->queryParams[0]->filter='iparameter';
		$drvInf->methods->get->queryParams[0]->required=true;
		$drvInf->methods->get->queryParams[1]=new stdClass();
		$drvInf->methods->get->queryParams[1]->name='source';
		$drvInf->methods->get->queryParams[1]->filter='iparameter';
		$drvInf->methods->get->queryParams[1]->required=true;
		$drvInf->methods->get->queryParams[2]=new stdClass();
		$drvInf->methods->get->queryParams[2]->name='authorization';
		$drvInf->methods->get->queryParams[2]->filter='cdata';
		$drvInf->methods->get->queryParams[2]->value='';
		return $drvInf;
		}
	function get()
		{
		// Setting defaults
		$vars=new stdClass();
		$vars->id=0;
		$vars->group=0;
		$vars->organization=0;
		$vars->rights=new MergeArrayObject();
		$vars->login='';
		if($this->queryParams->authorization)
			{
			// Getting credentials
			$credentials=explode(':',base64_decode(substr(
				$this->queryParams->authorization,6)));
			if(!(xcUtilsInput::filterValue($credentials[0],'text','iparameter')
				&&xcUtilsInput::filterValue($credentials[1],'text','iparameter')
				&&!isset($credentials[2])))
				throw new RestException(RestCodes::HTTP_400,'Bad credentials format.');
			// Checking credentials
			if('db'===$this->queryParams->source)
				{
				$this->core->db->selectDb($this->core->database->database);
				$this->core->db->query('SELECT * FROM users WHERE login="'
					.$credentials[0].'" AND (password="'.sha1($credentials[1])
					.'" OR password="'.md5($credentials[0].':' . $this->core->auth->realm
					. ':' . $credentials[1]).'")');
				if($this->core->db->numRows())
					{
					$vars->id=$this->core->db->result('users.id');
					$vars->group=$this->core->db->result('users.group');
					$vars->organization=$this->core->db->result('users.organization');
					$vars->login=$credentials[0];
					}
				}
			else if('conf'===$this->queryParams->source)
				{
				if(isset($this->core->auth, $this->core->auth->users,
						$this->core->auth->users->{$credentials[0]},
						$this->core->auth->users->{$credentials[0]}->pass)
					&&$this->core->auth->users->{$credentials[0]}->pass
					&&($this->core->auth->users->{$credentials[0]}->pass
						== sha1($credentials[1])
					||$this->core->auth->users->{$credentials[0]}->pass
						== md5($credentials[0] .':' . $this->core->auth->realm
							. ':' . $credentials[1])
					))
					{
					$vars->id=$this->core->auth->users->{$credentials[0]}->id;
					if(isset($this->core->auth->users->{$credentials[0]}->group))
						$vars->group=$this->core->auth->users->{$credentials[0]}->group;
					$vars->login=$credentials[0];
					$vars->rights=$this->core->auth->users->{$credentials[0]}->rights;
					}
				}
			else
				{
				throw new RestException(RestCodes::HTTP_400,'Unsupported auth source "'
					.$this->queryParams->source.'".');
				}
			}
		// Retrieving the rights according to the authentication level
		if('db'===$this->queryParams->source)
			{
			$this->core->db->query('SELECT DISTINCT rights.path'
				.($this->queryParams->method?'':', rights.enablings').' FROM rights'
				.' LEFT JOIN groups_rights ON groups_rights.rights_id=rights.id'
				.' LEFT JOIN groups ON groups.id=groups_rights.groups_id'
				.' WHERE (groups.id=0'.($vars->id?' OR groups.id=1':'').')'
				.($this->queryParams->method?' AND rights.enablings&'
				.RestMethods::getMethodFromString($this->queryParams->method):''));
			if($this->core->db->numRows())
				{
				while ($row = $this->core->db->fetchArray())
					{
					$right=new stdClass();
					$right->path=str_replace('{user.login}',$vars->login,
						str_replace('{user.group}',$vars->group,
						str_replace('{user.organization}',
						$vars->organization,$row['path'])));
					if(!$this->queryParams->method)
						$right->methods=$row['enablings'];
					$vars->rights->append($right);
					}
				}
			$this->core->db->query('SELECT DISTINCT rights.path'
				.($this->queryParams->method?'':', rights.enablings').' FROM rights'
				.' LEFT JOIN groups_rights ON groups_rights.rights_id=rights.id'
				.' LEFT JOIN groups ON groups.id=groups_rights.groups_id'
				.' LEFT JOIN groups_users ON groups_users.groups_id=groups.id'
				.' LEFT JOIN rights_users ON rights_users.rights_id=rights.id'
				.' LEFT JOIN users ON (users.id=groups_users.users_id'
					.' OR users.id=rights_users.users_id OR users.group=groups.id)'
				.' WHERE users.id='.$vars->id.($this->queryParams->method?
					' AND rights.enablings&'
					.RestMethods::getMethodFromString($this->queryParams->method):''));
			if($this->core->db->numRows())
				{
				while ($row = $this->core->db->fetchArray())
					{
					$right=new stdClass();
					$right->path=str_replace('{user.login}',$vars->login,
						str_replace('{user.group}',$vars->group,
						str_replace('{user.organization}',
						$vars->organization,$row['path'])));
					if(!$this->queryParams->method)
						$right->methods=$row['enablings'];
					$vars->rights->append($right);
					}
				}
			}
		else if('conf'===$this->queryParams->source)
			{
			if(isset($this->core->auth,$this->core->auth->users,
				$this->core->auth->users->public,
				$this->core->auth->users->public->rights))
				{
				// If the user does not exists, giving him public rights
				if(!$vars->id)
					{
					$vars->rights=$this->core->auth->users->public->rights;
					}
				// Otherwise, we augment him of the public rights
				else
					{
					foreach($this->core->auth->users->public->rights as $right)
						{
						$vars->rights->append($right);
						}
					}
				}
			// Erasing conf stored password since we do not need them anymore
			foreach(get_object_vars($this->core->auth->users) as $key =>$value)
				{
				if(isset($this->core->auth->users->{$key}->pass))
					{
					unset($this->core->auth->users->{$key}->pass);
					}
				}
			}
		return new RestVarsResponse(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt),
				'X-Rest-Uncacheback' =>'/users'),
			$vars);
		}
	function post()
		{
		$vars=new stdClass();
		$vars->message='Must authenticate to access this ressource.';
		return new RestVarsResponse(RestCodes::HTTP_401,
			array('WWW-Authenticate'=>'Basic realm="'.$this->core->auth->realm.'"',
				'Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)),
			$vars);
		}
	}
