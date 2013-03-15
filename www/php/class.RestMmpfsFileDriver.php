<?php
class RestMmpfsFileDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='Mmpfs: Multiple Multi Path File Driver';
		$drvInf->description='Manage a multiple multi path file an it\'s content.';
		$drvInf->usage='/mmpfs/path/folder1,folder2,foldern/filename.ext?mode=(merge|append)';
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
		$this->filePathes=array();
		$uriOptions=array();
		$uriOptionsCount=1;
		for($i=$this->request->uriNodes->count()-1; $i>0; $i--)
			{
			$uriOptions[$i]=explode(',',$this->request->uriNodes[$i]);
			$uriOptionsCount*=sizeof($uriOptions[$i]);
			}
		if($uriOptionsCount==0) // Must be 1 to run. Maybe should remove mpfs and use mmpfs only.
			throw new RestException(RestCodes::HTTP_303,'No multiple node given, use mpfs instead', '', array('Location'=>RestServer::Instance()->server->location.'mpfs'.$this->request->filePath));
		for($i=$uriOptionsCount; $i>0; $i--)
			array_push($this->filePathes,'');
		
		for($i=1, $j=$this->request->uriNodes->count(); $i<$j; $i++)
			{
			for($k=0, $l=sizeof($this->filePathes); $k<$l; $k++)
				{
				$index=0;
				$cells=1;
				for($m=$i+1; $m<$j; $m++)
					{
					$cells*=sizeof($uriOptions[$m]);
					}
				$index=floor($k/$cells)%sizeof($uriOptions[$i]);
				$this->filePathes[$k].='/'.$uriOptions[$i][$index];
				}
			}
		$exists=false;
		for($k=0, $l=sizeof($this->filePathes); $k<$l; $k++)
			{
			$this->filePathes[$k].='.'.$this->request->fileExt;
			foreach($this->core->server->paths as $path)
				{
				clearstatcache(false,$path.'..'.$this->filePathes[$k]);
				if(file_exists($path.'..'.$this->filePathes[$k]))
					{ // Remove the file path if he doesn't exist ?
					$exists=true;
					}
				}
			}
		if(!$exists)
			throw new RestException(RestCodes::HTTP_410,'No file found for the given uri (mmpfs'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');

		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'text/plain')
			);
		}
	function get()
		{
		$this->head();
		$response=new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'text/plain')
			);
		$mime=xcUtils::getMimeFromExt($this->request->fileExt);
		
		$exists=false;
		if(($mime=='application/internal'||$mime=='text/lang')&&$this->queryParams->mode=='merge')
			{
			$response->content=new stdClass();
			foreach($this->filePathes as $filePath)
				{
				$response->appendToHeader('X-Rest-Uncacheback','/fs'.$filePath);
				foreach($this->core->server->paths as $path)
					{
					clearstatcache(false,$path.'..'.$filePath);
					if(file_exists($path.'..'.$filePath))
						{
						$exists=true;
						xcDatas::import($response->content,file_get_contents($path.'..'.$filePath));
						if($this->queryParams->mode=='first')
							break 2;
						}
					}
				}
			}
		else if($mime=='text/xml'&&$this->queryParams->mode=='merge')
			{
			throw new RestException(RestCodes::HTTP_501,'I cannot merge XML files currently (mmpfs'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');
			$response->content=new stdClass();
			foreach($this->filePathes as $filePath)
				{
				$response->appendToHeader('X-Rest-Uncacheback','/fs'.$filePath);
				foreach($this->core->server->paths as $path)
					{
					clearstatcache(false,$path.'..'.$filePath);
					if(file_exists($path.'..'.$filePath))
						{
						$exists=true;
						xcDatas::import($response->content,file_get_contents($path.'..'.$filePath));
						if($this->queryParams->mode=='first')
							break 2;
						}
					}
				}
			}
		else
			{
			if($this->queryParams->mode=='merge')
				throw new RestException(RestCodes::HTTP_400,'Merge mode is not usable with this file type (mmpfs'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');
			$response->content='';
			foreach($this->filePathes as $filePath)
				{
				$response->appendToHeader('X-Rest-Uncacheback','/fs'.$filePath);
				foreach($this->core->server->paths as $path)
					{
					clearstatcache(false,$path.'..'.$filePath);
					if(file_exists($path.'..'.$filePath))
						{
						$exists=true;
						if($this->queryParams->mode=='first')
							{
							$response->content=file_get_contents($path.'..'.$filePath);
							break 2;
							}
						else
							$response->content.=($response->content?"\n":'').file_get_contents($path.'..'.$filePath);
						}
					}
				}
			if($mime=='application/internal'||$mime=='text/lang')
				$mime='text/plain';
			}
		$response->setHeader('Content-type',$mime);
		if(!$exists)
			throw new RestException(RestCodes::HTTP_410,'No file found for the given uri (mmpfs'.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');
		if($this->queryParams->download)
			{
			$response->setHeader('X-Rest-Cache','None');
			$response->setHeader('Content-Disposition','attachment; filename="'.$this->queryParams->download.'.'.$this->request->fileExt.'"');
			}
		return $response;
		}
	}
RestMmpfsFileDriver::$drvInf=RestMmpfsFileDriver::getDrvInf();