<?php
// this class is designed to be extended it's purpose is to output
// the resource content progressively
class RestStreamedResponse extends RestResponse
{
  public $core;
  protected $bufferSize;
  public function __construct($code=RestCodes::HTTP_200, $headers=array())
  {
    $output_buffering=ini_get('output_buffering');
    $this->bufferSize=($output_buffering=='Off'?
      0:($output_buffering=='On'?1000000:$output_buffering));
    $this->core=RestServer::Instance();
    parent::__construct($code, $headers, '');
  }
  // called to get the next chunk of data to send
  public function pump()
  {
    return '';
  }
  // used to access the whole datas can be accessed multiple times
  public function getContents()
  {
    // No need to buffer the content
    $this->bufferSize=0;
    // If content is not present, build it
    if (!$this->content) {
      while (($cnt=$this->pump())!=='') {
        $this->content.=$cnt;
      }
    }

    return $this->content;
  }
}

