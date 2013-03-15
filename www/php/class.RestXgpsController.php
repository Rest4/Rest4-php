<?php
class RestXgpsController extends RestController
	{
	static $ctrInf;
	function __construct(RestRequest $request)
		{
		// Checking uri nodes validity
		$this->checkUriInputs($request);
		// Finding the right driver to use
		$request->user='';
		if(isset($request->uriNodes[1])&&$request->uriNodes[1])
			{
			if($request->uriNodes->count()<=2)
				{
				if($request->uriNodes[1]=='all')
					$driver=new RestXgpsAllDriver($request);
				else if($request->uriNodes[1]=='cleanup')
					$driver=new RestXgpsCleanupDriver($request);
				else
					throw new RestException(RestCodes::HTTP_400,'Unrecognized node[1].');
				}
			else
				{
				if($request->uriNodes->count()>3)
					throw new RestException(RestCodes::HTTP_400,'Too many nodes in that uri.');
				if($request->uriNodes[2]=='position')
					$driver=new RestXgpsPositionDriver($request);
				else if($request->uriNodes[2]=='near')
					$driver=new RestXgpsNearDriver($request);
				else if($request->uriNodes[2]=='directions')
					$driver=new RestXgpsDirectionsDriver($request);
				else
					throw new RestException(RestCodes::HTTP_400,'Unable to interpret the second node value.');
				$request->user=$request->uriNodes[1];
				}
			}
		else
			throw new RestException(RestCodes::HTTP_400,'This controller requires at least 1 node.');
		parent::__construct($driver);
		}
	function getResponse()
		{
		$response=parent::getResponse();
		$response->setHeader('X-Rest-Cache','None');
		return $response;
		}
	}
RestXgpsController::$ctrInf=new stdClass();
RestXgpsController::$ctrInf->description='Expose GPS datas collected with X1 Intellitrac systems.';