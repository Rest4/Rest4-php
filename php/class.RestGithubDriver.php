<?php
class RestGithubDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=new stdClass();
		$drvInf->name='Github: File Driver for Github repositories';
		$drvInf->description='Retrieve a file content and display it.';
		$drvInf->usage='/github/path/to/repos/filename.ext';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='text/varstream';
		$drvInf->methods->head=$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='*';
		return $drvInf;
		}
	function head()
		{
		// Testing the file
		$res=new RestResource(new RestRequest(RestMethods::HEAD,'/http?uri=https://raw.github.com'.$this->request->filePath.$this->request->fileName.($this->request->fileExt?'.'.$this->request->fileExt:'')));
		$res=$res->getResponse();
		if($res->code!=RestCodes::HTTP_200)
			return $res;
		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>xcUtils::getMimeFromExt($this->request->fileExt))
			);
		}
	function get()
		{
		// Getting the file
		$res=new RestResource(new RestRequest(RestMethods::GET,'/http?uri=https://raw.github.com'.$this->request->filePath.$this->request->fileName.($this->request->fileExt?'.'.$this->request->fileExt:'')));
		$res=$res->getResponse();
		if($res->code!=RestCodes::HTTP_200)
			return $res;
		$res->setHeader('Content-Type',xcUtils::getMimeFromExt($this->request->fileExt));
		if(xcUtils::getMimeFromExt($this->request->fileExt)=='text/html')
			{
			// Could override absolute/rel links  ?
			}
		$res->setHeader('Cache-Control','public, max-age=31536000');
		$res->setHeader('X-Rest-Uncacheback','/http?uri=https://raw.github.com'.$this->request->filePath.$this->request->fileName.($this->request->fileExt?'.'.$this->request->fileExt:''));
		return $res;
		}
	}
