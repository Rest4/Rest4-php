<?php
class RestCacheXDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='Cache: X Cache Driver';
		$drvInf->description='Cache resources with XCache.';
		$drvInf->usage='/cache/x/uri-md5(queryString).ext';
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
		if(!$content=xcache_get(substr($this->request->uri,13)))
			throw new RestException(RestCodes::HTTP_410,'Not in the xcache.');
		$mime=xcUtils::getMimeFromExt($this->request->fileExt);
		if(array_search($mime,explode(',',RestResponseVars::MIMES))!==false)
			{
			$response=new RestResponseVars(RestCodes::HTTP_200);
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
		if(!xcache_set(substr($this->request->uri,13),$this->request->content))
			throw new RestException(RestCodes::HTTP_503,'Cannot put content in the x cache.');
		return new RestResponse(
			RestCodes::HTTP_201,
			array('Content-Type'=>xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	function post()
		{
		$content=xcache_get(substr($this->request->uri,13));
		if(!xcache_set(substr($this->request->uri,13),($content?$content:'').$this->request->content))
			throw new RestException(RestCodes::HTTP_503,'Cannot append content to xcache.');
		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	function delete()
		{
		// Must reimplement with recursion ?
		$urisToClean=array();
		array_push($urisToClean,substr($this->request->uri,13));
		$vcnt = xcache_count(XC_TYPE_VAR);
		for ($i = 0; $i < $vcnt; $i ++)
			{
			$data=xcache_list(XC_TYPE_VAR, $i);
			foreach($data['cache_list'] as $cres)
				{
				if(strpos($cres['name'],'callback.txt')===strlen($cres['name'])-12)
					{
					$urisToClean=array_merge($urisToClean,explode("\n",xcache_get($cres['name'])));
					xcache_unset($cres['name']);
					}
				}
			}
		for ($i = 0; $i < $vcnt; $i ++)
			{
			$data=xcache_list(XC_TYPE_VAR, $i);
			foreach($data['cache_list'] as $cres)
				{
				foreach($urisToClean as $uri)
					{
					if(strpos($cres['name'],$uri)===0)
						{
						xcache_unset($cres['name']);
						}
					}
				}
			}
		return new RestResponse(
			RestCodes::HTTP_410,
			array('Content-Type'=>'text/plain'),
			'X cache deleted.');
		}
	}
RestCacheXDriver::$drvInf=RestCacheXDriver::getDrvInf();