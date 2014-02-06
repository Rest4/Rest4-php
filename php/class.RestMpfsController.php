<?php
class RestMpfsController extends RestFslikeController
{
  static $ctrInf;
  public static function getCtrInf()
  {
    $ctrInf=new stdClass();
    $ctrInf->description='Multiple multi-path file provider.';

    return $ctrInf;
  }
  public function __construct(RestRequest $request)
  {
    // Checking uri validity
    $this->checkUriInputs($request);
    $this->checkUriSyntax($request);
    // Finding the driver to run
    if (!$request->fileName) {
      $driver=new RestMpfsFolderDriver($request);
    } else {
      $driver=new RestMpfsFileDriver($request);
    }
    parent::__construct($driver);
  }
  public function getResponse()
  {
    $response=parent::getResponse();
    $response->setHeader('Cache-Control','public, max-age=31536000');

    return $response;
  }
}

