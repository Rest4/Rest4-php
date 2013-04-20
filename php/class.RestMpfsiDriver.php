<?php
class RestMpfsiDriver extends RestVarsDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=parent::getDrvInf(RestMethods::GET|RestMethods::POST);
		$drvInf->name='Mmpfsi: Multiple Multi-Path File Info Driver';
		$drvInf->description='Expose a folder content throught multiple pathes.';
		$drvInf->usage='/mpfsi/path1,path2/foldername'.$drvInf->usage;
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='mode';
		$drvInf->methods->get->queryParams[0]->value='normal';
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
			for($i=sizeof($this->core->server->paths)-1; $i>=0; $i--)
				{
				$path=$this->core->server->paths[$i];
				clearstatcache(false,$path.'.'.$this->filePathes[$k]);
				if(file_exists($path.'.'.$this->filePathes[$k]))
					{ // Remove the file path if he doesn't exist ?
					if(!is_dir($path.'.'.$this->filePathes[$k]))
						throw new RestException(RestCodes::HTTP_500,
							'The given uri seems to not be a folder (mpfs'
							.$this->request->filePath.$this->request->fileName.')');
					$exists=true;
					}
				}
			}
		if(!$exists)
			throw new RestException(RestCodes::HTTP_500,
				'The given uri seems to not exists (/mpfsi'
				.$this->request->filePath.$this->request->fileName.')');

		return new RestResponseVars(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	function get()
		{
		$response=$this->head();
		$response->vars->files=new MergeArrayObject();
		$tempList=new MergeArrayObject();
		$exists=false;
		for($k=0, $l=sizeof($this->filePathes); $k<$l; $k++)
			{
			$response->appendToHeader('X-Rest-Uncacheback','/fs'.$this->filePathes[$k]);
			for($i=sizeof($this->core->server->paths)-1; $i>=0; $i--)
				{
				$path=$this->core->server->paths[$i];
				clearstatcache(false,$path.'.'.$this->filePathes[$k]);
				if(file_exists($path.'.'.$this->filePathes[$k]))
					{ // Remove the file path if he doesn't exist ?
					if(!is_dir($path.'.'.$this->filePathes[$k]))
						throw new RestException(RestCodes::HTTP_500,
							'The given uri seems to not be a folder (mpfs'
							.$this->request->filePath.$this->request->fileName.')');
					$exists=true;
					$folder = opendir($path.'.'.$this->filePathes[$k]);
					while ($filename = readdir($folder))
						{
						if($this->queryParams->mode=='light'
							&&($filename=='.'||$filename=='..'))
							continue;
						// Checking if the file is already in the list
						$itExists=false;
						foreach($tempList as $file)
							{
							if($file->name==$filename)
								{
								$itExists=true; break;
								}
							}
						if($itExists)
							continue;
						// Adding the file
						$entry=new stdClass();
						$entry->name = xcUtilsInput::filterValue($filename,'text','cdata');
						if($this->queryParams->mode=='normal')
							{
							$entry->path = $path;
							}
						if(is_dir($path.'.'.$this->filePathes[$k].'/'.$filename))
							{
							$entry->isDir = true;
							}
						else
							{
							$entry->mime = xcUtils::getMimeFromFilename($filename);
							$entry->size = @filesize($path.'.'.$this->filePathes[$k].'/'.$filename);
							$entry->isDir = false;
							}
						$entry->lastModified = @filemtime($path.'.'.$this->filePathes[$k].'/'.$filename);
						$tempList->append($entry);
						}
					}
				}
			}
		if(!$exists)
			throw new RestException(RestCodes::HTTP_410,
				'No folder found for the given uri (mpfs'.$this->request->filePath.')');
		$tempList->uasort(function ($a, $b) {
			if ($a->name == $b->name) {
				return 0;
			}
			return ($a->name < $b->name) ? -1 : 1;
		});
			
		foreach($tempList as $file)
			{
			$response->vars->files->append($file);
			}
		return $response;
		}
	}
