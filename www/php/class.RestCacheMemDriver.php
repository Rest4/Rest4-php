<?php
class RestCacheMemDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new xcDataObject();
		$drvInf->name='Cache: Mem Cache Driver';
		$drvInf->description='(!) Will cache resources with Memcache.';
		$drvInf->usage='/cache/mem/uri-md5(queryString).ext';
		$drvInf->methods=new xcDataObject();
		$drvInf->methods->options=new xcDataObject();
		$drvInf->methods->options->outputMimes='application/internal';
		$drvInf->methods->get=new xcDataObject();
		$drvInf->methods->get->outputMimes='*';
		$drvInf->methods->put=new xcDataObject();
		$drvInf->methods->put->outputMimes='*';
		$drvInf->methods->post=new xcDataObject();
		$drvInf->methods->post->outputMimes='*';
		$drvInf->methods->delete=new xcDataObject();
		$drvInf->methods->delete->outputMimes='*';
		return $drvInf;
		}
	function get()
		{
		throw new RestException(RestCodes::HTTP_501,'Not done yet');
		}
	function put()
		{
		throw new RestException(RestCodes::HTTP_501,'Not done yet');
		}
	function post()
		{
		throw new RestException(RestCodes::HTTP_501,'Not done yet');
		}
	function delete()
		{
		throw new RestException(RestCodes::HTTP_501,'Not done yet');
		}
	}
RestCacheMemDriver::$drvInf=RestCacheMemDriver::getDrvInf();