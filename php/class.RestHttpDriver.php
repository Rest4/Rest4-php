<?php
class RestHttpDriver extends RestDriver
	{
	private $_uri;
	private $_c;
	private $_c_headers;
	private $_c_content;
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=new stdClass();
		$drvInf->name='Http: Driver';
		$drvInf->description='Pipe a ressource from any HTTP server.';
		$drvInf->usage='/http(?uri=httpuri)';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='text/varstream';
		$drvInf->methods->head=$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='*';
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='uri';
		$drvInf->methods->get->queryParams[0]->filter='httpuri';
		$drvInf->methods->get->queryParams[0]->required=true;
		$drvInf->methods->get->queryParams[1]=new stdClass();
		$drvInf->methods->get->queryParams[1]->name='auth';
		$drvInf->methods->get->queryParams[1]->filter='parameter';
		$drvInf->methods->get->queryParams[1]->value='';
		$drvInf->methods->get->queryParams[2]=new stdClass();
		$drvInf->methods->get->queryParams[2]->name='user';
		$drvInf->methods->get->queryParams[2]->filter='parameter';
		$drvInf->methods->get->queryParams[2]->value='';
		$drvInf->methods->get->queryParams[3]=new stdClass();
		$drvInf->methods->get->queryParams[3]->name='password';
		$drvInf->methods->get->queryParams[3]->filter='iparameter';
		$drvInf->methods->get->queryParams[3]->value='';
		$drvInf->methods->post=$drvInf->methods->get;
		$drvInf->methods->put=$drvInf->methods->get;
		$drvInf->methods->delete=$drvInf->methods->get;
		return $drvInf;
		}
	private function prepare()
		{
		$this->_uri=$this->queryParams->uri;
        $this->_c = curl_init($this->_uri);
		curl_setopt ($this->_c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($this->_c, CURLOPT_SSL_VERIFYPEER, false);
		if($this->queryParams->auth)
			{
			switch($this->queryParams->auth)
				{
				case 'basic';
					curl_setopt ($this->_c, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
					break;
				case 'digest';
					curl_setopt ($this->_c, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
					break;
				}
			curl_setopt($this->_c, CURLOPT_USERPWD, $this->queryParams->user.':'.$this->queryParams->password);
			}

		$this->_c_headers=array();
		foreach($this->request->headers as $name => $value)
			{
			if($name=='User-Agent'||$name=='Accept'||$name=='Accept-Language'||$name=='Accept-Charset')
				array_push($this->_c_headers,$name.': '. $value);
			}
		}
	private function exec()
		{
		curl_setopt($this->_c, CURLOPT_HTTPHEADER, $theHeaders);
		
		$response=new RestResponse();

        $response->content = curl_exec($this->_c);

		$errorno=curl_errno($this->_c);
		$error=curl_error($this->_c);

		$response->code = curl_getinfo($this->_c,CURLINFO_HTTP_CODE);
		$response->setHeader('Content-Type',curl_getinfo($this->_c,CURLINFO_CONTENT_TYPE));
		$response->setHeader('Content-Length',curl_getinfo($this->_c,CURLINFO_CONTENT_LENGTH_DOWNLOAD));
        /*$newUri=curl_getinfo($this->_c,CURLINFO_EFFECTIVE_URL);
		if($newUri!=$this->_uri)
			{
			$response->setHeader('Location: ',$newUri);
			}*/

		curl_close($this->_c);
		if($errorno)
			throw new RestException(RestCodes::HTTP_500,'cURL got an error.','Error '.$errorno.': '.$error.', uri: '.$this->_uri);

		return $response;
		}/*
	function options()
		{
        $this->prepare();

		curl_setopt($_c,CURLOPT_CUSTOMREQUEST, RestMethods::OPTIONS);

		return $this->exec();
		}*/
	function head()
		{
        $this->prepare();

		curl_setopt($this->_c,CURLOPT_CUSTOMREQUEST, RestMethods::getStringFromMethod(RestMethods::HEAD));

		return $this->exec();
		}
	function get()
		{
        $this->prepare();
		
		return $this->exec();
		}
	function post()
		{
        $this->prepare();

		curl_setopt($this->_c,CURLOPT_POST, 1);
		if(($this->request->getHeader('Content-Type')=='text/varstream'
			||$this->request->getHeader('Content-Type')=='text/lang')
			&&($this->request->content instanceof ArrayObject||$this->request->content instanceof stdClass))
			{
			$this->request->content=Varstream::export($this->request->content);
			}
		array_push($this->_c_headers,'Content-Type: text/plain');
		curl_setopt($this->_c,CURLOPT_POSTFIELDS, $this->request->content);

		return $this->exec();
		}
	function put()
		{
        $this->prepare();

		curl_setopt($this->_c,CURLOPT_CUSTOMREQUEST, RestMethods::getStringFromMethod(RestMethods::PUT));
		
		if(($this->request->getHeader('Content-Type')=='text/varstream'
			||$this->request->getHeader('Content-Type')=='text/lang')
			&&($this->request->content instanceof ArrayObject||$this->request->content instanceof stdClass))
			{
			$this->request->content='#text/varstream'."\n".Varstream::export($this->request->content);
			}
		/*$fh = tmpfile();
		fwrite($fh, $this->request->content);
		fseek($fh, 0);
		curl_setopt($this->_c,CURLOPT_INFILE, $fh);
		curl_setopt($this->_c, CURLOPT_INFILESIZE, strlen($this->request->content));*/
		array_push($this->_c_headers,'Content-Type: text/plain');
		curl_setopt($this->_c,CURLOPT_POSTFIELDS, $this->request->content);

		return $this->exec();
		}
	function delete()
		{
        $this->prepare();

		curl_setopt($this->_c,CURLOPT_CUSTOMREQUEST, RestMethods::getStringFromMethod(RestMethods::DELETE));

		return $this->exec();
		}
	}
