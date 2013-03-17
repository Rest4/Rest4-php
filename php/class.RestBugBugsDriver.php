<?php
class RestBugBugsDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='Bug: Driver';
		$drvInf->description='Handle bug reports.';
		$drvInf->usage='/bugs(.ext)?';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='text/varstream';
		$drvInf->methods->head=new stdClass();
		$drvInf->methods->head->outputMimes='text/varstream';
		$drvInf->methods->post=new stdClass();
		$drvInf->methods->post->outputMimes='text/varstream';
		return $drvInf;
		}
	function head()
		{
		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'text/varstream')
			);
		}
	function post()
		{
		$response=$this->head();
		$this->core->db->selectDb($this->core->database->database);
		$this->core->db->query('INSERT INTO bugs (label,url,browser,screen,whatdone,whathad,whatshould,usermail,console,security) VALUES ('
			.'"'.xcUtilsInput::filterValue($this->request->content->entry->label,'text','cdata').'",'
			.'"'.xcUtilsInput::filterValue($this->request->content->entry->url,'text','cdata').'",'
			.'"'.xcUtilsInput::filterValue($this->request->content->entry->browser,'text','cdata').'",'
			.'"'.xcUtilsInput::filterValue($this->request->content->entry->screen,'text','cdata').'",'
			.'"'.xcUtilsInput::filterValue($this->request->content->entry->whatdone,'text','cdata').'",'
			.'"'.xcUtilsInput::filterValue($this->request->content->entry->whathad,'text','cdata').'",'
			.'"'.xcUtilsInput::filterValue($this->request->content->entry->whatshould,'text','cdata').'",'
			.'"'.xcUtilsInput::filterValue($this->request->content->entry->usermail,'email','mail').'",'
			.'"'.xcUtilsInput::filterValue($this->request->content->entry->console,'text','pcdata').'",'
			.'"'.xcUtilsInput::filterValue($this->request->content->entry->security,'number','int').'"'
			.')');
		$id=$this->core->db->insertId();
		if($this->request->content->entry&&$this->request->content->entry->screenshot)
			{
			$params=explode(';',substr($this->request->content->entry->screenshot,5));
			$cnt=explode(',',$params[1]);
			$res=new RestResource(new RestRequest(RestMethods::PUT,'/fs/db/'.$this->core->database->database.'/bugs/'.$id.'/files/screenshot.jpg?force=yes',array('Content-Type'=>$params[0]),base64_decode($cnt[1])));
			$res=$res->getResponse();
			if($res->code!=RestCodes::HTTP_201)
				trigger_error('Cannot write the bug screenshot (code: '.$res->code.', uri: '.$this->core->site->location.'cache/'.$this->core->site->cache.'/'.$this->request->controller.$this->request->filePath.$this->request->fileName.($this->request->queryString?'-'.md5($this->request->queryString):'').($this->request->fileExt?'.'.$this->request->fileExt:'').')');
			}
		$response->setHeader('Content-Type','text/varstream');
		$response->setHeader('X-Rest-Uncache','/db/'.$this->core->database->database.'/bugs|/fsi/db/bugs');
		return $response;	
		}
	}
RestBugBugsDriver::$drvInf=RestBugBugsDriver::getDrvInf();