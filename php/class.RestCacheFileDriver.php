<?php
class RestCacheFileDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=new stdClass();
		$drvInf->name='Cache: File Cache Driver';
		$drvInf->description='(!) Will cache resources in the filesystem.';
		$drvInf->usage='/cache/fs/uri-md5(queryString).ext';
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
		$resource=new RestResource(new RestRequest(RestMethods::GET,
			'/fs'.substr($this->request->uri,9),array()));
		return $resource->getResponse();
		}
	function put()
		{
		$mime=xcUtils::getMimeFromExt($this->request->fileExt);
		if($mime=='text/varstream'||$mime=='text/lang')
			{
			if($this->request->content instanceof ArrayObject
				||$this->request->content instanceof stdClass)
				{
				$content=Varstream::export($this->request->content);
				}
			else
				{
				$content=$this->request->content;
				trigger_error($this->core->server->location.': FsCache: '.$this->request->uri
					.': the request content is not an ArrayObject or a stdClass.');
				}
			}
		else
			$content=$this->request->content;
		$resource=new RestResource(new RestRequest(RestMethods::PUT,
			'/fs'.substr($this->request->uri,9).'?force=yes',array(),$content));
		return $resource->getResponse();
		}
	function post()
		{
		$mime=xcUtils::getMimeFromExt($this->request->fileExt);
		if($mime=='text/varstream'||$mime=='text/lang')
			{
			if($this->request->content instanceof ArrayObject
				||$this->request->content instanceof stdClass)
				{
				$content=Varstream::export($this->request->content);
				}
			else
				{
				$content=$this->request->content;
				//trigger_error($this->core->server->location.': FsCache: '
				//.$this->request->uri.': the request content is not a ArrayObject or a stdClass.');
				}
			}
		else
			$content=$this->request->content;
		$resource=new RestResource(new RestRequest(RestMethods::POST,
			'/fs'.substr($this->request->uri,9).'?force=yes',array(),$content));
		return $resource->getResponse();
		}
	function delete()
		{
		$res=new RestResource(new RestRequest(RestMethods::GET,'/fsi/cache.dat',array()));
		$res=$res->getResponse();
		if($res->code!=RestCodes::HTTP_200)
			{
			throw new RestException(RestCodes::HTTP_503,'Cannot get file cache content.');
			}
		foreach($res->content->files as $file)
			{
			if($file->name!='.'&&$file->name!='..')
				{
				$res=new RestResource(new RestRequest(RestMethods::DELETE,
					'/fs/cache/'.$file->name.($file->isDir?'/?recursive=yes':''),array()));
				$res=$res->getResponse();
				if($res->code!=RestCodes::HTTP_410)
					{
					throw new RestException(RestCodes::HTTP_500,
						'Cannot delete linked content in the cache.',
						'code:'.$res->code.', contents:'.$res->getContents());
					}
				}
			}
		$res=new RestResponse(
			RestCodes::HTTP_410,
			array('Content-Type'=>'text/plain')
			);
		return $res;
		}
	}
