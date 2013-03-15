<?php
class RestMmpfsiController extends RestFslikeController
	{
	static $ctrInf;
	function __construct(RestRequest $request)
		{
		// Checking uri nodes validity
		$this->checkUriInputs($request);
		// Reject folders
		if($request->isFolder)
			throw new RestException(RestCodes::HTTP_301,'Redirecting to the right uri for this ressource.', '', array('Location'=>RestServer::Instance()->server->location.'mmpfsi'.($request->filePath?substr($request->filePath,0,strlen($request->filePath)-1):'').'.dat'));
		else
			$driver=new RestMmpfsiDriver($request);
		parent::__construct($driver);
		}
	function getResponse()
		{
		$response=parent::getResponse();
		$response->setHeader('Cache-Control','public, max-age=31536000');
		return $response;
		}
	}
RestMmpfsiController::$ctrInf=new stdClass();
RestMmpfsiController::$ctrInf->description='Multiple multi-path file information provider.';