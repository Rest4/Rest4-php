<?php
class RestFsFileDriver extends RestFsDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=new stdClass();
		$drvInf->name='Fs: File Driver';
		$drvInf->description='Manage a file an it\'s content.';
		$drvInf->usage='/fs/path/filename.ext';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='*';
		$drvInf->methods->head=$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='download';
		$drvInf->methods->get->queryParams[0]->type='text';
		$drvInf->methods->get->queryParams[0]->filter='iparameter';
		$drvInf->methods->get->queryParams[0]->value='';
		$drvInf->methods->get->outputMimes='*';
		$drvInf->methods->put=new stdClass();
		$drvInf->methods->put->queryParams=new MergeArrayObject();
		$drvInf->methods->put->queryParams[0]=new stdClass();
		$drvInf->methods->put->queryParams[0]->name='force';
		$drvInf->methods->put->queryParams[0]->values=new MergeArrayObject();
		$drvInf->methods->put->queryParams[0]->values[0]=
			$drvInf->methods->put->queryParams[0]->value='no';
		$drvInf->methods->put->queryParams[0]->values[1]='yes';
		$drvInf->methods->put->outputMimes='*';
		$drvInf->methods->post=new stdClass();
		$drvInf->methods->post->outputMimes='*';
		$drvInf->methods->delete=new stdClass();
		$drvInf->methods->delete->outputMimes='*';
		return $drvInf;
		}
	function head()
		{
		clearstatcache(false,'.'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt);
		if(!file_exists('.'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt))
			throw new RestException(RestCodes::HTTP_410,'No file found at the given uri (fs'
				.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');

		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>xcUtils::getMimeFromExt($this->request->fileExt),
				'Content-Length'=>filesize('.'.$this->request->filePath.$this->request->fileName
					.'.'.$this->request->fileExt),
				'Last-Modified'=>gmdate('D, d M Y H:i:s', (filemtime('.'
			.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt)-84600)). ' GMT')
			);
		}
	function get()
		{
		$response=$this->head();
		$response=new RestFsStreamResponse(RestCodes::HTTP_200,
			array('Content-Type'=>$response->getHeader('Content-Type'),
				'Content-Length'=>$response->getHeader('Content-Length'),
				'Last-Modified'=>$response->getHeader('Last-Modified')),
			array('.'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt),
			($this->queryParams->download?$this->queryParams->download.'.'.$this->request->fileExt:'')
			);
		return $response;
		}
	function post()
		{
		clearstatcache(false,'.'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt);
		if(!file_exists('.'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt))
			throw new RestException(RestCodes::HTTP_410,'No file found for at the given uri (fs'
				.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');

		if(!is_string($this->request->content))
			throw new RestException(RestCodes::HTTP_500,'The request content MUST be a string (fs'
				.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');

		if(!file_put_contents('.'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt,
			$this->request->content,FILE_APPEND))
			throw new RestException(RestCodes::HTTP_500,'Couldn\'t save content to the given uri (fs'
				.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');

		chmod('.'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt,0700);
		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-type'=>xcUtils::getMimeFromExt($this->request->fileExt)),
			@file_get_contents('.'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt)
			);
		}
	function put()
		{
		clearstatcache(false,'.'.$this->request->filePath);
		if(!file_exists('.'.$this->request->filePath))
			{
			if($this->queryParams->force=='yes')
				$this->createParentFolders();
			else
				throw new RestException(RestCodes::HTTP_400,'Can\'t save file in an unexisting folder'
					.' ('.$this->request->filePath.')');
			}

		// Can't get real content type with PHP
		//if(xcUtils::getMimeFromExt($this->request->fileExt)==$this->request->getHeader('Content-Type'))
		//	throw new RestException(RestCodes::HTTP_400,
		//	'The content of your request do not correspond with the file content type.');
		if(!is_string($this->request->content))
			{
			if($this->request->content instanceof ArrayObject
				||$this->request->content instanceof stdClass)
				$content=Varstream::export($this->request->content);
			else
				throw new RestException(RestCodes::HTTP_500,'The request content MUST be a string (fs'
					.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');
			}
		else
			$content=$this->request->content;
			
		if(file_put_contents('.'.$this->request->filePath.$this->request->fileName
			.'.'.$this->request->fileExt,$content)===false)
			throw new RestException(RestCodes::HTTP_500,'Couldn\'t save file to the given uri (fs'
				.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');
			
		chmod('.'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt,0700);

		return new RestResponse(RestCodes::HTTP_201,
			array('Content-type'=>xcUtils::getMimeFromExt($this->request->fileExt)),
			$this->request->content);
		}
	function delete()
		{
		clearstatcache(false,'.'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt);
		if(file_exists('.'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt)
			&&!unlink('.'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt))
			throw new RestException(RestCodes::HTTP_500,'Couldn\'t delete the file at the given uri (fs'
				.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');

		return new RestResponse(RestCodes::HTTP_410,
			array('Content-type'=>xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	}
