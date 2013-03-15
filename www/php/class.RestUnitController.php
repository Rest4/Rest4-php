<?php
class RestUnitController extends RestController
	{
	static $ctrInf;
	function __construct(RestRequest $request)
		{
		// Checking uri nodes validity
		if($request->uriNodes->count()>1)
			throw new RestException(RestCodes::HTTP_400,'Too many nodes in that uri.');
		// Reject folders
		if($request->isFolder)
			throw new RestException(RestCodes::HTTP_301,'Redirecting to the right uri for this ressource.', '', array('Location'=>RestServer::Instance()->server->location.'users'.($request->user?'/'.$request->user:'').($request->fileExt?'.'.$request->fileExt:'').($request->queryString?'?'.$request->queryString:'')));
		$driver=new RestUnitDriver($request);
		parent::__construct($driver);
		}
	function getResponse()
		{
		$response=parent::getResponse();
		$response->setHeader('X-Rest-Cache','None');
		return $response;
		}
	}
RestUnitController::$ctrInf=new stdClass();
RestUnitController::$ctrInf->description='Rest oriented unit testing.';