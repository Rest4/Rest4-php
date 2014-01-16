<?php
class RestSqlController extends RestController
{
  static $ctrInf;
  public static function getCtrInf()
  {
    $ctrInf=new stdClass();
    $ctrInf->description='Execute SQL.';

    return $ctrInf;
  }
  public function __construct(RestRequest $request)
  {
    // Checking uri nodes validity
    if ($request->uriNodes->count()>1) {
      throw new RestException(RestCodes::HTTP_400,
        'Too many nodes in that uri.');
    }
    // Launching the driver
    if($request->queryString) {
      throw new RestException(RestCodes::HTTP_400,
      'File controller do not accept any query string'
      . ' ('.$request->queryString.')');
    } else {
      $driver=new RestSqlDriver($request);
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

