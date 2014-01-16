<?php
class RestHttpController extends RestController
{
  static $ctrInf;
  public static function getCtrInf()
  {
    $ctrInf=new stdClass();
    $ctrInf->description='Simple HTTP wrapper.';

    return $ctrInf;
  }
  public function __construct(RestRequest $request)
  {
    // Checking uri nodes validity
    if ($request->uriNodes->count()>1) {
      throw new RestException(RestCodes::HTTP_400,'Too many nodes in that uri.');
    }
    if ($request->filePath=='/') {
      throw new RestException(RestCodes::HTTP_301,
        'Redirecting to the right uri for the http controller.',
        '', array('Location'=>RestServer::Instance()->server->location
          .'http?'.$request->queryString));
    }
    if($request->method!=RestMethods::OPTIONS
      &&($request->filePath||$request->fileName||$request->fileExt)) {
      throw new RestException(RestCodes::HTTP_400,
        'Http controller can\'t have file path, name or ext'
        .' (Sample : ?uri=http%3A%2F%2Fwww.elitwork.com)','filePath:'
        .$request->filePath.'fileName:'.$request->fileName
        .'fileExt:'.$request->fileExt);
    }
    $driver=new RestHttpDriver($request);
    parent::__construct($driver);
  }
}

