<?php
class RestCacheFileDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new xcDataObject();
		$drvInf->name='Cache: File Cache Driver';
		$drvInf->description='(!) Will cache resources in the filesystem.';
		$drvInf->usage='/cache/fs/uri-md5(queryString).ext';
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
		$resource=new RestResource(new RestRequest(RestMethods::GET,'/fs'.substr($this->request->uri,9),array()));
		return $resource->getResponse();
		}
	function put()
		{
		$mime=xcUtils::getMimeFromExt($this->request->fileExt);
		if($mime=='application/internal'||$mime=='text/lang')
			{
			if($this->request->content instanceof xcObjectCollection||$this->request->content instanceof xcDataObject)
				{
				$content=$this->request->content->exportContent();
				}
			else
				{
				$content=$this->request->content;
				trigger_error($this->core->server->location.': FsCache: '.$this->request->uri.': the request content is not a xcObjectCollection or a xcDataObject.');
				}
			}
		else
			$content=$this->request->content;
		$resource=new RestResource(new RestRequest(RestMethods::PUT,'/fs'.substr($this->request->uri,9).'?force=yes',array(),$content));
		return $resource->getResponse();
		}
	function post()
		{
		$mime=xcUtils::getMimeFromExt($this->request->fileExt);
		if($mime=='application/internal'||$mime=='text/lang')
			{
			if($this->request->content instanceof xcObjectCollection||$this->request->content instanceof xcDataObject)
				{
				$content=$this->request->content->exportContent();
				}
			else
				{
				$content=$this->request->content;
				//trigger_error($this->core->server->location.': FsCache: '.$this->request->uri.': the request content is not a xcObjectCollection or a xcDataObject.');
				}
			}
		else
			$content=$this->request->content;
		$resource=new RestResource(new RestRequest(RestMethods::POST,'/fs'.substr($this->request->uri,9).'?force=yes',array(),$content));
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
				$res=new RestResource(new RestRequest(RestMethods::DELETE,'/fs/cache/'.$file->name.($file->isDir?'/?recursive=yes':''),array()));
				$res=$res->getResponse();
				if($res->code!=RestCodes::HTTP_410)
					{
					//return $res; // dbg
					throw new RestException(RestCodes::HTTP_500,'Cannot delete linked content in the cache (code:'.$res->code.$res->content.').');
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
RestCacheFileDriver::$drvInf=RestCacheFileDriver::getDrvInf();