<?php
class RestDocController extends RestSiteController
	{
	static $ctrInf;
	function __construct(RestRequest $request)
		{
		parent::__construct($request);
		}
	}
RestDocController::$ctrInf=new stdClass();
RestDocController::$ctrInf->description='Show the Rest framework documentation.';
?>