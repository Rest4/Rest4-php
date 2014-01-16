<?php
class RestDocController extends RestSiteController
{
  static $ctrInf;
  public static function getCtrInf()
  {
    $ctrInf=new stdClass();
    $ctrInf->description='Show the Rest framework documentation.';

    return $ctrInf;
  }
  public function __construct(RestRequest $request)
  {
    $this->_index='root';
    parent::__construct($request);
  }
}

