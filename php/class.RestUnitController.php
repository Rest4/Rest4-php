<?php
class RestUnitController extends RestController
{
  static $ctrInf;
  public static function getCtrInf()
  {
    $ctrInf=new stdClass();
    $ctrInf->description='Rest oriented unit testing.';

    return $ctrInf;
  }
  public function __construct(RestRequest $request)
  {
    // Checking uri nodes validity
    if ($request->uriNodes->count()>1) {
      throw new RestException(RestCodes::HTTP_400,
        'Too many nodes in that uri.');
    }
    // Reject folders
    if ($request->isFolder) {
      throw new RestException(RestCodes::HTTP_301,
        'Redirecting to the right uri for this ressource.', '',
        array('Location'=>'/unit'.($request->fileExt?'.'.$request->fileExt:'')
          .($request->queryString?'?'.$request->queryString:'')));
    }
    $driver=new RestUnitDriver($request);
    parent::__construct($driver);
  }
  public function getResponse()
  {
    $response=parent::getResponse();
    $response->setHeader('X-Rest-Cache','None');

    return $response;
  }
}

