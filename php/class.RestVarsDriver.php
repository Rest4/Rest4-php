<?php
class RestVarsDriver extends RestDriver
{
  public function __construct(RestRequest $request)
  {
    parent::__construct($request);
  }
  // Helper to build driver informations
  public static function getDrvInf($methods=0)
  {
    $drvInf=new stdClass();
    $drvInf->usage='.(json|dat|php|xml|html|form)';
    $drvInf->methods=new stdClass();
    $drvInf->methods->options=new stdClass();
    $drvInf->methods->options->outputMimes=RestVarsResponse::MIMES;
    $drvInf->methods->head=new stdClass();
    $drvInf->methods->head->outputMimes=RestVarsResponse::MIMES;
    if ($methods&RestMethods::GET) {
      // HEAD and GET resources must have the same query params
      $drvInf->methods->get=$drvInf->methods->head;
    }
    if ($methods&RestMethods::PUT) {
      $drvInf->methods->put=new stdClass();
      $drvInf->methods->put->outputMimes=RestVarsResponse::MIMES;
    }
    if ($methods&RestMethods::POST) {
      $drvInf->methods->post=new stdClass();
      $drvInf->methods->post->outputMimes=RestVarsResponse::MIMES;
    }
    if ($methods&RestMethods::DELETE) {
      $drvInf->methods->delete=new stdClass();
      $drvInf->methods->delete->outputMimes=RestVarsResponse::MIMES;
    }
    if ($methods&RestMethods::PATCH) {
      $drvInf->methods->patch=new stdClass();
      $drvInf->methods->patch->outputMimes=RestVarsResponse::MIMES;
    }

    return $drvInf;
  }
  // if the head method is not provided, execute get and empty content
  public function head()
  {
    $response=$this->get();
    $response->vars=new stdClass();

    return $response;
  }
}

