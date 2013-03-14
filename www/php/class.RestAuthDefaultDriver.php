<?php
class RestAuthDefaultDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new xcDataObject();
		$drvInf->name='Auth: Default Auth Driver';
		$drvInf->description='Authentifies users with the configuration file and show their rights.';
		$drvInf->usage='/auth/default.ext?method=(request_method)&authorization=(basic_auth_string)';
		$drvInf->methods=new xcDataObject();
		$drvInf->methods->options=new xcDataObject();
		$drvInf->methods->options->outputMimes='application/internal';
		$drvInf->methods->head=$drvInf->methods->get=new xcDataObject();
		$drvInf->methods->get->outputMimes='application/internal';
		$drvInf->methods->get->queryParams=new xcObjectCollection();
		$drvInf->methods->get->queryParams[0]=new xcDataObject();
		$drvInf->methods->get->queryParams[0]->name='method';
		$drvInf->methods->get->queryParams[0]->filter='iparameter';
		$drvInf->methods->get->queryParams[0]->value='';
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
			$credentials=explode(':',base64_decode(substr($this->queryParams->authorization,6)));
			if(!(xcUtilsInput::filterValue($credentials[0],'text','iparameter')&&xcUtilsInput::filterValue($credentials[1],'text','iparameter')&&!isset($credentials[2])))
				throw new RestException(RestCodes::HTTP_400,'Bad credentials format.');
			// Testing with registered users
			if(isset($this->core->auth,$this->core->auth->{$credentials[0]},$this->core->auth->{$credentials[0]}->pass)
				&&$this->core->auth->{$credentials[0]}->pass&&$this->core->auth->{$credentials[0]}->pass==$credentials[1])
				{
				$response->content->id=$this->core->auth->{$credentials[0]}->id;
				if(isset($this->core->auth->{$credentials[0]}->group))
					$response->content->group=$this->core->auth->{$credentials[0]}->group;
				$response->content->login=$credentials[0];
				$response->content->rights=$this->core->auth->{$credentials[0]}->rights;
				}
			}
		if(isset($this->core->auth,$this->core->auth->public,$this->core->auth->public->rights))
			{
			if(!$response->content->id)
				$response->content->rights=$this->core->auth->public->rights;
			else
				{
				foreach($this->core->auth->public->rights as $right)
					{
					$response->content->rights->append($right);
					}
				}
			}
		unset($this->core->auth);
		$response->setHeader('X-Rest-Uncacheback','/fs/conf/conf.dat');
		return $response;
		}
	function post()
		{
		return new RestResponse(RestCodes::HTTP_401,
			array('Content-Type'=>'text/plain','WWW-Authenticate'=>'Basic realm="'.$this->core->server->realm.'"'),
			'Must authenticate to access this ressource.');
		}
	}
RestAuthDefaultDriver::$drvInf=RestAuthDefaultDriver::getDrvInf();