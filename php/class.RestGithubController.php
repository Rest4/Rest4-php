<?php
class RestGithubController extends RestFslikeController
{
  static $ctrInf;
  public static function getCtrInf()
  {
    $ctrInf=new stdClass();
    $ctrInf->description='Serve contents from a GitHub commit.';

    return $ctrInf;
  }
  public function __construct(RestRequest $request)
  {
    // Checking uri nodes validity
    $this->checkUriInputs($request);
    // Reject folders
    if ($request->isFolder) {
      throw new RestException(RestCodes::HTTP_400,'Folders are not allowed.');
    } else {
      $driver=new RestGithubDriver($request);
    }
    parent::__construct($driver);
  }
}

