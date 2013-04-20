<?php
class RestFsFolderDriver extends RestFsDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='Fs: Folder Driver';
		$drvInf->description='Manage a folder and list his content.';
		$drvInf->usage='/fs/path/';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='*';
		$drvInf->methods->head=$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='*';
		$drvInf->methods->put=new stdClass();
		$drvInf->methods->put->outputMimes='*';
		$drvInf->methods->put->queryParams=new MergeArrayObject();
		$drvInf->methods->put->queryParams[0]=new stdClass();
		$drvInf->methods->put->queryParams[0]->name='force';
		$drvInf->methods->put->queryParams[0]->value='no';
		$drvInf->methods->delete=new stdClass();
		$drvInf->methods->delete->outputMimes='*';
		$drvInf->methods->delete->queryParams=new MergeArrayObject();
		$drvInf->methods->delete->queryParams[0]=new stdClass();
		$drvInf->methods->delete->queryParams[0]->name='recursive';
		$drvInf->methods->delete->queryParams[0]->value='no';
		return $drvInf;
		}
	function head()
		{
		clearstatcache(false,'.'.$this->request->filePath);
		if(!file_exists('.'.$this->request->filePath))
			throw new RestException(RestCodes::HTTP_410,'No folder found for the given uri'
				.' (fs'.$this->request->filePath.')');
		if(!is_dir('.'.$this->request->filePath))
			throw new RestException(RestCodes::HTTP_500,'The given uri seems to not be a folder'
				.' (fs'.$this->request->filePath.')');

		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'text/plain')
			);
		}
	function get()
		{
		$response=$this->head();
		if($response->code==RestCodes::HTTP_200)
			{
			$response->content='';
			$folder = opendir('.'.$this->request->filePath);
			while ($filename = readdir($folder))
				{
				if ($filename && $filename != "." && $filename != "..")
					{
					$response->content.=($response->content?"\n":'').$filename;
					}
				}
			}

		return $response;
		}
	function put()
		{
		$parentFolder='';
		if($this->request->uriNodes->count()>2)
			$parentFolder=preg_replace('/^\/(.+)\/([^\/]+)\/$/','/$1',$this->request->filePath);
		clearstatcache(false,'.'.$parentFolder);
		if($parentFolder&&!@file_exists('.'.$parentFolder))
			{
			if($this->queryParams->force=='yes')
				$this->createParentFolders();
			else
				throw new RestException(RestCodes::HTTP_400,
					'The parent folder does not exists ('.$parentFolder.')');
			}
		clearstatcache(false,'.'.$this->request->filePath);
		if(!file_exists('.'.$this->request->filePath))
			{
			if(!mkdir('.'.$this->request->filePath))
				throw new RestException(RestCodes::HTTP_500,
					'Couldn\'t create the folder at the given uri (fs'.$this->request->filePath.')');
			chmod('.'.$this->request->filePath,0700);
			}
		return new RestResponse(RestCodes::HTTP_201,
			array('Content-type'=>'text/plain','X-Rest-Uncache'=>'/fs'.$parentFolder),
			'Folder created: /fs'.$this->request->filePath);
		}
	function delete()
		{
		$parentFolder=preg_replace('/^\/(.+)\/([^\/]+)\/$/','/$1',$this->request->filePath);
		if($this->queryParams->recursive=='yes')
			{
			if($this->request->uriNodes->count()>10)
				{
				throw new RestException(RestCodes::HTTP_500,
					'Cannot delete more than 10 folders recursively.');
				}
			$res=new RestResource(new RestRequest(RestMethods::GET,'/fsi'
				.substr($this->request->filePath,0,strlen($this->request->filePath)-1).'.dat?mode=light'));
			$res=$res->getResponse();
			if($res->code!=RestCodes::HTTP_200)
				return $res;
			foreach($res->vars->files as $file)
				{
				if($file->isDir)
					{
					$res=new RestResource(new RestRequest(RestMethods::DELETE,'/fs'
						.$this->request->filePath.$file->name.'/?recursive=yes'));
					$res=$res->getResponse();
					if($res->code!=RestCodes::HTTP_410)
						{
						throw new RestException(RestCodes::HTTP_500,'Unable to delete (uri: /fs'
							.$this->request->filePath.$file->name.'/?recursive=yes, code: '.$res->code
							.', content: '.$res->getContents().')');
						}
					}
				else
					{
					$res=new RestResource(new RestRequest(RestMethods::DELETE,'/fs'
						.$this->request->filePath.$file->name));
					$res=$res->getResponse();
					if($res->code!=RestCodes::HTTP_410)
						{
						throw new RestException(RestCodes::HTTP_500,'Unable to delete (uri: /fs'
							.$this->request->filePath.$file->name.', code: '.$res->code.', content: '
							.$res->getContents().')');
						}
					}
				}
			}
		if($this->get()->getContents()!='')
			throw new RestException(RestCodes::HTTP_400,'The folder is not empty (fs'
				.$this->request->filePath.')');
		clearstatcache(false,'.'.$this->request->filePath);
		if(file_exists('.'.$this->request->filePath))
			if(!rmdir('.'.$this->request->filePath))
				throw new RestException(RestCodes::HTTP_500,'Couldn\'t delete the folder at the given uri (fs'
					.$this->request->filePath.')');
		return new RestResponse(RestCodes::HTTP_410,
			array('Content-type'=>'text/plain','X-Rest-Uncache'=>'/fs'.$parentFolder),
			'No more folder at the given uri (fs'.$this->request->filePath.')');
		}
	}
