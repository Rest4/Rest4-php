<?php
class RestAuthDigestDriver extends RestVarsDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=parent::getDrvInf(RestMethods::GET|RestMethods::POST);
		$drvInf->name='Auth: Digest Auth Driver';
		$drvInf->description='Authentifies users with the digest'
			.' method and show their rights.';
		$drvInf->usage='/auth/digest'.$drvInf->usage
			.'?method=(request_method)&authorization=(digest_auth_string)';
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='method';
		$drvInf->methods->get->queryParams[0]->filter='iparameter';
		$drvInf->methods->get->queryParams[0]->required=true;
		$drvInf->methods->get->queryParams[1]=new stdClass();
		$drvInf->methods->get->queryParams[1]->name='authorization';
		$drvInf->methods->get->queryParams[1]->filter='cdata';
		$drvInf->methods->get->queryParams[1]->value='';
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
			if(!$data = $this->http_digest_parse(substr($this->queryParams->authorization,7)))
				throw new RestException(RestCodes::HTTP_400,'Bad credentials format.');
			// Checking credentials
			$this->core->db->selectDb($this->core->database->database);
			$this->core->db->query('SELECT * FROM users WHERE login="'
				.xcUtilsInput::filterValue($data['username'],'text','iparameter').'"');
			if(!$this->core->db->numRows())
				throw new RestException(RestCodes::HTTP_400,
					'Bad credentials format.'); // Don't give username infos.
			 // A1 = md5(username:realm:pass)
			$A1=$this->core->db->result('users.password');
			$A2 = md5(strtoupper($this->queryParams->method).':'.$data['uri']);
			$valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc']
				.':'.$data['cnonce'].':'.$data['qop'].':'.$A2);
			if($data['response'] == $valid_response)
				{
				$vars->id=$this->core->db->result('users.id');
				$vars->group=$this->core->db->result('users.group');
				$vars->organization=$this->core->db->result('users.organization');
				$vars->login=$data['username'];
				}
			}
		// Getting default anonymous and connected user rights
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
					str_replace('{user.organization}',$vars->organization,
					$row['path'])));
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
			.' WHERE users.id='.$vars->id
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
			array('WWW-Authenticate'=>'Digest realm="'.$this->core->server->realm.'",'
				.', qop="auth, auth-int"'
				.', nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093"'
				.', opaque="5ccc069c403ebaf9f0171e9517f40e41"' // Optional
				//.', stale="false"' // Optionnal
				.', algorithm="MD5"',
				'Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)
				),
			$vars);
		}
	function http_digest_parse($txt) // http://php.net/manual/fr/features.http-auth.php
		{
		$needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1,
			'username'=>1, 'uri'=>1, 'response'=>1);
		$data = array();
		$keys = implode('|', array_keys($needed_parts));

		preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@',
			$txt, $matches, PREG_SET_ORDER);

		foreach ($matches as $m) {
		$data[$m[1]] = $m[3] ? $m[3] : $m[4];
		unset($needed_parts[$m[1]]);
		}

		return $needed_parts ? false : $data;
		}
	}
