<?php
class RestFsController extends RestFslikeController
	{
	static $ctrInf;
	function __construct(RestRequest $request)
		{
		// Checking uri validity
		$this->checkUriInputs($request);
		$this->checkUriSyntax($request);
		// Finding the driver to run
		if(!$request->fileName)
			$driver=new RestFsFolderDriver($request);
		else
			$driver=new RestFsFileDriver($request);
		parent::__construct($driver);
		}
	function getResponse()
		{
		$response=parent::getResponse();
		$response->setHeader('X-Rest-Cache','None');
		$response->setHeader('Cache-Control','public, max-age=31536000');
		return $response;
		}
	}
RestFsController::$ctrInf=new stdClass();
RestFsController::$ctrInf->description='Serve contents of the filesystem.';