<?php
class RestMpfsiDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='Mpfsi: Multi-Path File Info Driver';
		$drvInf->description='Expose a folder content throught multiple pathes.';
		$drvInf->usage='/mpfsi/path/foldername.ext';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='application/internal';
		$drvInf->methods->head=$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='application/internal';
		$drvInf->methods->get->queryParams=new xcObjectCollection();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='mode';
		$drvInf->methods->get->queryParams[0]->value='normal';
		return $drvInf;
		}
	function head()
		{
		$exists=false;
		foreach($this->core->server->paths as $path)
			{
			clearstatcache(false,$path.'..'.$this->request->filePath.$this->request->fileName);
			if(file_exists($path.'..'.$this->request->filePath.$this->request->fileName))
				{
				if(!is_dir($path.'..'.$this->request->filePath.$this->request->fileName))
					throw new RestException(RestCodes::HTTP_500,'The given uri seems to not be a folder (/mpfsi'.$this->request->filePath.$this->request->fileName.')');
				$exists=true;
				}
			}
		if(!$exists)
			throw new RestException(RestCodes::HTTP_500,'The given uri seems to not be a folder (/mpfsi'.$this->request->filePath.$this->request->fileName.')');

		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'application/internal')
			);
		}
	function get()
		{
		$response=$this->head();
		if($response->code==RestCodes::HTTP_200)
			{
			$response->content=new stdClass();
			$response->content->files=new xcObjectCollection();
			$tempList=new xcObjectCollection();
			
		foreach($this->core->server->paths as $path)
			{
			clearstatcache(false,$path.'..'.$this->request->filePath.$this->request->fileName);
			if(file_exists($path.'..'.$this->request->filePath.$this->request->fileName))
				{
				if(!is_dir($path.'..'.$this->request->filePath.$this->request->fileName))
					throw new RestException(RestCodes::HTTP_500,'The given uri seems to not be a folder (mpfs'.$this->request->filePath.$this->request->fileName.')');
				$folder = opendir($path.'..'.$this->request->filePath.$this->request->fileName);
				while ($filename = readdir($folder))
					{
					if($this->queryParams->mode=='light'&&($filename=='.'||$filename=='..'))
						continue;
					$exists=false;
					foreach($tempList as $file)
						{
						if($file->name==$filename)
							{
							$exists=true; break;
							}
						}
					if($exists)
						continue;
					$entry=new stdClass();
					$entry->name = xcUtilsInput::filterValue($filename,'text','cdata');
					if(is_dir($path.'..'.$this->request->filePath.$this->request->fileName.($this->request->fileName?'/':'').$filename))
						{
						$entry->isDir = true;
						}
					else
						{
						$entry->mime = xcUtils::getMimeFromFilename($filename);
						$entry->size = @filesize($path.'..'.$this->request->filePath.$this->request->fileName.($this->request->fileName?'/':'').$filename);
						$entry->isDir = false;
						}
					$entry->lastModified = @filemtime($path.'..'.$this->request->filePath.$this->request->fileName.($this->request->fileName?'/':'').$filename);
					$tempList->append($entry);
					}
				}
			}
			
			$tempList->uasort(function ($a, $b) {
				if ($a->name == $b->name) {
					return 0;
				}
				return ($a->name < $b->name) ? -1 : 1;
			});
				
			foreach($tempList as $file)
				{
				$response->content->files->append($file);
				}
			$response->setHeader('Content-Type','application/internal');
			}
		$response->setHeader('X-Rest-Uncacheback','/fs'.$this->request->filePath.$this->request->fileName);
		return $response;
		}
	}
RestMpfsiDriver::$drvInf=RestMpfsiDriver::getDrvInf();