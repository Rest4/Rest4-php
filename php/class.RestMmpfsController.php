<?php
class RestMmpfsController extends RestFslikeController
	{
	static $ctrInf;
	function __construct(RestRequest $request)
		{
		// Checking uri validity
		$this->checkUriInputs($request);
		$this->checkUriSyntax($request);
		// Finding the driver to run
		if(!$request->fileName)
			$driver=new RestMmpfsFolderDriver($request);
		else
			$driver=new RestMmpfsFileDriver($request);
		parent::__construct($driver);
		}
	function getResponse()
		{
		$response=parent::getResponse();
		$response->setHeader('Cache-Control','public, max-age=31536000');
		return $response;
		}
	}
RestMmpfsController::$ctrInf=new stdClass();
RestMmpfsController::$ctrInf->description='Multiple multi-path file provider.';