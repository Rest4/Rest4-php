<?php
class RestFslikeController extends RestController
	{
	static $ctrInf;
	function checkUriInputs($request)
		{
		// Testing uri node validity
		if($request->fileName&&!preg_match('/^([a-z0-9 \-_,\.]+)$/i',$request->fileName))
			throw new RestException(RestCodes::HTTP_400,'Illegal character(s) found in the file name ([a-z0-9 -_,.] only)');
		for($i=$request->uriNodes->count()-1; $i>=0; $i--)
			{
			if(!preg_match('/^([a-z0-9 \-_,\.]+)$/i',$request->uriNodes[$i]))
				throw new RestException(RestCodes::HTTP_400,'Illegal character(s) found in the node '.$i.' ([a-z0-9 -_,.] only)');
			}
		}
	function checkUriSyntax($request)
		{
		// No file name but a file extension : removing extension
		if($request->fileName==''&&$request->fileExt)
			throw new RestException(RestCodes::HTTP_301,'Redirecting to the right uri for this folder ('.$request->filePath.$request->controller.')', '', array('Location'=>RestServer::Instance()->server->location.$request->controller.$request->filePath));
		// No extension and no '/', redirecting to the folder
		if(($request->filePath=='')||($request->fileExt==''&&$request->fileName))
			throw new RestException(RestCodes::HTTP_301,'Redirecting to the right uri for this folder ('.$request->filePath.$request->controller.$request->fileName.'/)', '', array('Location'=>RestServer::Instance()->server->location.$request->controller.$request->filePath.$request->fileName.'/'));
		}
	}
RestFslikeController::$ctrInf=new stdClass();
RestFslikeController::$ctrInf->description='Extend me to match file names.';