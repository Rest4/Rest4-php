<?php
class RestBugBugsDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new xcDataObject();
		$drvInf->name='Bug: Driver';
		$drvInf->description='Handle bug reports.';
		$drvInf->usage='/bugs(.ext)?';
		$drvInf->methods=new xcDataObject();
		$drvInf->methods->options=new xcDataObject();
		$drvInf->methods->options->outputMimes='application/internal';
		$drvInf->methods->head=new xcDataObject();
		$drvInf->methods->head->outputMimes='application/internal';
		$drvInf->methods->post=new xcDataObject();
		$drvInf->methods->post->outputMimes='application/internal';
		return $drvInf;
		}
	function head()
		{
		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'application/internal')
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
				trigger_error('Cannot write the bug screenshot (code: '.$res->code.', uri: '.$this->core->getVar('site.location').'cache/'.$this->core->site->cache.'/'.$this->request->controller.$this->request->filePath.$this->request->fileName.($this->request->queryString?'-'.md5($this->request->queryString):'').($this->request->fileExt?'.'.$this->request->fileExt:'').')');
			}
		$response->setHeader('Content-Type','application/internal');
		$response->setHeader('X-Rest-Uncache','/db/'.$this->core->database->database.'/bugs|/fsi/db/bugs');
		return $response;	
		}
	}
RestBugBugsDriver::$drvInf=RestBugBugsDriver::getDrvInf();