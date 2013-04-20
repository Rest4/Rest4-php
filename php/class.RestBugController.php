<?php
class RestBugController extends RestController
	{
	static $ctrInf;
	static function getCtrInf()
		{
		$ctrInf=new stdClass();
		$ctrInf->description='Allow BugMeBack bugreport handling.';
		return $ctrInf;
		}
	function __construct(RestRequest $request)
		{
		// Checking uri nodes validity
		if($request->uriNodes->count()>1)
			throw new RestException(RestCodes::HTTP_400,'Too many nodes in that uri.');
		// Launching the driver
		if($request->queryString)
			throw new RestException(RestCodes::HTTP_400,
				'Bug controller do not accept any query string ('.$request->queryString.')');
		else
			$driver=new RestBugBugsDriver($request);
		parent::__construct($driver);
		}
	function getResponse()
		{
		$response=parent::getResponse();
		$response->setHeader('X-Rest-Cache','None');
		return $response;
		}
	}
