<?php
class RestSessionController extends RestController
{
  static $ctrInf;
  public static function getCtrInf()
  {
    $ctrInf=new stdClass();
    $ctrInf->description='Manage visotrs sessions.';

    return $ctrInf;
  }
  public function __construct(RestRequest $request)
  {
    // Reject folders
    if ($request->isFolder) {
      throw new RestException(RestCodes::HTTP_400, 'Folders are not allowed.');
    } else {
      $driver=new RestSessionDriver($request);
    }
    parent::__construct($driver);
  }
}

