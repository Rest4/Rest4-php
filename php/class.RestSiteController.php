<?php
class RestSiteController extends RestCompositeController
	{
	static function getCtrInf()
		{
		$ctrInf=new stdClass();
		$ctrInf->description='Extend to create websites.';
		return $ctrInf;
		}
	function __construct(RestRequest $request)
		{
		// Checking composite request
		$this->checkCompositeRequest($request);
		// Launching page driver
		if(!(isset($request->uriNodes[2])&&$request->uriNodes[2]))
			throw new RestException(RestCodes::HTTP_410,'No web page node given.');
		$driverClass='Rest'.ucfirst($request->uriNodes[0])
			.ucfirst($request->uriNodes[2]).'Driver';
		if(!xcUtils::classExists($driverClass))
			{
			$driverClass2='Rest'.ucfirst($request->uriNodes[0]).'DefaultDriver';
			if(!xcUtils::classExists($driverClass2))
				throw new RestException(RestCodes::HTTP_400,
					'The given driver is not present here ('.$driverClass.')');
			$driverClass=$driverClass2;
			}
		$driver=new $driverClass($request);
		parent::__construct($driver);
		}
	}
