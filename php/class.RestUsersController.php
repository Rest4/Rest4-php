<?php
class RestUsersController extends RestController
{
  static $ctrInf;
  public static function getCtrInf()
  {
    $ctrInf=new stdClass();
    $ctrInf->description='Expose users informations.';

    return $ctrInf;
  }
  public function __construct(RestRequest $request)
  {
    $core=RestServer::Instance();
    // Checking uri nodes validity
    $this->checkUriInputs($request);
    // Checking nodes
    if($request->uriNodes->count()>2)
      throw new RestException(RestCodes::HTTP_400,
        'Too many nodes in that uri.');
    // Reject folders
    if($request->isFolder)
      throw new RestException(RestCodes::HTTP_301,
        'Redirecting to the right uri for this ressource.',
        '', array('Location'=>'/users'
          .(isset($request->uriNodes[1])?'/'.$request->uriNodes[1]:'')
          .($request->fileExt?'.'.$request->fileExt:'')
          .($request->queryString?'?'.$request->queryString:'')));
    if (isset($request->uriNodes[1])) {
      if ($request->uriNodes[1]=='me') {
        if ($core->user->login) {
          throw new RestException(RestCodes::HTTP_301,'You are there.', '',
            array('Location'=>'/users'
              .($core->user->login?'/'.$core->user->login:'')
              .($request->fileExt?'.'.$request->fileExt:'')
              .($request->queryString?'?'.$request->queryString:'')));
        } else {
          throw new RestException(RestCodes::HTTP_400,
            'Cannot tell who you are since you\'re not authentified.');
        }
      } else {
        $driver=new RestUsersUserDriver($request);
      }
    } else {
      $driver=new RestUsersDriver($request);
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

