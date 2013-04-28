<?php
class RestCacheApcDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=new stdClass();
		$drvInf->name='Cache: Apc Cache Driver';
		$drvInf->description='(!) Will cache resources with Apc.';
		$drvInf->usage='/cache/apc/uri-md5(queryString).ext';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='text/varstream';
		$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='*';
		$drvInf->methods->put=new stdClass();
		$drvInf->methods->put->outputMimes='*';
		$drvInf->methods->post=new stdClass();
		$drvInf->methods->post->outputMimes='*';
		$drvInf->methods->delete=new stdClass();
		$drvInf->methods->delete->outputMimes='*';
		return $drvInf;
		}
	function get()
		{
		if(!(apc_exists(substr($this->request->uri,13))
			&&$content=apc_fetch(substr($this->request->uri,13))))
			throw new RestException(RestCodes::HTTP_410,'Not in the apc cache.');
		$mime=xcUtils::getMimeFromExt($this->request->fileExt);
		if(array_search($mime,explode(',',RestVarsResponse::MIMES))!==false)
			{
			$response=new RestVarsResponse(RestCodes::HTTP_200);
			Varstream::import($response->vars,$content);
			}
		else
			{
			$response=new RestResponse(RestCodes::HTTP_200);
			$response->content=$content;
			}
		$response->setHeader('Content-type',$mime);
		$response->setHeader('Last-Modified',
			gmdate('D, d M Y H:i:s', (time()-84600)) . ' GMT');
		return $response;
		}
	function put()
		{
		if((apc_exists(substr($this->request->uri,13))
			&&!apc_store(substr($this->request->uri,13),$this->request->content))
			||!apc_add(substr($this->request->uri,13),$this->request->content))
			throw new RestException(RestCodes::HTTP_503,
				'Cannot put content in the apc cache.');
		return new RestResponse(
			RestCodes::HTTP_201,
			array('Content-Type'=>xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	function post()
		{
		if(apc_exists(substr($this->request->uri,13)))
			$content=apc_fetch(substr($this->request->uri,13));
		if(!apc_store(substr($this->request->uri,13),($content?$content:'')
			.$this->request->content))
			throw new RestException(RestCodes::HTTP_503,
				'Cannot append content to the apc cache.');
		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	function delete()
		{
		// Must reimplement with recursion ?
		$urisToClean=array();
		array_push($urisToClean,substr($this->request->uri,13));
		$cachedKeys = new APCIterator('user', '/^'.substr($this->request->uri,13)
			.'/', APC_ITER_VALUE);
		foreach ($cachedKeys AS $key => $value)
			{
			if(strpos($key,'callback.txt')===strlen($key)-12)
				{
				$urisToClean=array_merge($urisToClean,explode("\n",$value));
				apc_delete($key['name']);
				}
			}
		foreach($urisToClean as $uri)
			{
			apc_delete($uri);
			}
		return new RestResponse(
			RestCodes::HTTP_410,
			array('Content-Type'=>'text/plain'),
			'Apc cache deleted.');
		}
	}
