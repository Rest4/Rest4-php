<?php
class RestAuthController extends RestController
{
  static $ctrInf;
  public static function getCtrInf()
  {
    $ctrInf=new stdClass();
    $ctrInf->description='Provides authentification tools.';

    return $ctrInf;
  }
  public function __construct(RestRequest $request)
  {
    // Checking uri nodes validity
    if ($request->uriNodes->count()>2) {
      throw new RestException(RestCodes::HTTP_400,'Too many nodes in that uri.');
    }
    // Finding the right driver
    if ($request->uriNodes[1]=='basic') {
      $driver=new RestAuthBasicDriver($request);
    } else if ($request->uriNodes[1]=='digest') {
      $driver=new RestAuthDigestDriver($request);
    } else if ($request->uriNodes[1]=='session') {
      $driver=new RestAuthSessionDriver($request);
    } else {
      throw new RestException(RestCodes::HTTP_400,
        'Unsupported HTTP authentification type ('.$request->uriNodes[1].').');
    }
    parent::__construct($driver);
  }
  public function getResponse()
  {
    $response=parent::getResponse();
    $response->setHeader('X-Rest-Cache','None');

    return $response;
  }
}
