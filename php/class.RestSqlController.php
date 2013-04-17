<?php
class RestSqlController extends RestController
	{
	static $ctrInf;
	function __construct(RestRequest $request)
		{
		// Checking uri nodes validity
		if($request->uriNodes->count()>1)
			throw new RestException(RestCodes::HTTP_400,'Too many nodes in that uri.');
		// Launching the driver
		if($request->queryString)
			throw new RestException(RestCodes::HTTP_400,
				'File controller do not accept any query string ('.$request->queryString.')');
		else
			$driver=new RestSqlDriver($request);
		parent::__construct($driver);
		}
	function getResponse()
		{
		$response=parent::getResponse();
		$response->setHeader('X-Rest-Cache','None');
		return $response;
		}
	}
RestSqlController::$ctrInf=new stdClass();
RestSqlController::$ctrInf->description='Execute SQL.';