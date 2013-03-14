<?php
class RestCacheController extends RestFslikeController
	{
	static $ctrInf;
	function __construct(RestRequest $request)
		{
		// Checking uri nodes validity
		$this->checkUriInputs($request);
		// Finding the right driver
		if($request->uriNodes[1]=='xcache')
			$driver=new RestCacheXDriver($request);
		else if($request->uriNodes[1]=='apc')
			$driver=new RestCacheApcDriver($request);
		else if($request->uriNodes[1]=='memcache')
			$driver=new RestCacheMemDriver($request);
		else if($request->uriNodes[1]=='fs')
			$driver=new RestFileDriver($request);
		// else if($request->uriNodes[1]=='delegate') // Could be interesting to create a specific driver to delagate the cache to a proxy
		//	 $driver=new RestCacheDelegateDriver($request);
		else
			throw new RestException(RestCodes::HTTP_500,'Given an unsupported cache system ('.$request->uriNodes[1].').');
		parent::__construct($driver);
		}
	function getResponse()
		{
		$response=parent::getResponse();
		$response->setHeader('X-Rest-Cache','None');
		return $response;
		}
	}
RestCacheController::$ctrInf=new xcDataObject();
RestCacheController::$ctrInf->description='Provide caching solutions.';