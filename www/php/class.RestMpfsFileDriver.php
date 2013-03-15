<?php
class RestMpfsFileDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='Mpfs: Multi Path File Driver';
		$drvInf->description='Manage a file an it\'s content.';
		$drvInf->usage='/mpfs/filepath/filename.ext?mode=(merge|append)';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='*';
		$drvInf->methods->head=$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='*';
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='mode';
		$drvInf->methods->get->queryParams[0]->value='first';
		$drvInf->methods->get->queryParams[1]=new stdClass();
		$drvInf->methods->get->queryParams[1]->name='download';
		$drvInf->methods->get->queryParams[1]->type='text';
		$drvInf->methods->get->queryParams[1]->filter='iparameter';
		$drvInf->methods->get->queryParams[1]->value='';
		return $drvInf;
		}
	function head()
		{
		$exists=false;
		foreach($this->core->server->paths as $path)
			{
			clearstatcache(false,$path.'..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt);
			if(file_exists($path.'..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt))
				{
				$exists=true;
				break;
				}
			}
		if(!$exists)
			throw new RestException(RestCodes::HTTP_410,'No file found for the given uri (mpfs'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');

		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'text/plain')
			);
		}
	function get()
		{
		$response=new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'text/plain')
			);
		$mime=xcUtils::getMimeFromExt($this->request->fileExt);
		
		$exists=false;
		if(($mime=='application/internal'||$mime=='text/lang')&&$this->queryParams->mode=='merge')
			{
			$response->content=new stdClass();
			for($i=$this->core->server->paths->count()-1; $i>=0; $i--)
				{
				clearstatcache(false,$this->core->server->paths[$i].'..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt);
				if(file_exists($this->core->server->paths[$i].'..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt))
					{
					$exists=true;
					xcDatas::import($response->content,file_get_contents($this->core->server->paths[$i].'..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt));
					if($this->queryParams->mode=='first')
						break;
					}
				}
			}
		else if($mime=='text/xml'&&$this->queryParams->mode=='merge')
			{
			$response->content=new stdClass();
			for($i=$this->core->server->paths->count()-1; $i>=0; $i--)
				{
				clearstatcache(false,$this->core->server->paths[$i].'..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt);
				if(file_exists($this->core->server->paths[$i].'..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt))
					{
					$exists=true;
					xcDatas::import($response->content,file_get_contents($this->core->server->paths[$i].'..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt));
					if($this->queryParams->mode=='first')
						break;
					}
				}
			}
		else
			{
			if($mime=='text/xml'&&$this->queryParams->mode=='append')
				throw new RestException(RestCodes::HTTP_400,'Append mode is not yet usable with XML files (mpfs'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');
			if($this->queryParams->mode=='merge')
				throw new RestException(RestCodes::HTTP_400,'Merge mode is not usable with this file type (mpfs'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');
			$response->content='';
			for($i=$this->core->server->paths->count()-1; $i>=0; $i--)
				{
				clearstatcache(false,$this->core->server->paths[$i].'..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt);
				if(file_exists($this->core->server->paths[$i].'..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt))
					{
					$exists=true;
					if($this->queryParams->mode=='first')
						$response->content=file_get_contents($this->core->server->paths[$i].'..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt);
					else
						$response->content.=($response->content?"\n":'').file_get_contents($this->core->server->paths[$i].'..'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt);
					}
				}
			if($mime=='application/internal'||$mime=='text/lang')
				$mime='text/plain';
			}
		$response->setHeader('Content-type',$mime);
		if(!$exists)
			throw new RestException(RestCodes::HTTP_410,'No file found for the given uri (mpfs'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');
		$response->setHeader('X-Rest-Uncacheback','/fs'.$this->request->filePath.$this->request->fileName);
		if($this->queryParams->download)
			{
			$response->setHeader('X-Rest-Cache','None');
			$response->setHeader('Content-Disposition','attachment; filename="'.$this->queryParams->download.'.'.$this->request->fileExt.'"');
			}
		return $response;
		}
	}
RestMpfsFileDriver::$drvInf=RestMpfsFileDriver::getDrvInf();