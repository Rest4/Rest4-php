<?php
class RestFsiDriver extends RestVarsDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=parent::getDrvInf(RestMethods::GET);
		$drvInf->name='Fsi: File Info Driver';
		$drvInf->description='Expose a folder content.';
		$drvInf->usage='/fsi/path/foldername'.$drvInf->usage;
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='mode';
		$drvInf->methods->get->queryParams[0]->value='normal';
		return $drvInf;
		}
	function head()
		{
		if(!file_exists('.'.$this->request->filePath.$this->request->fileName))
			throw new RestException(RestCodes::HTTP_410,'No folder found for the given uri'
				.' (/fsi'.$this->request->filePath.$this->request->fileName.')');
		if(!is_dir('.'.$this->request->filePath.$this->request->fileName))
			throw new RestException(RestCodes::HTTP_500,'The given uri seems to not be a folder'
				.' (/fsi'.$this->request->filePath.$this->request->fileName.')');

		return new RestResponseVars(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	function get()
		{
		$response=$this->head();
		if($response->code==RestCodes::HTTP_200)
			{
			$response->vars->files=new MergeArrayObject();
			$tempList=new MergeArrayObject();
			$folder = opendir('.'.$this->request->filePath.$this->request->fileName);
			while ($filename = readdir($folder))
				{
				if(($filename!='..'||$this->request->filePath))
					{
					if($this->queryParams->mode=='light'&&($filename=='.'||$filename=='..'))
						continue;
					$entry=new stdClass();
					$entry->name = xcUtilsInput::filterValue($filename,'text','cdata');
					if(is_dir('.'.($this->request->filePath?$this->request->filePath.$this->request->fileName
						.($this->request->fileName?'/':''):'/').$filename))
						{
						$entry->isDir = true;
						}
					else
						{
						$entry->mime = xcUtils::getMimeFromFilename($filename);
						$entry->size = @filesize('.'.($this->request->filePath?$this->request->filePath
							.$this->request->fileName.($this->request->fileName?'/':''):'/').$filename);
						$entry->isDir = false;
						}
					$entry->lastModified = @filemtime('.'.($this->request->filePath?$this->request->filePath
						.$this->request->fileName.($this->request->fileName?'/':''):'/').$filename);
					$tempList->append($entry);
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
					$response->vars->files->append($file);
					}
			}
		$response->setHeader('X-Rest-Uncacheback','/fs'.$this->request->filePath.$this->request->fileName);
		return $response;
		}
	}
