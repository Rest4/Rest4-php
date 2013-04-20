<?php
class RestMpfsFileDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=new stdClass();
		$drvInf->name='Mmpfs: Multiple Multi Path File Driver';
		$drvInf->description='Manage a multiple multi path file an it\'s content.';
		$drvInf->usage='/mpfs/path/folder1,folder2,foldern/filename.ext?mode=(merge|append)';
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
		$response = new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>xcUtils::getMimeFromExt($this->request->fileExt))
			);
		// Filling an array with each possible uris
		$possibleUris=array();
		$uriOptions=array();
		$uriOptionsCount=1;
		for($i=$this->request->uriNodes->count()-1; $i>0; $i--)
			{
			$uriOptions[$i]=explode(',',$this->request->uriNodes[$i]);
			$uriOptionsCount*=sizeof($uriOptions[$i]);
			}
		for($i=$uriOptionsCount; $i>0; $i--)
			array_push($possibleUris,'');
		
		for($i=1, $j=$this->request->uriNodes->count(); $i<$j; $i++)
			{
			for($k=0, $l=sizeof($possibleUris); $k<$l; $k++)
				{
				$index=0;
				$cells=1;
				for($m=$i+1; $m<$j; $m++)
					{
					$cells*=sizeof($uriOptions[$m]);
					}
				$index=floor($k/$cells)%sizeof($uriOptions[$i]);
				$possibleUris[$k].='/'.$uriOptions[$i][$index];
				}
			}
		// Building file pathes list by verifying existence of uris in each include pathes
		$this->filePathes=array();
		$exists=false;
		for($k=0, $l=sizeof($possibleUris); $k<$l; $k++)
			{
			$possibleUris[$k].='.'.$this->request->fileExt;
			$response->appendToHeader('X-Rest-Uncacheback','/fs'.$possibleUris[$k]);
			// First mode : ini pathes are tested from the highest to the lowest
			if($this->queryParams->mode=='first')
				{
				for($i=0, $j=sizeof($this->core->server->paths); $i<$j; $i++)
					{
					clearstatcache(false,$this->core->server->paths[$i].'.'.$possibleUris[$k]);
					if(file_exists($this->core->server->paths[$i].'.'.$possibleUris[$k]))
						{
						array_push($this->filePathes,$this->core->server->paths[$i].'.'.$possibleUris[$k]);
						break 2; // stops when the first file is found
						}
					}
				}
			// Append|Merge mode testing from the lowest to the highest
			else
				{
				for($i=sizeof($this->core->server->paths)-1; $i>=0; $i--)
					{
					clearstatcache(false,$this->core->server->paths[$i].'.'.$possibleUris[$k]);
					if(file_exists($this->core->server->paths[$i].'.'.$possibleUris[$k]))
						{
						array_push($this->filePathes,$this->core->server->paths[$i].'.'.$possibleUris[$k]);
						}
					}
				}
			}
		if(!sizeof($this->filePathes))
			throw new RestException(RestCodes::HTTP_410,'No file found for the given uri (mpfs'
				.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');
		return $response;
		}
	function get()
		{
		$response=$this->head();
		$mime=$response->getHeader('Content-Type');
		
		$exists=false;
		if($this->queryParams->mode=='merge')
			{
			if($mime!='text/varstream'&&$mime!='text/lang')
				throw new RestException(RestCodes::HTTP_400,
					'Merge mode is not usable with this file type (mpfs'.$this->request->filePath
					.$this->request->fileName.'.'.$this->request->fileExt.')');
			$vars=new stdClass();
			foreach($this->filePathes as $filePath)
				Varstream::import($vars,file_get_contents($filePath));
			$response->content=Varstream::export($vars);
			}
		else if($this->queryParams->mode=='append')
			{
			if(strpos($mime,'text/')!==0)
				throw new RestException(RestCodes::HTTP_400,
					'Append mode is not usable with this file type (mpfs'
					.$this->request->filePath.$this->request->fileName.'.'.$this->request->fileExt.')');
			$response->content='';
			foreach($this->filePathes as $filePath)
				$response->content.=($response->content?"\n":'').file_get_contents($filePath);
			}
		else
			{
			$response->content=file_get_contents($this->filePathes[0]);
			}
		if($this->queryParams->download)
			{
			$response->setHeader('X-Rest-Cache','None');
			$response->setHeader('Content-Disposition','attachment; filename="'
				.$this->queryParams->download.'.'.$this->request->fileExt.'"');
			}
		return $response;
		}
	}
