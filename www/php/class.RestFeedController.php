<?php
class RestFeedController extends RestController
	{
	static $ctrInf;
	function __construct(RestRequest $request)
		{
		// Checking uri nodes validity
		if($request->uriNodes->count()>1)
			throw new RestException(RestCodes::HTTP_400,'Too many nodes in that uri.');
		if($request->filePath=='/')
			throw new RestException(RestCodes::HTTP_301,'Redirecting to the right uri for the http controller.', '', array('Location'=>RestServer::Instance()->server->location.'http?'.$request->queryString));
		if($request->filePath||$request->fileName)
			throw new RestException(RestCodes::HTTP_400,'Http controller can\'t have file path or name (Sample : ?uri.ext=http%3A%2F%2Fwww.elitwork.com%2Factualite.atom)','filePath:'.$request->filePath.'fileName:'.$request->fileName.'fileExt:'.$request->fileExt);
		/*if((!$request->queryString)||strpos($request->queryString,'?url='))
			throw new RestException(RestCodes::HTTP_400,'Http controller must have a legal query string (Sample : ?uri=http%3A%2F%2Fwww.elitwork.com%2Factualite.atom)');*/
		$driver=new RestFeedDriver($request);
		parent::__construct($driver);
		}
	}
RestFeedController::$ctrInf=new xcDataObject();
RestFeedController::$ctrInf->description='Serve feeds content.';