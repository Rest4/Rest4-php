<?php
class RestAuthController extends RestController
	{
	static $ctrInf;
	function __construct(RestRequest $request)
		{
		// Checking uri nodes validity
		if($request->uriNodes->count()>2)
			throw new RestException(RestCodes::HTTP_400,'Too many nodes in that uri.');
		// Finding the right driver
		if($request->uriNodes[1]=='basic')
			$driver=new RestAuthBasicDriver($request);
		else if($request->uriNodes[1]=='digest')
			$driver=new RestAuthDigestDriver($request);
		else if($request->uriNodes[1]=='session')
			$driver=new RestAuthSessionDriver($request);
		else if($request->uriNodes[1]=='default')
			$driver=new RestAuthDefaultDriver($request);
		else
			throw new RestException(RestCodes::HTTP_400,'Unsupported HTTP authentification type.');
		parent::__construct($driver);
		}
	function getResponse()
		{
		$response=parent::getResponse();
		$response->setHeader('X-Rest-Cache','None');
		return $response;
		}
	}
RestAuthController::$ctrInf=new xcDataObject();
RestAuthController::$ctrInf->description='Provides authentification tools.';