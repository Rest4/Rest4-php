<?php
class RestVarsDriver extends RestDriver
	{
	function __construct(RestRequest $request)
		{
		parent::__construct($request);
		}
	// Helper to build driver informations
	static function getDrvInf($methods)
		{
		$drvInf=new stdClass();
		$drvInf->usage='.(json|dat|php|xml|html|form)';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes=RestResponseVars::MIMES;
		$drvInf->methods->head=new stdClass();
		$drvInf->methods->head->outputMimes=RestResponseVars::MIMES;
		if($methods&RestMethods::GET)
			{
			// HEAD and GET resources must have the same query params
			$drvInf->methods->get=$drvInf->methods->head;
			}
		if($methods&RestMethods::PUT)
			{
			$drvInf->methods->put=new stdClass();
			$drvInf->methods->put->outputMimes=RestResponseVars::MIMES;
			}
		if($methods&RestMethods::POST)
			{
			$drvInf->methods->post=new stdClass();
			$drvInf->methods->post->outputMimes=RestResponseVars::MIMES;
			}
		if($methods&RestMethods::DELETE)
			{
			$drvInf->methods->delete=new stdClass();
			$drvInf->methods->delete->outputMimes=RestResponseVars::MIMES;
			}
		return $drvInf;
		}
	// if the head method is not provided, execute get and empty content
	function head()
		{
		$response=$this->get();
		$response->vars=new stdClass();
		return $response;
		}
	}
