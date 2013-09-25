<?php
class RestCacheXEntriesDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=new stdClass();
		$drvInf->name='Cache: XCache Entries Driver';
		$drvInf->usage='/cache/xcache.ext';
		$drvInf->description='Cache resources with XCache.';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='text/varstream';
		$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='text/varstream';
		$drvInf->methods->delete=new stdClass();
		$drvInf->methods->delete->outputMimes='*';
		return $drvInf;
		}
	function get()
		{
		$response=new RestVarsResponse(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		$response->vars=new MergeArrayObject();
		$vcnt = xcache_count(XC_TYPE_VAR);
		for ($i = 0; $i < $vcnt; $i ++)
			{
			$data=xcache_list(XC_TYPE_VAR, $i);
			foreach($data['cache_list'] as $cres)
				{
				$entry=new stdClass();
				$entry->name=$cres['name'];
				$entry->refcount=$cres['refcount'];
				$entry->hits=$cres['hits'];
				$entry->ctime=$cres['ctime'];
				$entry->atime=$cres['atime'];
				$entry->hvalue=$cres['hvalue'];
				$entry->size=$cres['size'];
				$response->vars->append($entry);
				}
			}
		return $response;
		}
	function delete()
		{		
		// Must reimplement with recursion ?
		$urisToClean=array();
		array_push($urisToClean,
			(isset($this->core->cache->prefix)?$this->core->cache->prefix:'')
			.substr($this->request->uri,13));
		$vcnt = xcache_count(XC_TYPE_VAR);
		for ($i = 0; $i < $vcnt; $i ++)
			{
			$data=xcache_list(XC_TYPE_VAR, $i);
			foreach($data['cache_list'] as $cres)
				{
				if(strpos($cres['name'],'callback.txt')===strlen($cres['name'])-12)
					{
					$urisToClean=array_merge($urisToClean,
						explode("\n",xcache_get($cres['name'])));
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
			'XCache contents deleted.');
		}
	}
