<?php
class RestSiteController extends RestCompositeController
{
  public static function getCtrInf()
  {
    $ctrInf=new stdClass();
    $ctrInf->description='Extend to create websites.';

    return $ctrInf;
  }
  public function __construct(RestRequest $request)
  {
    // Checking composite request
    $this->checkCompositeRequest($request);
    // Launching page driver
    // Private pages:  /site/en-US/private/login/pagename.html
    if ('private' == $request->uriNodes[2]) {
      if ($request->uriNodes->count()<5) {
        throw new RestException(RestCodes::HTTP_410,
          'No private web page node given.');
      }
      if('me' == $request->uriNodes[3]) {
        $core = RestServer::Instance();
        throw new RestException(RestCodes::HTTP_301,
          'Redirecting to your private page.','',
          array('Location' => '/'.$request->uriNodes[0].'/'
            .$request->uriNodes[1].'/private/'.$core->user->login
            .'/'.$request->uriNodes[4].'.'.$request->fileExt));
      }
      $driverClass='Rest'.ucfirst($request->uriNodes[0]).'Private'
         .ucfirst($request->uriNodes[4]).'Driver';
      // Default pages fallback
      if (!xcUtils::classExists($driverClass)) {
        $driverClass2='RestSitePrivate'
           .ucfirst($request->uriNodes[4]).'Driver';
        if (!xcUtils::classExists($driverClass2)) {
          $driverClass2='Rest'.ucfirst($request->uriNodes[0])
            .'PrivateDefaultDriver';
          if (!xcUtils::classExists($driverClass2)) {
            $driverClass2='RestSitePrivateDefaultDriver';
            if (!xcUtils::classExists($driverClass2)) {
              throw new RestException(RestCodes::HTTP_400,
                'The given driver is not present here ('.$driverClass.')');
            }
          }
        }
        $driverClass=$driverClass2;
      }
    // Public pages:  /site/en-US/pagename.html
    } else {
      if ($request->uriNodes->count()<3) {
        throw new RestException(RestCodes::HTTP_410,'No web page node given.');
      }
      $driverClass='Rest'.ucfirst($request->uriNodes[0])
                   .ucfirst($request->uriNodes[2]).'Driver';
      // Default pages fallback
      if (!xcUtils::classExists($driverClass)) {
        $driverClass2='RestSite'.ucfirst($request->uriNodes[2]).'Driver';
        if (!xcUtils::classExists($driverClass2)) {
          $driverClass2='Rest'.ucfirst($request->uriNodes[0]).'DefaultDriver';
          if (!xcUtils::classExists($driverClass2)) {
            $driverClass2='RestSiteDefaultDriver';
            if (!xcUtils::classExists($driverClass2)) {
              throw new RestException(RestCodes::HTTP_400,
                'The given driver is not present here ('.$driverClass.')');
            }
          }
        }
        $driverClass=$driverClass2;
      }
    }
    $driver=new $driverClass($request);
    parent::__construct($driver);
  }
}

