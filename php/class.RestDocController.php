<?php
class RestDocController extends RestSiteController
	{
	static $ctrInf;
	function __construct(RestRequest $request)
		{
		$this->_index='root';
		parent::__construct($request);
		}
	}
RestDocController::$ctrInf=new stdClass();
RestDocController::$ctrInf->description='Show the Rest framework documentation.';