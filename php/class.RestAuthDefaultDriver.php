<?php
class RestAuthDefaultDriver extends RestVarsDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=parent::getDrvInf(RestMethods::GET|RestMethods::POST);
		$drvInf->name='Auth: Default Auth Driver';
		$drvInf->description='Authentifies users with the configuration file and show their rights.';
		$drvInf->usage='/auth/default'.$drvInf->usage
			.'?method=(request_method)&authorization=(basic_auth_string)';
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='method';
		$drvInf->methods->get->queryParams[0]->filter='iparameter';
		$drvInf->methods->get->queryParams[0]->value='';
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
			$credentials=explode(':',base64_decode(substr($this->queryParams->authorization,6)));
			if(!(xcUtilsInput::filterValue($credentials[0],'text','iparameter')
				&&xcUtilsInput::filterValue($credentials[1],'text','iparameter')&&!isset($credentials[2])))
				throw new RestException(RestCodes::HTTP_400,'Bad credentials format.');
			// Testing with registered users
			if(isset($this->core->auth,$this->core->auth->{$credentials[0]},
					$this->core->auth->{$credentials[0]}->pass)
				&&$this->core->auth->{$credentials[0]}->pass
				&&$this->core->auth->{$credentials[0]}->pass==$credentials[1])
				{
				$vars->id=$this->core->auth->{$credentials[0]}->id;
				if(isset($this->core->auth->{$credentials[0]}->group))
					$vars->group=$this->core->auth->{$credentials[0]}->group;
				$vars->login=$credentials[0];
				$vars->rights=$this->core->auth->{$credentials[0]}->rights;
				}
			}
		if(isset($this->core->auth,$this->core->auth->public,$this->core->auth->public->rights))
			{
			if(!$vars->id)
				$vars->rights=$this->core->auth->public->rights;
			else
				{
				foreach($this->core->auth->public->rights as $right)
					{
					$vars->rights->append($right);
					}
				}
			}
		unset($this->core->auth);
		return new RestVarsResponse(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt),
				'X-Rest-Uncacheback' =>'//fs/conf/conf.dat'),
			$vars);
		}
	function post()
		{
		$vars=new stdClass();
		$vars->message='Must authenticate to access this ressource.';
		return new RestVarsResponse(RestCodes::HTTP_401,
			array('WWW-Authenticate'=>'Basic realm="'.$this->server->realm.'"',
				'Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)),
			$vars);
		}
	}
