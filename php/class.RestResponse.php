<?php
class RestResponse extends RestMessage
{
  public $code;
  public function __construct($code=RestCodes::HTTP_200, $headers=array(),
    $content='')
  {
    $this->code=$code;
    parent::__construct($headers,$content);
  }
  public function getContents()
  {
    return $this->content;
  }
}

