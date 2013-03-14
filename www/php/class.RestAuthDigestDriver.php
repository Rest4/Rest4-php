<?php
class RestAuthDigestDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new xcDataObject();
		$drvInf->name='Auth: Digest Auth Driver';
		$drvInf->description='Authentifies users with the digest method and show their rights.';
		$drvInf->usage='/auth/digest.ext?method=(request_method)&authorization=(digest_auth_string)';
		$drvInf->methods=new xcDataObject();
		$drvInf->methods->options=new xcDataObject();
		$drvInf->methods->options->outputMimes='application/internal';
		$drvInf->methods->head=$drvInf->methods->get=new xcDataObject();
		$drvInf->methods->get->outputMimes='application/internal';
		$drvInf->methods->get->queryParams=new xcObjectCollection();
		$drvInf->methods->get->queryParams[0]=new xcDataObject();
		$drvInf->methods->get->queryParams[0]->name='method';
		$drvInf->methods->get->queryParams[0]->filter='iparameter';
		$drvInf->methods->get->queryParams[0]->required=true;
		$drvInf->methods->get->queryParams[1]=new xcDataObject();
		$drvInf->methods->get->queryParams[1]->name='authorization';
		$drvInf->methods->get->queryParams[1]->filter='cdata';
		$drvInf->methods->get->queryParams[1]->value='';
		$drvInf->methods->post=new xcDataObject();
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
		// Setting defaults
		$response=new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'application/internal')
			);
		$response->content=new xcDataObject();
		$response->content->id=0;
		$response->content->group=0;
		$response->content->organization=0;
		$response->content->rights=new xcObjectCollection();
		$response->content->login='';
		if($this->queryParams->authorization)
			{
			// Getting credentials
			if(!$data = $this->http_digest_parse(substr($this->queryParams->authorization,7)))
				throw new RestException(RestCodes::HTTP_400,'Bad credentials format.');
			// Checking credentials
			$this->core->db->selectDb($this->core->database->database);
			$this->core->db->query('SELECT * FROM users WHERE login="'.xcUtilsInput::filterValue($data['username'],'text','iparameter').'"');
			if(!$this->core->db->numRows())
				throw new RestException(RestCodes::HTTP_400,'Bad credentials format.'); // Don't give username infos.
			//echo 'A1: '.$this->core->db->result('users.password')."\n";
			//echo 'A1: '.$data['username'] . ':' . $this->core->server->realm . ':' . 'fnriocio13'."\n";
			//echo 'A1: '.md5($data['username'] . ':' . $this->core->server->realm . ':' . 'fnriocio13')."\n";
			$A1=$this->core->db->result('users.password'); //$A1 = md5($data['username'] . ':' . $this->core->server->realm . ':' . 'pass');
			//echo 'A2: '.strtoupper($this->queryParams->method).':'.$data['uri']."\n";
			$A2 = md5(strtoupper($this->queryParams->method).':'.$data['uri']);
			$valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);
			//echo $data['response'] .'=='. $valid_response;
			if($data['response'] == $valid_response)
				{
				$response->content->id=$this->core->db->result('users.id');
				$response->content->group=$this->core->db->result('users.group');
				$response->content->organization=$this->core->db->result('users.organization');
				$response->content->login=$data['username'];
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
					$right=new xcDataObject();
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
					$right=new xcDataObject();
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
			array('Content-Type'=>'text/plain','WWW-Authenticate'=>'Digest realm="'.$this->core->server->realm.'",'
				.', qop="auth, auth-int"'
				.', nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093"'
				.', opaque="5ccc069c403ebaf9f0171e9517f40e41"' // Optional
				//.', stale="false"' // Optionnal
				.', algorithm="MD5"' // Optionnal
				),
			'Must authenticate to access this ressource.');
		}
	function http_digest_parse($txt) // http://php.net/manual/fr/features.http-auth.php
		{
		$needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
		$data = array();
		$keys = implode('|', array_keys($needed_parts));

		preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);

		foreach ($matches as $m) {
		$data[$m[1]] = $m[3] ? $m[3] : $m[4];
		unset($needed_parts[$m[1]]);
		}

		return $needed_parts ? false : $data;
		}
	}
RestAuthDigestDriver::$drvInf=RestAuthDigestDriver::getDrvInf();