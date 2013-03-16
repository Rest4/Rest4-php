<?php
class RestFsiDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='Fsi: File Info Driver';
		$drvInf->description='Expose a folder content.';
		$drvInf->usage='/fsi/path/foldername.ext';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='application/internal';
		$drvInf->methods->head=$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='application/internal';
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='mode';
		$drvInf->methods->get->queryParams[0]->value='normal';
		return $drvInf;
		}
	function head()
		{
		if(!file_exists('.'.$this->request->filePath.$this->request->fileName))
			throw new RestException(RestCodes::HTTP_410,'No folder found for the given uri (/fsi'.$this->request->filePath.$this->request->fileName.')');
		if(!is_dir('.'.$this->request->filePath.$this->request->fileName))
			throw new RestException(RestCodes::HTTP_500,'The given uri seems to not be a folder (/fsi'.$this->request->filePath.$this->request->fileName.')');

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
			$response->content->files=new MergeArrayObject();
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
					if(is_dir('.'.($this->request->filePath?$this->request->filePath.$this->request->fileName.($this->request->fileName?'/':''):'/').$filename))
						{
						$entry->isDir = true;
						}
					else
						{
						$entry->mime = xcUtils::getMimeFromFilename($filename);
						$entry->size = @filesize('.'.($this->request->filePath?$this->request->filePath.$this->request->fileName.($this->request->fileName?'/':''):'/').$filename);
						$entry->isDir = false;
						}
					$entry->lastModified = @filemtime('.'.($this->request->filePath?$this->request->filePath.$this->request->fileName.($this->request->fileName?'/':''):'/').$filename);
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
					$response->content->files->append($file);
					}
			$response->setHeader('Content-Type','application/internal');
			}
		$response->setHeader('X-Rest-Uncacheback','/fs'.$this->request->filePath.$this->request->fileName);
		return $response;
		}
	}
RestFsiDriver::$drvInf=RestFsiDriver::getDrvInf();