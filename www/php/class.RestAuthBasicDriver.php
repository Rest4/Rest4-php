<?php
class RestAuthBasicDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='Auth: Basic Auth Driver';
		$drvInf->description='Authentifies users with the basic method and show their rights.';
		$drvInf->usage='/auth/basic.ext?method=(request_method)&authorization=(basic_auth_string)';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='application/internal';
		$drvInf->methods->head=$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='application/internal';
		$drvInf->methods->get->queryParams=new xcObjectCollection();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='method';
		$drvInf->methods->get->queryParams[0]->filter='iparameter';
		$drvInf->methods->get->queryParams[0]->value='';
		$drvInf->methods->get->queryParams[1]=new stdClass();
		$drvInf->methods->get->queryParams[1]->name='authorization';
		$drvInf->methods->get->queryParams[1]->filter='cdata';
		$drvInf->methods->get->queryParams[1]->value='';
		$drvInf->methods->post=new stdClass();
		$drvInf->methods->post->outputMimes='application/internal';
		return $drvInf;
		}
	function head()
		{
		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>xcUtils::getMimeFromExt($this->request->fileExt))
			);
		}
	function get()
		{
		$this->core->db->selectDb($this->core->database->database);
		// Setting defaults
		$response=new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'application/internal')
			);
		$response->content=new stdClass();
		$response->content->id=0;
		$response->content->group=0;
		$response->content->organization=0;
		$response->content->rights=new xcObjectCollection();
		$response->content->login='';
		if($this->queryParams->authorization)
			{
			// Getting credentials
			$credentials=explode(':',base64_decode(substr($this->queryParams->authorization,6)));
			if(!(xcUtilsInput::filterValue($credentials[0],'text','iparameter')&&xcUtilsInput::filterValue($credentials[1],'text','iparameter')&&!isset($credentials[2])))
				throw new RestException(RestCodes::HTTP_400,'Bad credentials format.');
			// Checking credentials
			$this->core->db->query('SELECT * FROM users WHERE login="'.$credentials[0].'" AND (password="'.sha1($credentials[1]).'" OR password="'.md5($credentials[0] . ':' . $this->core->server->realm . ':' . $credentials[1]).'")');
			if($this->core->db->numRows())
				{
				$response->content->id=$this->core->db->result('users.id');
				$response->content->group=$this->core->db->result('users.group');
				$response->content->organization=$this->core->db->result('users.organization');
				$response->content->login=$credentials[0];
				}
			}
		// Getting default anonymous and connected user rights
		$this->core->db->query('SELECT DISTINCT rights.path'.($this->queryParams->method?'':', rights.enablings').' FROM rights'
			.' LEFT JOIN groups_rights ON groups_rights.rights_id=rights.id'
			.' LEFT JOIN groups ON groups.id=groups_rights.groups_id'
			.' WHERE (groups.id=0'.($response->content->id?' OR groups.id=1':'').')'.($this->queryParams->method?' AND rights.enablings&'.RestMethods::getMethodFromString($this->queryParams->method):''));
			if($this->core->db->numRows())
				{
				while ($row = $this->core->db->fetchArray())
					{
					$right=new stdClass();
					$right->path=str_replace('{user.login}',$response->content->login,
						str_replace('{user.group}',$response->content->group,
						str_replace('{user.organization}',$response->content->organization,$row['path'])));
					if(!$this->queryParams->method)
						$right->methods=$row['enablings'];
					$response->content->rights->append($right);
					}
				}
			$this->core->db->query('SELECT DISTINCT rights.path'.($this->queryParams->method?'':', rights.enablings').' FROM rights'
				.' LEFT JOIN groups_rights ON groups_rights.rights_id=rights.id'
				.' LEFT JOIN groups ON groups.id=groups_rights.groups_id'
				.' LEFT JOIN groups_users ON groups_users.groups_id=groups.id'
				.' LEFT JOIN rights_users ON rights_users.rights_id=rights.id'
				.' LEFT JOIN users ON (users.id=groups_users.users_id OR users.id=rights_users.users_id OR users.group=groups.id)'
				.' WHERE users.id='.$response->content->id.($this->queryParams->method?' AND rights.enablings&'.RestMethods::getMethodFromString($this->queryParams->method):''));
			if($this->core->db->numRows())
				{
				while ($row = $this->core->db->fetchArray())
					{
					$right=new stdClass();
					$right->path=str_replace('{user.login}',$response->content->login,
						str_replace('{user.group}',$response->content->group,
						str_replace('{user.organization}',$response->content->organization,$row['path'])));
					if(!$this->queryParams->method)
						$right->methods=$row['enablings'];
					$response->content->rights->append($right);
					}
				}
		$response->setHeader('X-Rest-Uncacheback','/users');
		return $response;
		}
	function post()
		{
		return new RestResponse(RestCodes::HTTP_401,
			array('Content-Type'=>'text/plain','WWW-Authenticate'=>'Basic realm="'.$this->core->server->realm.'"'),
			'Must authenticate to access this ressource.');
		}
	}
RestAuthBasicDriver::$drvInf=RestAuthBasicDriver::getDrvInf();