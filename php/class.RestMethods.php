<?php
class RestMethods
{
  const OPTIONS=1;
  const HEAD=2;
  const GET=4;
  const POST=8;
  const PUT=16;
  const DELETE=32;
  const PATCH=64;
  public static function getMethodFromString($string)
  {
    $string=strtoupper($string);
    switch ($string) {
    case 'OPTIONS':
      return self::OPTIONS;
      break;
    case 'HEAD':
      return self::HEAD;
      break;
    case 'GET':
      return self::GET;
      break;
    case 'PUT':
      return self::PUT;
      break;
    case 'POST':
      return self::POST;
      break;
    case 'DELETE':
      return self::DELETE;
      break;
    case 'PATCH':
      return self::PATCH;
      break;
    default:
      throw new RestException(RestCodes::HTTP_400,
        'The requested method is not supported ('.$string.')');
      break;
    }
  }
  public static function getStringFromMethod($method)
  {
    switch ($method) {
    case self::OPTIONS:
      return 'OPTIONS';
      break;
    case self::HEAD:
      return 'HEAD';
      break;
    case self::GET:
      return 'GET';
      break;
    case self::PUT:
      return 'PUT';
      break;
    case self::POST:
      return 'POST';
      break;
    case self::DELETE:
      return 'DELETE';
      break;
    case self::PATCH:
      return 'PATCH';
      break;
    default:
      throw new RestException(RestCodes::HTTP_400,
        'The requested method is not supported ('.$method.')');
      break;
    }
  }
}

