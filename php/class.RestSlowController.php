<?php
class RestSlowController extends RestController
	{
	static $ctrInf;
	function __construct(RestRequest $request)
		{
		// Checking uri nodes validity
		if($request->uriNodes->count()>1)
			throw new RestException(RestCodes::HTTP_400,'Too many nodes in that uri.');
		if($request->filePath=='/')
			throw new RestException(RestCodes::HTTP_301,'Redirecting to the right uri for the http controller.', '', array('Location'=>RestServer::Instance()->server->location.'slow?'.$request->queryString));
		if($request->method!=RestMethods::OPTIONS&&($request->filePath||$request->fileName||$request->fileExt))
			throw new RestException(RestCodes::HTTP_400,'Slow controller can\'t have file path, name or ext (Sample : ?uri=/fsi/public.dat)','filePath:'.$request->filePath.'fileName:'.$request->fileName.'fileExt:'.$request->fileExt);
		$driver=new RestSlowDriver($request);
		parent::__construct($driver);
		}
	}
RestSlowController::$ctrInf=new stdClass();
RestSlowController::$ctrInf->description='Slows down the response.';