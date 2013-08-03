<?php
class RestAppDriver extends RestCompositeDriver
	{
	function prepare()
		{
		// Preparing composite structure
		parent::prepare();
		// Importing main language file
		$this->loadLocale('/app/lang/$-'.$this->request->uriNodes[2].'.lang?mode=merge',false);
		}
	function finish()
		{
		return new RestTemplatedResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>xcUtils::getMimeFromExt($this->core->document->type)),
			$this->loadTemplate('/app/'.$this->core->document->type
			.'/'.$this->request->uriNodes[2].'.tpl','',true),
			$this->core);
		}
	}
