<?php
class RestAppController extends RestCompositeController
	{
	static $ctrInf;
	static function getCtrInf()
		{
		$ctrInf=new stdClass();
		$ctrInf->description='Allows you to create a webapp.';
		return $ctrInf;
		}
	function __construct(RestRequest $request)
		{
		// Checking composite request
		$this->checkCompositeRequest($request);
		// Launching app driver
		$driverClass='RestApp'.ucfirst($request->uriNodes[2]).'Driver';
		if(!xcUtils::classExists($driverClass))
			throw new RestException(RestCodes::HTTP_400,
				'The given driver is not present here ('.$driverClass.')');
		$driver=new $driverClass($request);
		parent::__construct($driver);
		}
	function getResponse()
		{
		$response=parent::getResponse();
		$response->setHeader('X-Rest-Cache','None');
		$response->setHeader('Cache-Control','private');
		return $response;
		}
	}
