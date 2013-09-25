<?php
class RestCacheController extends RestFslikeController
	{
	static $ctrInf;
	static function getCtrInf()
		{
		$ctrInf=new stdClass();
		$ctrInf->description='Provide caching solutions.';
		return $ctrInf;
		}
	function __construct(RestRequest $request)
		{
		// Checking uri nodes validity
		$this->checkUriInputs($request);
		// Finding the right driver
		if($request->uriNodes[1]=='xcache')
			{
			$driver=new RestCacheXDriver($request);
			}
		else if($request->uriNodes[1]=='apc')
			{
			$driver=new RestCacheApcDriver($request);
			}
		else if($request->uriNodes[1]=='memcache')
			{
			$driver=new RestCacheMemDriver($request);
			}
		else if($request->uriNodes[1]=='fs')
			{
			$driver=new RestFileDriver($request);
			}
		// No need to create a specific driver to delagate the cache to a proxy
		// routers are there for that purpose
		else
			{
			throw new RestException(RestCodes::HTTP_500,
				'Given an unsupported cache system ('.$request->uriNodes[1].').');
			}
		parent::__construct($driver);
		}
	function getResponse()
		{
		$response=parent::getResponse();
		$response->setHeader('X-Rest-Cache','None');
		return $response;
		}
	}
