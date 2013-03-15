<?php
class RestFsFileDriver extends RestFsDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new xcDataObject();
		$drvInf->name='Fs: File Driver';
		$drvInf->description='Manage a file an it\'s content.';
		$drvInf->usage='/fs/path/filename.ext';
		$drvInf->methods=new xcDataObject();
		$drvInf->methods->options=new xcDataObject();
		$drvInf->methods->options->outputMimes='*';
		$drvInf->methods->head=$drvInf->methods->get=new xcDataObject();
		$drvInf->methods->get->queryParams=new xcObjectCollection();
		$drvInf->methods->get->queryParams[0]=new xcDataObject();
		$drvInf->methods->get->queryParams[0]->name='download';
		$drvInf->methods->get->queryParams[0]->type='text';
		$drvInf->methods->get->queryParams[0]->filter='iparameter';
		$drvInf->methods->get->queryParams[0]->value='';
		$drvInf->methods->get->outputMimes='*';
		$drvInf->methods->put=new xcDataObject();
		$drvInf->methods->put->queryParams=new xcObjectCollection();
		$drvInf->methods->put->queryParams[0]=new xcDataObject();
		$drvInf->methods->put->queryParams[0]->name='force';
		$drvInf->methods->put->queryParams[0]->value='no';
		$drvInf->methods->put->outputMimes='*';
		$drvInf->methods->post=new xcDataObject();
		$drvInf->methods->post->outputMimes='*';
		$drvInf->methods->delete=new xcDataObject();
		$drvInf->methods->delete->outputMimes='*';
		return $drvInf;
		}
	function head()
		{
		clearstatcache(false,'..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt);
		if(!file_exists('..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt))
			throw new RestException(RestCodes::HTTP_410,'No file found at the given uri (fs'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');

		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>xcUtils::getMimeFromExt($this->request->fileExt),'Content-Length'=>filesize('..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt))
			);
		}
	function get()
		{
		$response=$this->head();
		$mime=xcUtils::getMimeFromExt($this->request->fileExt);
		if($mime=='application/internal'||$mime=='text/lang')
			{
			$response->content=new xcDataObject();
			xcDatas::import($response->content,file_get_contents('..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt));
			}
		else
			{
			$response->content=file_get_contents('..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt);
			}
		$response->setHeader('Last-Modified',gmdate('D, d M Y H:i:s', (filemtime('..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt)-84600)) . ' GMT');
		$response->setHeader('Content-type',$mime);
		if($this->queryParams->download)
			{
			$response->setHeader('X-Rest-Cache','None');
			$response->setHeader('Content-Disposition','attachment; filename="'.$this->queryParams->download.'.'.$this->request->fileExt.'"');
			}
		return $response;
		}/*
	function get()
		{
		$response=$this->head();
		$mime=xcUtils::getMimeFromExt($this->request->fileExt);
		if($mime!='application/internal')
			$response->setHeader('Content-type',$mime);
		else
			$response->setHeader('Content-type','text/plain');
		$response->content=file_get_contents('..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt);
		return $response;
		}*/
	function post()
		{
		clearstatcache(false,'..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt);
		if(!file_exists('..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt))
			throw new RestException(RestCodes::HTTP_410,'No file found for at the given uri (fs'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');

		if(!is_string($this->request->content))
			throw new RestException(RestCodes::HTTP_500,'The request content MUST be a string (fs'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');

		if(!file_put_contents('..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt,$this->request->content,FILE_APPEND))
			throw new RestException(RestCodes::HTTP_500,'Couldn\'t save content to the given uri (fs'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');

		chmod('..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt,0700);
		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-type'=>xcUtils::getMimeFromExt($this->request->fileExt),'X-Rest-Uncache'=>'/fs'.$this->request->filePath),
			@file_get_contents('..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt)
			);
		}
	function put()
		{
		clearstatcache(false,'..'.$this->request->filePath);
		if(!file_exists('..'.$this->request->filePath))
			{
			if($this->queryParams->force=='yes')
				$this->createParentFolders();
			else
				throw new RestException(RestCodes::HTTP_400,'Can\'t save file in an unexisting folder ('.$this->request->filePath.')');
			}

		// Can't get real content type with PHP
		//if(xcUtils::getMimeFromExt($this->request->fileExt)==$this->request->getHeader('Content-Type'))
		//	throw new RestException(RestCodes::HTTP_400,'The content of your request do not correspond with the file content type.');
		if(!is_string($this->request->content))
			{
			if($this->request->content instanceof xcObjectCollection||$this->request->content instanceof xcDataObject)
				$content=xcDatas::export($this->request->content);
			else
				throw new RestException(RestCodes::HTTP_500,'The request content MUST be a string (fs'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');
			}
		else
			$content=$this->request->content;
			
		if(file_put_contents('..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt,$content)===false)
			throw new RestException(RestCodes::HTTP_500,'Couldn\'t save file to the given uri (fs'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');
			
		chmod('..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt,0700);

		return new RestResponse(RestCodes::HTTP_201,
			array('Content-type'=>xcUtils::getMimeFromExt($this->request->fileExt),'X-Rest-Uncache'=>'/fs'.$this->request->filePath),
			$this->request->content);
		}
	function delete()
		{
		clearstatcache(false,'..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt);
		if(file_exists('..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt)
			&&!unlink('..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt))
			throw new RestException(RestCodes::HTTP_500,'Couldn\'t delete the file at the given uri (fs'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');

		return new RestResponse(RestCodes::HTTP_410,
			array('Content-type'=>xcUtils::getMimeFromExt($this->request->fileExt),'X-Rest-Uncache'=>'/fs'.$this->request->filePath.'|/fsi'.$this->request->filePath));
		}
	}
RestFsFileDriver::$drvInf=RestFsFileDriver::getDrvInf();