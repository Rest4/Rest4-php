<?php
class RestApplikeController extends RestController
	{
	static $ctrInf;
	static function getCtrInf()
		{
		$ctrInf=new stdClass();
		$ctrInf->description='Inherit and get a permissive uri node filter.';
		return $ctrInf;
		}
	function checkUriInputs($request)
		{
		if($request->fileName&&!preg_match('/^([a-z0-9]+)$/i',$request->fileName))
			{
			throw new RestException(RestCodes::HTTP_400,'Illegal character(s) found in the file name ([a-Z0-9] only)');
			}
		if(isset($request->uriNodes[1])&&!preg_match('/^([a-z\-]+)$/i',$request->uriNodes[1]))
			{
			throw new RestException(RestCodes::HTTP_400,'Illegal character(s) found in the locale name ([a-Z-] only)');
			}
		}
	}
