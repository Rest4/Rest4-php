<?php
class RestCompositeController extends RestApplikeController
{
  public $_index='index';
  private $_i18n='';
  public function checkCompositeRequest(RestRequest $request)
  {
    // Checking uri nodes validity
    $this->checkUriInputs($request);
    // Decoding locale string
    $request->lang='';
    $request->locale='';
    if (isset($request->uriNodes[1])) {
      $i18nStrings=explode('-',$request->uriNodes[1]);
      if (sizeof($i18nStrings)<3&&strlen($i18nStrings[0])<4
        &&$i18nStrings[0]==strtolower($i18nStrings[0])
        &&((!isset($i18nStrings[1]))||(strlen($i18nStrings[1])<4
        &&$i18nStrings[1]==strtoupper($i18nStrings[1])))) {
        $request->lang=$i18nStrings[0];
        $request->locale=(isset($i18nStrings[1])?$i18nStrings[1]:'');
        $request->i18n=$request->lang.($request->locale?'-'.$request->locale:'');
      }
    }
    if (!$request->lang) {
      // Should locale infos in the accept-language field for redirection building
      throw new RestException(RestCodes::HTTP_301,
        'Redirecting to the right uri for this ressource.', '',
        array('Location'=>'/'.$request->controller.'/'
          .RestServer::Instance()->server->defaultLang.'-'
          .RestServer::Instance()->server->defaultLocale
          .'/'.$this->_index.'.html'));
    }
    // Testing the driver node
    if (!(isset($request->uriNodes[2])&&$request->uriNodes[2])) {
      throw new RestException(RestCodes::HTTP_301,
        'Redirecting to the right uri for this ressource.', '',
        array('Location'=>'/'.$request->controller.'/'.$request->uriNodes[1]
          .'/'.$this->_index.'.html'));
    }
    // Reject folders
    if ($request->isFolder) {
      throw new RestException(RestCodes::HTTP_400,'Not supposed to happen.');
    }
    // Keep the i18n string: dirty, will probably necessit a refactoring
    $this->_i18n = $request->i18n;
  }
  public function getResponse()
  {
    $response=parent::getResponse();
    $response->setHeader('Content-Language', $this->_i18n);
    return $response;
  }
}
