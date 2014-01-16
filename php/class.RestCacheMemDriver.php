<?php
class RestCacheMemDriver extends RestDriver
{
  static $drvInf;
  public static function getDrvInf($methods=0)
  {
    $drvInf=new stdClass();
    $drvInf->name='Cache: Mem Cache Driver';
    $drvInf->description='(!) Will cache resources with Memcache.';
    $drvInf->usage='/cache/mem/uri-md5(queryString).ext';
    $drvInf->methods=new stdClass();
    $drvInf->methods->options=new stdClass();
    $drvInf->methods->options->outputMimes='text/varstream';
    $drvInf->methods->get=new stdClass();
    $drvInf->methods->get->outputMimes='*';
    $drvInf->methods->put=new stdClass();
    $drvInf->methods->put->outputMimes='*';
    $drvInf->methods->post=new stdClass();
    $drvInf->methods->post->outputMimes='*';
    $drvInf->methods->delete=new stdClass();
    $drvInf->methods->delete->outputMimes='*';

    return $drvInf;
  }
  public function get()
  {
    throw new RestException(RestCodes::HTTP_501,'Not done yet');
  }
  public function put()
  {
    throw new RestException(RestCodes::HTTP_501,'Not done yet');
  }
  public function post()
  {
    throw new RestException(RestCodes::HTTP_501,'Not done yet');
  }
  public function delete()
  {
    throw new RestException(RestCodes::HTTP_501,'Not done yet');
  }
}

