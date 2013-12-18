<?php
class RestCacheXDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=new stdClass();
		$drvInf->name='Cache: XCache Driver';
		$drvInf->description='Manage XCache contents.';
		$drvInf->usage='/cache/xcache/uri-md5(queryString).ext?mode=(|multiple)';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='text/varstream';
		$drvInf->methods->get=
			$drvInf->methods->delete=new stdClass();
		$drvInf->methods->get->outputMimes='*';
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='mode';
		$drvInf->methods->get->queryParams[0]->values=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]->values[0]=
			$drvInf->methods->get->queryParams[0]->value='single';
		$drvInf->methods->get->queryParams[0]->values[1]='multiple';
		$drvInf->methods->put=new stdClass();
		$drvInf->methods->put->outputMimes='*';
		$drvInf->methods->post=new stdClass();
		$drvInf->methods->post->outputMimes='*';
		return $drvInf;
		}
	function get()
		{
		$cacheKey=(isset($this->core->cache->prefix)?$this->core->cache->prefix:'')
			.substr($this->request->uri,13);
		if($this->queryParams->mode=='single')
			{
			if(!$content=xcache_get($cacheKey))
				{
				throw new RestException(RestCodes::HTTP_410,'Not in the xcache.');
				}
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
			}
		else
			{
			$cacheKey=substr($cacheKey,0,
				strlen($cacheKey)-1-strlen($this->request->fileExt)-14);
			$response=new RestVarsResponse(RestCodes::HTTP_200,
				array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
			$response->vars=new MergeArrayObject();
			$vcnt = xcache_count(XC_TYPE_VAR);
			for ($i = 0; $i < $vcnt; $i ++)
				{
				$data=xcache_list(XC_TYPE_VAR, $i);
				foreach($data['cache_list'] as $cres)
					{
					if(strpos($cres['name'],$cacheKey)===0)
						{
						$entry=new stdClass();
						$entry->name=(isset($this->core->cache->prefix)?
							substr($cres['name'],strlen($this->core->cache->prefix)+1):
							$cres['name']);
						$entry->resname=$cacheKey;
						$entry->refcount=$cres['refcount'];
						$entry->hits=$cres['hits'];
						$entry->ctime=$cres['ctime'];
						$entry->atime=$cres['atime'];
						$entry->hvalue=$cres['hvalue'];
						$entry->size=$cres['size'];
						$response->vars->append($entry);
						}
					}
				}
			}
		return $response;
		}
	function put()
		{
		$cacheKey=(isset($this->core->cache->prefix)?$this->core->cache->prefix:'')
			.substr($this->request->uri,13);
		if(!xcache_set($cacheKey,$this->request->content))
			{
			throw new RestException(RestCodes::HTTP_503,
				'Cannot put content in the x cache.');
			}
		return new RestResponse(
			RestCodes::HTTP_201,
			array('Content-Type'=>xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	function post()
		{
		$cacheKey=(isset($this->core->cache->prefix)?$this->core->cache->prefix:'')
			.substr($this->request->uri,13);
		$content=$content=xcache_get($cacheKey);
		if(!xcache_set($cacheKey,($content?$content:'').$this->request->content))
			{
			throw new RestException(RestCodes::HTTP_503,
				'Cannot append content to xcache.');
			}
		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	function delete()
		{
		$cacheKey=(isset($this->core->cache->prefix)?$this->core->cache->prefix:'')
			.substr($this->request->uri,13);
		if($this->queryParams->mode=='single')
			{
			if(xcache_isset($cacheKey))
				{
				xcache_unset($cacheKey);
				}
			}
		else
			{
			$cacheKey=substr($cacheKey,0,
				strlen($cacheKey)-1-strlen($this->request->fileExt)-14);
			// Must reimplement with recursion ?
			$urisToClean=array();
			$vcnt = xcache_count(XC_TYPE_VAR);
			for ($i = 0; $i < $vcnt; $i ++)
				{
				$data=xcache_list(XC_TYPE_VAR, $i);
				foreach($data['cache_list'] as $cres)
					{
					if(strpos($cres['name'],$cacheKey)===0
						&&strpos($cres['name'],'callback.txt')===strlen($cres['name'])-12)
						{
						$urisToClean=array_merge($urisToClean,
							explode("\n",xcache_get($cres['name'])));
						xcache_unset($cres['name']);
						}
					}
				}
			if(isset($this->core->cache->prefix)) {
			  for($i=0; $i<sizeof($urisToClean); $i++) {
			    if($urisToClean[$i])
			      {
  			    $urisToClean[$i]=$this->core->cache->prefix.$urisToClean[$i];
            }
          else
            {
            array_splice($urisToClean, $i, 1); $i--;
            }
			  }
			}
			array_push($urisToClean,$cacheKey);
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
			}
		return new RestResponse(
			RestCodes::HTTP_410,
			array('Content-Type'=>'text/plain'),
			'XCache contents deleted.');
		}
	}
