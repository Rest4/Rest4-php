<?php
class RestSlowResponse extends RestStreamedResponse
{
  private $_response;
  private $_delay;
  private $_i=0;
  private $_chunks;
  public function __construct($response, $delay)
  {
    parent::__construct($response->code, $response->headers);
    $response->setHeader('Content-Type',$response->getHeader('Content-Type'));
    if (!($response instanceof RestStreamedResponse)) {
      if($response->getHeader('Content-Type')=='text/varstream'
          ||$response->getHeader('Content-Type')=='text/lang') {
        $response->setHeader('Content-Type','text/plain');
        if ($response->content instanceof ArrayObject
            ||$response->content instanceof stdClass) {
          $response->content=Varstream::export($response->content);
        } else
          $response->content=xcUtilsInput::filterAsCdata(
                               utf8_encode(print_r($response->content,true)));
      }
      $this->_chunks=explode("\n",$response->getContents());
    }
    $this->_response=$response;
    $this->_delay=$delay;
  }
  public function pump()
  {
    ob_flush();
    flush();
    usleep($this->_delay);
    if ($this->_response instanceof RestStreamedResponse) {
      return $this->_response->pump();
    } else
      if (isset($this->_chunks[$this->_i])) {
        $this->_i++;

        return $this->_chunks[$this->_i-1]."\n";
      }

    return '';
  }
}

