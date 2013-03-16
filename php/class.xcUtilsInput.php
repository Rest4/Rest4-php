<?php
class xcUtilsInput
	{
	public static $core;

	public static function filterValue($value, $type='text', $filter='parameter')
		{
		switch ($type)
			{
			case 'number':
				switch($filter)
					{
					case 'int':
						if(self::isInt($value))
							return intval($value);
						break;
					case 'float':
						if(self::isFloat($value))
							return floatval($value);
						break;
					default:
						throw new Exception('self -> filterValue : the filter "'.$filter.'" is not supported for this type : '.$type.'.');
					}
				break;
			case 'date':
				switch($filter)
					{
					case 'day':
						if(self::isDay($value))
							return $value;
						break;
					case 'date':
						if(self::isDate($value))
							return $value;
						break;
					default:
						throw new Exception('self -> filterValue : the filter "'.$filter.'" is not supported for this type : '.$type.'.');
					}
				break;
			case 'datetime':
				switch($filter)
					{
					case 'datetime':
						if(self::isDatetime($value))
							return $value;
						break;
					default:
						throw new Exception('self -> filterValue : the filter "'.$filter.'" is not supported for this type : '.$type.'.');
					}
				break;
			case 'time':
				switch($filter)
					{
					case 'time':
						if(self::isTime($value))
							return $value;
						break;
					default:
						throw new Exception('self -> filterValue : the filter "'.$filter.'" is not supported for this type : '.$type.'.');
					}
				break;
			case 'email':
				switch($filter)
					{
					case 'mail':
						if(self::isMail($value))
							return $value;
						break;
					default:
						throw new Exception('self -> filterValue : the filter "'.$filter.'" is not supported for this type : '.$type.'.');
					}
				break;
			case 'tel':
				switch($filter)
					{
					case 'phone':
						if(self::isPhone($value))
							return $value;
						break;
					default:
						throw new Exception('self -> filterValue : the filter "'.$filter.'" is not supported for this type : '.$type.'.');
					}
				break;
			case 'text':
				switch($filter)
					{
					case '':
					case 'parameter':
						if(self::isParameter($value))
							return $value;
						break;
					case 'iparameter':
						if(self::isIParameter($value))
							return $value;
						break;
					case 'ascii':
						if(self::isAscii($value))
							return $value;
						break;
					case 'b64':
						if(self::isB64($value))
							return $value;
						break;
					case 'httpuri':
						if(self::isHttpuri($value))
							return $value;
						break;
					case 'uri':
						if(self::isUri($value))
							return $value;
						break;
					case 'cdata':
						return self::filterAsCdata($value);
						break;
					case 'nbpcdata':
						return self::filterAsNbpcdata($value);
						break;
					case 'pcdata':
						return self::filterAsPcdata($value);
						break;
					case 'bbcdata':
						return self::filterAsBbcdata($value);
						break;
					case 'bbnbpcdata':
						return self::filterAsBbnbpcdata($value);
						break;
					case 'bbpcdata':
						return self::filterAsBbpcdata($value);
						break;
					default:
						throw new Exception('self -> filterValue : the filter "'.$filter.'" is not supported for this type : '.$type.'.');
					}
				break;
			case 'file':
				switch($filter)
					{
					case 'datauri':
						if(self::isDatauri($value))
							return $value;
						break;
					default:
						throw new Exception('self -> filterValue : the filter "'.$filter.'" is not supported for this type : '.$type.'.');
					}
				break;
			default:
				throw new Exception('self -> filterValue : the type "'.$type.'" is not supported.');
			}
		return null;
		}

	// Xhtml inputs
	public static function filterAsCdata($string)
		{
		// Decoding entities
		$string=html_entity_decode($string,ENT_QUOTES|(defined('ENT_HTML401')?constant('ENT_HTML401'):0),'UTF-8');
		// XML 1.0 Specification entities conversion
		$string = str_replace('&', '&amp;', $string); // Must be the 1rst
		$string = str_replace('<', '&lt;', $string);
		$string = str_replace('>', '&gt;', $string);
		$string=self::filterAsInlinepcdata($string);
		if(ini_get('magic_quotes_gpc'))
			{
			$string = str_replace('\\"', '&#034;', $string);
			}
		else
			{
			$string = str_replace('"', '&#034;', $string);
			}
		return $string;
		}

	public static function filterAsInlinepcdata($string)
		{
		$string=self::filterAsNbpcdata($string);
		$string = str_replace("\n", ' ', $string);
		$string = str_replace("\r", '', $string);
		$string = str_replace("\t", '', $string);
		return $string;
		}

	public static function filterAsNbpcdata($string)
		{
		$string=trim($string);
		// Converting template characters to entities
		$string = str_replace('#', '&#35;', $string);
		$string = str_replace('{', '&#123;', $string);
		$string = str_replace('}', '&#125;', $string);
		$string = str_replace('%', '&#37;', $string);
		$string = str_replace('@', '&#64;', $string);
		// SQL Injection filter
		if(ini_get('magic_quotes_gpc'))
			{
			$string = str_replace('\\\'', '&#039;', $string); // Could be &#8217; in paragraphs ?
			}
		else
			{
			$string = str_replace('\'', '&#039;', $string);
			$string = str_replace('"', '\\"', $string);
			}
		// French special conversion
		$string = str_replace(' :', '&#0160;:', $string);
		$string = str_replace(' !', '&#0160;!', $string); // Should be &#8201; but not recognized yet by browsers...
		$string = str_replace(' ?', '&#0160;?', $string); // Should be &#8201; but not recognized yet by browsers...
		return $string;
		}

	public static function filterAsPcdata($string)
		{
		$string=self::filterAsNbpcdata($string);
		return $string;
		}

	public static function pcdata2Cdata($string)
		{
		$string=strip_tags($string);
		$string=html_entity_decode($string,ENT_QUOTES|(defined('ENT_HTML401')?constant('ENT_HTML401'):0),'UTF-8');
		$string=self::filterAsCdata($string);
		return $string;
		}

	// XBBCode inputs
	public static function filterAsBbcdata($string)
		{
		$string=self::filterAsCdata($string);
		// Removing blank characters
		$string = preg_replace('/(\r?\n)/i', ' ', $string);
		$string = preg_replace('/ [ ]+/', ' ', $string);
		return $string;
		}

	public static function filterAsBbpcdata($string)
		{
		$string=self::filterAsBbcdata($string);
		return $string;
		}

	public static function filterAsBbnbpcdata($string)
		{
		$string=self::filterAsBbcdata($string);
		return $string;
		}

	// String inputs
	public static function isB64($string)
		{
		if(preg_match('%^[a-zA-Z0-9/+]*={0,2}$%',$string))
			return true;
		return false;
		}

	public static function filterAsB64($string)
		{
		if(self::isB64($string))
			return $string;
		return '';
		}

	public static function isIAscii($string)
		{
		if(self::filterAsIAscii($string)==$string)
			return true;
		return false;
		}

	public static function filterAsIAscii($string)
		{
		$string=trim($string);
		$string=preg_replace('/[' . utf8_encode('√¡ƒ¬¿') . ']/u', 'A', $string);
		$string=preg_replace('/[' . utf8_encode('·‰‚‡„Â') . ']/u', 'a', $string);
		$string=preg_replace('/[' . utf8_encode('«') . ']/u', 'C', $string);
		$string=preg_replace('/[' . utf8_encode('Á') . ']/u', 'c', $string);
		$string=preg_replace('/[' . utf8_encode('–') . ']/u', 'D', $string);
		$string=preg_replace('/[' . utf8_encode('') . ']/u', 'd', $string);
		$string=preg_replace('/[' . utf8_encode('…»À ') . ']/u', 'E', $string);
		$string=preg_replace('/[' . utf8_encode('ÈËÎÍ') . ']/u', 'e', $string);
		$string=preg_replace('/[' . utf8_encode('œŒÃÕ') . ']/u', 'I', $string);
		$string=preg_replace('/[' . utf8_encode('ÔÓÏÌ') . ']/u', 'i', $string);
		$string=preg_replace('/[' . utf8_encode('—') . ']/u', 'N', $string);
		$string=preg_replace('/[' . utf8_encode('Ò') . ']/u', 'n', $string);
		$string=preg_replace('/[' . utf8_encode('’÷‘“”ÿ') . ']/u', 'O', $string);
		$string=preg_replace('/[' . utf8_encode('ˆÙÚÛı¯') . ']/u', 'o', $string);
		$string=preg_replace('/[' . utf8_encode('‹€Ÿ⁄') . ']/u', 'U', $string);
		$string=preg_replace('/[' . utf8_encode('¸˚˘˙') . ']/u', 'u', $string);
		$string=preg_replace('/[' . utf8_encode('ü›') . ']/u', 'Y', $string);
		$string=preg_replace('/[' . utf8_encode('ˇ˝') . ']/u', 'y', $string);
		$string=preg_replace('/[' . utf8_encode('∆') . ']/u', 'AE', $string);
		$string=preg_replace('/[' . utf8_encode('Ê') . ']/u', 'ae', $string);
		$string=preg_replace('/[' . utf8_encode('å') . ']/u', 'OE', $string);
		$string=preg_replace('/[' . utf8_encode('ú') . ']/u', 'oe', $string);
		$string=preg_replace('/[' . utf8_encode('ﬂ') . ']/u', 'ss', $string);
		$string=preg_replace('/[^a-z0-9_:\/\.\-\=]/i', '_', $string);
		return preg_replace('/_[_]*/', '_', $string);
		}

	public static function isAscii($string)
		{
		if(self::filterAsAscii($string)==$string)
			return true;
		return false;
		}

	public static function filterAsAscii($string)
		{
		return strtolower(self::filterAsIAscii($string));
		}

	public static function isParameter($string)
		{
		if(self::filterAsParameter($string)==$string)
			return true;
		return false;
		}

	public static function filterAsParameter($string)
		{
		$string=self::filterAsIParameter($string);
		$string=preg_replace('/[^a-z0-9_]/', '_', $string);
		$string=preg_replace('/_[_]*/', '_', $string);
		if($string!='_')
			return $string;
		return '';
		}

	public static function isIParameter($string)
		{
		if(self::filterAsIParameter($string)==$string)
			return true;
		return false;
		}

	public static function filterAsIParameter($string)
		{
		$string=self::filterAsIAscii($string);
		$string=preg_replace('/[^a-z0-9_]/i', '_', $string);
		$string=preg_replace('/_[_]*/', '_', $string);
		if($string!='_')
			return $string;
		return '';
		}

	// Date/time inputs
	public static function isDate($string)
		{
		if(preg_match('/^([0-9]{2,4})\-([0-9]{1,2})\-([0-9]{1,2})$/',$string,$matches)
			&&checkdate($matches[2], $matches[3], $matches[1]))
			{
			return true;
			}
		return false;
		}
	public static function isDay($string)
		{
		if(preg_match('/^1900\-([0-9]{1,2})\-([0-9]{1,2})$/',$string,$matches)
			&&checkdate($matches[1], $matches[2], '1900'))
			{
			return true;
			}
		return false;
		}

	public static function isDatetime($string)
		{
		if(preg_match('/^([0-9]{2,4})\-([0-9]{1,2})\-([0-9]{1,2})(( |T)([0-9]{1,2})\:([0-9]{1,2})\:([0-9]{1,2}))?$/',$string,$matches)
			&&checkdate($matches[2], $matches[3], $matches[1])
			&&((!isset($matches[5]))||$matches[5]<24)
			&&((!isset($matches[6]))||$matches[6]<60)
			&&((!isset($matches[7]))||$matches[7]<60))
			{
			return true;
			}
		return false;
		}

	public static function isTime($string)
		{
		if(preg_match('/^([0-9]+)\:([0-9]{1,2})(\:([0-9]{1,2}))?$/',$string,$matches))
			{
			return true;
			}
		return false;
		}

	// Uri inputs
	public static function filterAsHttpuri($string)
		{
		if(self::isHttpuri($string))
			{
			// Replace generic data (http:// and / at the end)
			$string = preg_replace('/^(http:\/\/)(.+)([\/]?)$/','$2',$string);
			if(substr($string,strlen($string)-1)=='/')
					$string=substr($string,0,strlen($string)-1);
			return $string;
			}
		else
			return'';
		}

	public static function isHttpuri($string)
		{
		if(preg_match('/^(http:\/\/([^\/\?#]+)\/)?([^\?#]*)(\?([^#]*))?(#(.*))?$/',$string))
			return true;
		return false;
		}

	public static function filterAsRelativeuri($string)
		{
		if(self::isRelativeuri($string))
			{
			// Replace generic data (http:// and / at the end)
			$string = preg_replace('/^(http:\/\/)(.+)([\/]?)$/','$2',$string); //rawurlencode(utf8_decode()) urlencode(utf8_decode()) || rawurlencode(utf8_decode()) + Faille avec entitÈes dÈj‡ faÓtes ?-> http://www.php.net/manual/fr/function.urlencode.php#73366
			if(substr($string,strlen($string)-1)=='/')
					$string=substr($string,0,strlen($string)-1);
			return self::filterAsCData($string);
			}
		else
			return'';
		}

	public static function isRelativeuri($string)
		{
		if(!preg_match('/^(([^:\/\?#]+):\/\/([^\/\?#]+)\/)(.*)$/',$string))
			return true;
		return false;
		}

	public static function filterAsUri($string)
		{
		if(self::isUri($string))
			return $string;
		else
			return'';
		}

	public static function isUri($string)
		{
		// Accept '.' char in protocol, see http://www.iana.org/assignments/uri-schemes.html.
		// Domains accept special chars like È ‡ etc... find the entire list and find a way to comply with it !
		if(preg_match('/^(([a-z0-9\.]+):\/\/(([a-z0-9]+)(:([a-z0-9]+))?@)?([^ _\/\?#]*)(:([0-9]+))?\/)?([^\?#]*)(\?([^#]*))?(#(.*))?$/',$string))
			return true;
		return false;
		}

	// Mail inputs
	public static function isMail($string)
		{
		if(self::filterAsMail($string)!='')
			{
			return true;
			}
		else
			{
			return false;
			}
		}

	public static function filterAsMail($string)
		{
		if(eregi("^[a-z0-9]+([_.-][a-z0-9]+)*@([a-z0-9]+([.-][a-z0-9]+)*)+\\.[a-z]{2,4}$",$string))
			return $string;
		return '';
		}

	// Phone inputs
	public static function isPhone($string)
		{
		if(self::filterAsPhone($string)!='')
			return true;
		return false;
		}

	public static function filterAsPhone($string)
		{
		if(!self::$core)
			self::$core=RestServer::Instance();
		$string=str_replace(' ', '',$string);
		$lformat=self::$core->l_phone_local_format;
		if($string)
			{
			if(strpos($string,$lformat)===0)
				{
				$string=str_replace('.', '',$string);
				$string=str_replace('-', '',$string);
				$string='+'.self::$core->l_phone_local_indicator.'.'.substr($string,1);
				}
			if(preg_match('/^\+([0-9]+)\.([0-9]*)$/',$string))
				{
				return $string;
				}
			}
		return '';
		}

	// Number inputs
	public static function isInt($string)
		{
		if(preg_match('/^([\-]?)([\+]?)([0-9]+)([,\.]?)([0-9]*)$/', $string))
			{
			return true;
			}
		return false;
		}

	public static function filterAsInt($string)
		{
		if(self::isInt($string))
			return intval(preg_replace('/^(\-?)(\+?)([0-9]+)([,\.]?)([0-9]*)$/', '$1$3', $string));
		else
			return'';
		}

	public static function isFloat($string)
		{
		if(preg_match('/^([\-]?)([\+]?)([0-9]+)([,\.]?)([0-9]*)$/', $string))
			{
			return true;
			}
		return false;
		}

	public static function filterAsFloat($string)
		{
		if(self::isFloat($string))
			{
			$string=preg_replace('/^(\-?)(\+?)([0-9]+)([,\.]?)([0-9]*)$/', '$1$3.$5', $string);
			if(substr($string,strlen($string)-1)=='.')
				$string=substr($string,0,strlen($string)-1);
			return doubleval($string);
			}
		else
			return'';
		}

	// File inputs

	public static function isDatauri($string)
		{
		if(preg_match('/^data:(([a-z]+)\/([a-z]+))?;base64,([a-z0-9\+\=\/]*)$/i', $string))
			{
			return true;
			}
		return false;
		}

	}
?>
