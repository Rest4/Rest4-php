<?php
class RestFsController extends RestFslikeController
{
  static $ctrInf;
  public static function getCtrInf()
  {
    $ctrInf=new stdClass();
    $ctrInf->description='Serve contents of the filesystem.';

    return $ctrInf;
  }
  public function __construct(RestRequest $request)
  {
    // Checking uri validity
    $this->checkUriInputs($request);
    $this->checkUriSyntax($request);
    // Finding the driver to run
    if (!$request->fileName) {
      $driver=new RestFsFolderDriver($request);
    } else {
      $driver=new RestFsFileDriver($request);
    }
    parent::__construct($driver);
  }
  public function getResponse()
  {
    $response=parent::getResponse();
    $response->setHeader('X-Rest-Cache','None');
    $response->setHeader('Cache-Control','public, max-age=31536000');

    return $response;
  }
}

