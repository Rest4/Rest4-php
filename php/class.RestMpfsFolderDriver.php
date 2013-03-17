<?php
class RestMpfsFolderDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='Mmpfs: Multiple Multi Path Folder Driver';
		$drvInf->description='Retrieve and fetch folder contents for each paths.';
		$drvInf->usage='/mpfs/path/folder1,folder2,foldern/';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='*';
		$drvInf->methods->head=$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='*';
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
			foreach($this->core->server->paths as $path)
				{
				clearstatcache(false,$path.'.'.$this->filePathes[$k]);
				if(file_exists($path.'.'.$this->filePathes[$k]))
					{ // Remove the file path if he doesn't exist ?
					if(!is_dir($path.'.'.$this->filePathes[$k]))
						throw new RestException(RestCodes::HTTP_500,'The given uri seems to not be a folder (mpfs'.$filePath.')');
					$exists=true;
					}
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
		$this->head();
		$response=new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'text/plain')
			);
		$filenames=array();
		$response->content='';
		$exists=false;
		for($k=0, $l=sizeof($this->filePathes); $k<$l; $k++)
			{
			$response->appendToHeader('X-Rest-Uncacheback','/fs'.$this->filePathes[$k]);
			foreach($this->core->server->paths as $path)
				{
				clearstatcache(false,$path.'.'.$this->filePathes[$k]);
				if(file_exists($path.'.'.$this->filePathes[$k]))
					{ // Remove the file path if he doesn't exist ?
					if(!is_dir($path.'.'.$this->filePathes[$k]))
						throw new RestException(RestCodes::HTTP_500,'The given uri seems to not be a folder (mpfs'.$filePath.')');
					$exists=true;
					$folder = opendir($path.'.'.$this->filePathes[$k]);
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
			}
		if(!$exists)
			throw new RestException(RestCodes::HTTP_410,'No folder found for the given uri (mpfs'.$this->request->filePath.')');
		return $response;
		}
	}
RestMpfsFolderDriver::$drvInf=RestMpfsFolderDriver::getDrvInf();