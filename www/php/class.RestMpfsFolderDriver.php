<?php
class RestMpfsFolderDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='Mpfs: Multi Path Folder Driver';
		$drvInf->description='Retrieve and fetch folder contents for each paths.';
		$drvInf->usage='/mpfs/folderpath/';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='*';
		$drvInf->methods->head=$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='*';
		return $drvInf;
		}
	function head()
		{
		$exists=false;
		foreach($this->core->server->paths as $path)
			{
			clearstatcache(false,$path.'..'.$this->request->filePath);
			if(file_exists($path.'..'.$this->request->filePath))
				{
				if(!is_dir($path.'..'.$this->request->filePath))
					throw new RestException(RestCodes::HTTP_500,'The given uri seems to not be a folder (mpfs'.$this->request->filePath.')');
				$exists=true;
				break;
				}
			}
		if(!$exists)
			throw new RestException(RestCodes::HTTP_410,'No folder found for the given uri (mpfs'.$this->request->filePath.')');

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
		$exists=false;
		$filenames=array();
		$response->content='';
		foreach($this->core->server->paths as $path)
			{
			clearstatcache(false,$path.'..'.$this->request->filePath);
			if(file_exists($path.'..'.$this->request->filePath))
				{
				if(!is_dir($path.'..'.$this->request->filePath))
					throw new RestException(RestCodes::HTTP_500,'The given uri seems to not be a folder (mpfs'.$this->request->filePath.')');
				$exists=true;
				$folder = opendir($path.'..'.$this->request->filePath);
				while ($filename = readdir($folder))
					{
					if ($filename && $filename != "." && $filename != "..")
						{
						if(array_search($filename,$filenames)===false)
							{
							array_push($filenames,$filename);
							$response->content.=($response->content?"\n":'').$filename;
							}
						}
					}
				}
			}
		if(!$exists)
			throw new RestException(RestCodes::HTTP_410,'No folder found for the given uri (mpfs'.$this->request->filePath.')');
		$response->setHeader('X-Rest-Uncacheback','/fs'.$this->request->filePath);
		return $response;
		}
	}
RestMpfsFolderDriver::$drvInf=RestMpfsFolderDriver::getDrvInf();