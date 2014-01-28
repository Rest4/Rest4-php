<?php
class RestCaptchaController extends RestController
{
  static $ctrInf;
  public static function getCtrInf()
  {
    $ctrInf=new stdClass();
    $ctrInf->description='Serve captchas from a string.';

    return $ctrInf;
  }
  public function __construct(RestRequest $request)
  {
    if ($request->uriNodes->count() > 2) {
      throw new RestException(RestCodes::HTTP_400,
        'Too many nodes in that uri.');
    }
    if (1 == $request->uriNodes->count()) {
      throw new RestException(RestCodes::HTTP_400,
        'Please choose the captcha type.');
    }
    if('audio' == $request->uriNodes[1]) {
      $driver=new RestCaptchaAudioDriver($request);
    } else if('visual' == $request->uriNodes[1]) {
      $driver=new RestCaptchaVisualDriver($request);
    }
    parent::__construct($driver);
  }
  public function getResponse()
  {
    $response=parent::getResponse();
    $response->setHeader('X-Rest-Cache','None');
    $response->setHeader('Cache-Control','private, max-age=0');

    return $response;
  }
}

