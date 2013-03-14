<?php
class RestMpfsController extends RestFslikeController
	{
	static $ctrInf;
	function __construct(RestRequest $request)
		{
		// Checking uri validity
		$this->checkUriInputs($request);
		$this->checkUriSyntax($request);
		// Finding the driver to run
		if(!$request->fileName)
			$driver=new RestMpfsFolderDriver($request);
		else
			$driver=new RestMpfsFileDriver($request);
		parent::__construct($driver);
		}
	function getResponse()
		{
		$response=parent::getResponse();
		//$response->setHeader('X-Rest-Cache','None');
		$response->setHeader('Cache-Control','public, max-age=31536000');
		return $response;
		}
	}
RestMpfsController::$ctrInf=new xcDataObject();
RestMpfsController::$ctrInf->description='Multi-path file provider.';