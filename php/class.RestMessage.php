<?php
class RestMessage
{
  public $headers;
  public $content;
  public function __construct($headers=array(), $content='')
  {
    $this->headers=array();
    foreach ($headers as $name=>$value) {
      $name=self::_normName($name);
      if (!isset($value)) {
        throw new RestException(RestCodes::HTTP_500,
          'No value transmitted for the header '
          .$name.' in the RestMessage constructor.');
      }
      $this->setHeader($name,$value);
    }
    $this->content=$content;
  }
  public function headerIsset($name)
  {
    $name=self::_normName($name);
    if (!isset($this->headers[$name])) {
      return false;
    }

    return true;
  }
  public function setHeader($name, $value)
  {
    $name=self::_normName($name);
    if (!isset($value)) {
      throw new RestException(RestCodes::HTTP_500,
        'No value transmitted for the header '.$name.'.');
    }
    $this->headers[$name] = $value;
  }
  public function appendToHeader($name, $value)
  {
    $name=self::_normName($name);
    if (!isset($value)) {
      throw new RestException(RestCodes::HTTP_500,
        'No value transmitted for the header '.$name.'.');
    }
    if (!isset($this->headers[$name])) {
      $this->headers[$name]='';
    }
    $this->headers[$name] .= ($this->headers[$name] ? '|' : '') . $value;
  }
  public function getHeader($name)
  {
    $name=self::_normName($name);
    if (isset($this->headers[$name])) {
      return $this->headers[$name];
    }

    return '';
  }
  public function unsetHeader($name)
  {
    $name=self::_normName($name);
    if (isset($this->headers[$name])) {
      unset($this->headers[$name]);
    }
  }
  private static function _normName($name)
  {
    return  str_replace(' ', '-',
      ucwords(strtolower(str_replace('-',' ',$name))));
  }
}

