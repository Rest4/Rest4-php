<?php
class RestAuthSessionDriver extends RestVarsDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=parent::getDrvInf(RestMethods::GET|RestMethods::POST);
		$drvInf->name='Auth: Session Auth Driver';
		$drvInf->description='Authentifies visitors/users sessions and show their rights.';
		$drvInf->usage='/auth/session'.$drvInf->usage
			.'?method=(request_method)&cookie=(cookies)';
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
		$drvInf->methods->get->queryParams[2]->name='cookie';
		$drvInf->methods->get->queryParams[2]->filter='cdata';
		$drvInf->methods->get->queryParams[2]->value='';
		return $drvInf;
		}
	function get()
		{
		if('db'!==$this->queryParams->source)
			{
			throw new RestException(RestCodes::HTTP_400,'Unsupported auth source "'
				.$this->queryParams->source.'".');
			}
		$this->core->db->selectDb($this->core->database->database);
		// Setting defaults
		$vars=new stdClass();
		$vars->id=0;
		$vars->group=0;
		$vars->organization=0;
		$vars->rights=new MergeArrayObject();
		$vars->login='';
		if($this->queryParams->cookie)
			{
			// Getting sessid
			$sessid=(strpos($this->queryParams->cookie,'sessid=')===false?'':
				substr($this->queryParams->cookie,strpos($this->queryParams->cookie,'sessid=')+7));
			$sessid=xcUtilsInput::filterValue((strpos($sessid,';')===false?$sessid:
				substr($sessid,0,strpos($sessid,';'))),'text','iparameter');
			if($sessid)
				{
				// Checking validity
				$this->core->db->query('SELECT * FROM visitors, users WHERE visitors.sessid="'.$sessid.'"');
				if($this->core->db->numRows())
					{
					$vars->id=$this->core->db->result('users.id');
					$vars->login=$this->core->db->result('users.login');
					$vars->group=$this->core->db->result('users.group');
					$vars->organization=$this->core->db->result('users.organization');
					$vars->sessid=$sessid;
					}
				}
			}
		// Getting default anonymous and connected user rights
		$this->core->db->query('SELECT DISTINCT rights.path'.($this->queryParams->method?'':', rights.enablings')
			.' FROM rights'
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
						str_replace('{user.organization}',$vars->organization,$row['path'])));
					if(!$this->queryParams->method)
						$right->methods=$row['enablings'];
					$vars->rights->append($right);
					}
				}
			$this->core->db->query('SELECT DISTINCT rights.path'.($this->queryParams->method?'':', rights.enablings')
				.' FROM rights'
				.' LEFT JOIN groups_rights ON groups_rights.rights_id=rights.id'
				.' LEFT JOIN groups ON groups.id=groups_rights.groups_id'
				.' LEFT JOIN groups_users ON groups_users.groups_id=groups.id'
				.' LEFT JOIN rights_users ON rights_users.rights_id=rights.id'
				.' LEFT JOIN users ON (users.id=groups_users.users_id OR users.id=rights_users.users_id OR users.group=groups.id)'
				.' WHERE users.id='.$vars->id.($this->queryParams->method?
				' AND rights.enablings&'.RestMethods::getMethodFromString($this->queryParams->method):''));
			if($this->core->db->numRows())
				{
				while ($row = $this->core->db->fetchArray())
					{
					$right=new stdClass();
					$right->path=str_replace('{user.login}',$vars->login,
						str_replace('{user.group}',$vars->group,
						str_replace('{user.organization}',$vars->organization,$row['path'])));
					if(!$this->queryParams->method)
						$right->methods=$row['enablings'];
					$vars->rights->append($right);
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
			array('WWW-Authenticate'=>'Cookie realm="'.$this->core->auth->realm.'"',
				'Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)),
			$vars);
		}
	}
