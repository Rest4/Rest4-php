<?php
class RestCacheApcDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new xcDataObject();
		$drvInf->name='Cache: Apc Cache Driver';
		$drvInf->description='(!) Will cache resources with Apc.';
		$drvInf->usage='/cache/apc/uri-md5(queryString).ext';
		$drvInf->methods=new xcDataObject();
		$drvInf->methods->options=new xcDataObject();
		$drvInf->methods->options->outputMimes='application/internal';
		$drvInf->methods->get=new xcDataObject();
		$drvInf->methods->get->outputMimes='*';
		$drvInf->methods->put=new xcDataObject();
		$drvInf->methods->put->outputMimes='*';
		$drvInf->methods->post=new xcDataObject();
		$drvInf->methods->post->outputMimes='*';
		$drvInf->methods->delete=new xcDataObject();
		$drvInf->methods->delete->outputMimes='*';
		return $drvInf;
		}
	function get()
		{
		$response=new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'text/plain')
			);
		if(!(apc_exists(substr($this->request->uri,13))&&$content=apc_fetch(substr($this->request->uri,13))))
			throw new RestException(RestCodes::HTTP_410,'Not in the apc cache.');
		$mime=xcUtils::getMimeFromExt($this->request->fileExt);
		if($mime=='application/internal'||$mime=='text/lang')
			{
			$response->content=new xcDataObject();
			xcDatas::import($response->content,$content);
			}
		else
			{
			$response->content=$content;
			}
		$response->setHeader('Content-type',$mime);
		$response->setHeader('Last-Modified',gmdate('D, d M Y H:i:s', (time()-84600)) . ' GMT');
		return $response;
		}
	function put()
		{
		$mime=xcUtils::getMimeFromExt($this->request->fileExt);
		if($mime=='application/internal'||$mime=='text/lang')
			{
			if($this->request->content instanceof xcObjectCollection||$this->request->content instanceof xcDataObject)
				{
				$content=xcDatas::export($this->request->content);
				}
			else
				{
				$content=$this->request->content;
				//trigger_error($this->core->server->location.': ApcCache: '.$this->request->uri.': the request content is not a xcObjectCollection or a xcDataObject.');
				}
			}
		else
			$content=$this->request->content;
		if((apc_exists(substr($this->request->uri,13))&&!apc_store(substr($this->request->uri,13),$content))||!apc_add(substr($this->request->uri,13),$content))
			throw new RestException(RestCodes::HTTP_503,'Cannot put content in the apc cache.');
		return new RestResponse(
			RestCodes::HTTP_201,
			array('Content-Type'=>'text/plain'));
		}
	function post()
		{
		if(apc_exists(substr($this->request->uri,13)))
			$content=apc_fetch(substr($this->request->uri,13));
		else
			$content='';
		if((!$content)||strpos($content,$this->request->content)===false)
			{
			if(!apc_store(substr($this->request->uri,13),($content?$content."\n":'').$this->request->content))
				throw new RestException(RestCodes::HTTP_503,'Cannot put content in the apc cache.');
			}
		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'text/plain'));
		}
	function delete()
		{
		// Must reimplement with recursion ?
		$urisToClean=array();
		array_push($urisToClean,substr($this->request->uri,13));
		$cachedKeys = new APCIterator('user', '/^'.substr($this->request->uri,13).'/', APC_ITER_VALUE);
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
RestCacheApcDriver::$drvInf=RestCacheApcDriver::getDrvInf();