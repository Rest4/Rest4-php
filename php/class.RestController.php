<?php
class RestController
	{
	public $driver;
	function __construct(RestDriver $driver)
		{
		$this->driver=$driver;
		}
	static function getCtrInf()
		{
		return null;
		}
	function getResponse()
		{
		return $this->driver->getResponse();
		}
	function checkUriInputs($request)
		{
		if($request->fileName&&!xcUtilsInput::isIParameter($request->fileName))
			throw new RestException(RestCodes::HTTP_400,
				'Illegal character(s) found in the file name (a-z/0-9 only)');
		for($i=$request->uriNodes->count()-1; $i>=0; $i--)
			{
			if(!xcUtilsInput::isIParameter($request->uriNodes[$i]))
				throw new RestException(RestCodes::HTTP_400,
					'Illegal character(s) found in the node '.$i.' (a-z/0-9 only)');
			}
		}
	}
