<?php
class RestAppDriver extends RestCompositeDriver
	{
	function prepare()
		{
		// Preparing composite structure
		parent::prepare();
		// Importing main language file
		$this->loadLocale('/app/lang/$-'.$this->request->uriNodes[2].'.lang');
		}
	function finish()
		{
		// Creating response
		$response=new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>xcUtils::getMimeFromExt($this->core->document->type))
			);
		// Getting main template
		$template=new xcTemplate($this->loadTemplate('/app/'.$this->core->document->type
			.'/'.$this->request->uriNodes[2].'.tpl','',true),$this->core);
		$response->content=$template->getContents();
		return $response;
		}
	}
?>