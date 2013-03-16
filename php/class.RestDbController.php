<?php
class RestDbController extends RestController
	{
	static $ctrInf;
	function __construct(RestRequest $request)
		{
		$request->database='';
		$request->table='';
		$request->entry='';
		if($request->uriNodes->count()>4)
			throw new RestException(RestCodes::HTTP_400,'Too many nodes in that uri.');
		if(isset($request->uriNodes[1])&&$request->uriNodes[1])
			{
			$request->database=$request->uriNodes[1];
			if(isset($request->uriNodes[2])&&$request->uriNodes[2])
				{
				$request->table=$request->uriNodes[2];
					if(isset($request->uriNodes[3])&&$request->uriNodes[3]!=='')
					$request->entry=$request->uriNodes[3];
				}
			}
		// Reject folders
		if($request->isFolder)
			throw new RestException(RestCodes::HTTP_301,'Redirecting to the right uri for this ressource.', '', array('Location'=>RestServer::Instance()->server->location.'db'.($request->database?'/'.$request->database:'').($request->table?'/'.$request->table:'').($request->entry?'/'.$request->entry:'').($request->fileExt?'.'.$request->fileExt:'').($request->queryString?'?'.$request->queryString:'')));
		// Lauching the good driver
		if(ctype_digit($request->entry))
			$driver=new RestDbEntryDriver($request);
		else if($request->entry=='list')
			{
			$driver=new RestDbEntriesDriver($request);
			}
		else if($request->entry=='import')
			{
			$driver=new RestDbTableImportDriver($request);
			}
		else if($request->entry!=='')
			throw new RestException(RestCodes::HTTP_400,'Can\'t interpret entry node in that uri ('.$request->entry.')');
		else if($request->table)
			$driver=new RestDbTableDriver($request);
		else if($request->database)
			$driver=new RestDbBaseDriver($request);
		else
			$driver=new RestDbServerDriver($request);
		parent::__construct($driver);
		}/*
	function getResponse()
		{
		$response=parent::getResponse();
		$response->setHeader('X-Rest-Cache','None');
		return $response;
		}*/
	}
RestDbController::$ctrInf=new stdClass();
RestDbController::$ctrInf->description='Serve database contents.';