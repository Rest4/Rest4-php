<?php
class xcUtilsOutput
	{
	// Localization functions
	public static function localizeNumber($number,$decimals,$decpoint='',$thsep='')
		{
		if(!$decpoint) // Backward compatibility : remove when XCMS is moved to REST
			{
			$core=RestServer::Instance();
			$decpoint=$core->getVar('l_number_dec_point');
			$thsep=$core->getVar('l_number_thousands_sep');
			}
		return number_format($number, $decimals, $decpoint, $thsep);
		}

	public static function localizeAmount($number, $decimals, $currency, $format, $decpoint='',$thsep='')
		{
		if(!$decpoint) // Backward compatibility : remove when XCMS is moved to REST
			{
			$core=RestServer::Instance();
			$decpoint=$core->getVar('l_amount_dec_point');
			$thsep=$core->getVar('l_amount_thousands_sep');
			$format=$core->getVar('l_amount_thousands_sep');
			}
		return str_ireplace('C', $currency, str_ireplace('X', number_format($number, $decimals, $decpoint, $thsep), $format));
		}

	public static function localizeDate($date,$format='',$days=null,$months=null)
		{
		if(!$format) // Backward compatibility : remove when XCMS is moved to REST
			{
			$core=RestServer::Instance();
			$format=$core->getVar('l_date_format');
			$days=$core->getVar('l_days');
			$months=$core->getVar('l_months');
			}
		else
			{
			preg_match('/^([0-9]{2,4})\-([0-9]{1,2})\-([0-9]{1,2})( ([0-9]{1,2})\:([0-9]{1,2})\:([0-9]{1,2}))?$/',$date,$matches);
			$date=date($format,mktime((isset($matches[5])?$matches[5]:0), (isset($matches[6])?$matches[6]:0), (isset($matches[7])?$matches[7]:0), $matches[2], $matches[3], $matches[1]));
			}
		if(strpos($format,'l')>=0)
			{
			foreach($days as $key => $value)
				$date=str_ireplace($key,$value,$date);
			}
		if(strpos($format,'F')>=0)
			{
			foreach($months as $key => $value)
				$date=str_ireplace($key,$value,$date);
			}
		return $date;
		}

	public static function localizeDay($date,$format='',$days=null,$months=null)
		{
		if(!$format) // Backward compatibility : remove when XCMS is moved to REST
			{
			$core=RestServer::Instance();
			$format=$core->getVar('l_day_format');
			$days=$core->getVar('l_days');
			$months=$core->getVar('l_months');
			}
		if(strpos($format,'l')>=0)
			{
			foreach($days as $key => $value)
				$date=str_ireplace($key,$value,$date);
			}
		if(strpos($format,'F')>=0)
			{
			foreach($months as $key => $value)
				$date=str_ireplace($key,$value,$date);
			}
		return $date;
		}
	
	public static function localizeLatitude($l,$format='')
		{
		if(!$format) // Backward compatibility : remove when XCMS is moved to REST
			{
			$core=RestServer::Instance();
			$format=$core->getVar('l_gps_latitude');
			}
		$d = floor($l);
		$p = ($l-$d)*60;
		$m = floor($p);
		$s = round((($p-$m)*60));
		$str = $d.'° '.$m.'\' '.$s.'\'\' '.$format;
		return $str;
		}
	
	public static function localizeLongitude($l,$format='')
		{
		if(!$format) // Backward compatibility : remove when XCMS is moved to REST
			{
			$core=RestServer::Instance();
			$format=$core->getVar('l_gps_longitude');
			}
		$d = floor($l);
		$p = ($l-$d)*60;
		$m = floor($p);
		$s = round((($p-$m)*60));
		$str = $d.'° '.$m.'\' '.$s.'\'\' '.$format;
		return $str;
		}

	public static function localizePhoneNumber($number,$iformat='',$lformat='',$lindic='',$nformat='')
		{
		if($number)
			{
			if(!$iformat) // Backward compatibility : remove when XCMS is moved to REST
				{
				$core=RestServer::Instance();
				$iformat=$core->getVar('l_phone_indicator_format');
				$lformat=$core->getVar('l_phone_local_format');
				$lindic=$core->getVar('l_phone_local_indicator');
				$nformat=$core->getVar('l_phone_number_format');
				}
			$lnumber='';
			$lindicator='';
			
			$indicator=preg_replace('/\+([0-9]+)\.(?:.*)/','$1',$number);
			$number=preg_replace('/\+(?:[0-9]+)\.([0-9]+)/','$1',$number);
			if($indicator==$lindic)
				{
				$lindicator=$lformat;
				}
			else
				{
				$j=0;
				for($i=0; $i<strlen($iformat); $i++)
					{
					if($iformat[$i]=='X')
						{
						if($j<strlen($indicator))
							$lindicator.=$indicator[$j];
						$j++;
						}
					else
						{
						$lindicator.=$iformat[$i];
						}
					}
				}
			$j=0;
			for($i=0; $i<strlen($nformat); $i++)
				{
				if($nformat[$i]=='X')
					{
					if($j<strlen($number))
						$lnumber.=$number[$j];
					$j++;
					}
				else
					{
					$lnumber.=$nformat[$i];
					}
				}
			return $lindicator.$lnumber;
			}
		return '';
		}

	// Conversion functions
	public static function xbbcode2Xhtml($string, $all=false, $cleanup=false)
		{
		$string = str_replace('[[', '&#91;', $string);
		$string = str_replace(']]', '&#93;', $string);
		$tags = array('span', 'kbd', 'var', 'del', 'ins', 'div', 'strong', 'em', 'dfn', 'cite', 'q', 'blockquote', 'p', 'br', 'a', 'ol', 'ul', 'li', 'abbr', 'acronym', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'pre', 'address', 'img', 'tr', 'th', 'td', 'table', 'caption', 'thead', 'tfoot', 'tbody', 'dl', 'dd', 'dt', 'map', 'area', 'code', 'samp', 'sub', 'sup');
		array_push($tags, 'script', 'object', 'param'); // comment it for more security
		$attributes = array('class', 'id', 'name', 'dir', 'title', 'lang', 'style', 'href', 'hreflang', 'rel', 'rev', 'tabindex', 'type', 'accesskey', 'charset', 'datetime', 'cite', 'alt', 'longdesc', 'usemap', 'src', 'coords', 'shape', 'nohref', 'summary', 'scope');
		array_push($attributes, 'onclick', 'ondblclick', 'onkeydown', 'onkeypress', 'onkeyup', 'onload', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onunload', 'onblur', 'onfocus', 'defer', 'value', 'data', 'width', 'height'); // peuvent être commentés (-sécurité)
		$tagChars='abcdefghijklmnopqrstuvwxyz0123456789';
		$attributeChars='abcdefghijklmnopqrstuvwxyz';
		$curTextNode='';
		$curHtml='';
		//$debug='';
		// String loop
		for($i=0; $i<strlen($string); $i++)
			{
			$curTag='';
			$curTagBegin=-1;
			$opClTag=false;
			$curAtt='';
			$curAttBegin=-1;
			$curAttVal='';
			$curAttValBegin=-1;
			$lastSpaceInAtt=-1;
			if($string[$i]=='[')
				{
				if($curTextNode)
					{
		//			$debug.='<p>Textnode:"'.$curTextNode.'"</p>';
					$curHtml.=$curTextNode;
					}
				$curTextNode='';
				$curTagBegin=$i;
				if($string[$i+1]=='/')
					{
					// Tag close loop
					for($i=$i+2; $i<strlen($string); $i++)
						{
						if(strpos($tagChars,$string[$i])!==false)
							$curTag.=$string[$i];
						else
							break;
						}
					if(!($curTag&&($all||in_array($curTag,$tags))))
						{
		//				$debug.='<p>Bad tag close:'.$curTag.'</p>';
						$curTextNode.='[/'.$curTag.$string[$i];
						continue;
						}
					else if($i>=strlen($string)||$string[$i]!=']')
						{
		//				$debug.='<p>Bad close of:'.$curTag.'</p>';
						$curTextNode.='[/'.$curTag.$string[$i];
						continue;
						}
					else
						{
		//				$debug.='<p>Close:'.$curTag.'</p>';
						}
					$curHtml.='</'.$curTag.'>';
					}
				else
					{
					$curTextNode='';
					// Tag open loop
					for($i=$i+1; $i<strlen($string); $i++)
						{
						if(strpos($tagChars,$string[$i])!==false)
							$curTag.=$string[$i];
						else
							break;
						}
					if($curTag&&($all||in_array($curTag,$tags)))
						{
		//				$debug.='<p>Open:'.$curTag;
						$curHtml.='<'.$curTag;
						if($i+1<strlen($string)&&$string[$i]=='/'&&$string[$i+1]==']')
							{
							$opClTag=true;
							}
						else if($i+2<strlen($string)&&$string[$i]==' '&&$string[$i+1]=='/'&&$string[$i+2]==']')
							{
							$opClTag=true;
							}
						else if($i+1<strlen($string)&&$string[$i]==' '&&strpos($attributeChars,$string[$i+1])!==false)
							{
							while($string[$i]==' ')
								{
								$curAttBegin=$i+1;
								// Attribute name loop
								for($i=$i+1; $i<strlen($string); $i++)
									{
									if(strpos($attributeChars,$string[$i])!==false)
										{
										$curAtt.=$string[$i];
										continue;
										}
									else
										{
										if(in_array($curAtt,$attributes))
											{
		//									$debug.='<br />Att:'.$curAtt;
											$curHtml.=' '.$curAtt.'="';
											$curAttValBegin=$i+1;
											}
										else
											{
		//									$debug.='<br />Bad att:'.$curAtt;
											$curAtt='';
											$curAttValBegin=$i+1;
											}
										break;
										}
									}
								// Attribute value loop
								if($string[$i]=='=')
									{
									for($i=$i+1; $i<strlen($string); $i++)
										{
										if($string[$i]==']')
											{
											break;
											}
										else if($i<strlen($string)&&$string[$i]=='/'&&$string[$i+1]==']'&&$curAtt!='href'&&$curAtt!='src'&&$curAtt!='cite')
											{
											$opClTag=true;
											break;
											}
										else if($i<strlen($string)&&$string[$i]==' '&&$string[$i+1]=='/'&&$string[$i+2]==']')
											{
											$opClTag=true;
											break;
											}
										else if($string[$i]!=' ')
											{
											if($lastSpaceInAtt>=0)
												{
												if($string[$i]=='=')
													{
													$i=$lastSpaceInAtt;
													break;
													}
												else if(strpos($attributeChars,$string[$i])===false)
													$lastSpaceInAtt=-1;
												}
											continue;
											}
										else
											{
											$lastSpaceInAtt=$i;
											}
										}
									$curAttVal.=substr($string,$curAttValBegin,$i-$curAttValBegin);
									}
								else
									{
									if($i<strlen($string)&&$string[$i]=='/'&&$string[$i+1]==']')
										{
										$opClTag=true;
										}
									else if($i<strlen($string)&&$string[$i]==' '&&$string[$i+1]=='/'&&$string[$i+2]==']')
										{
										$opClTag=true;
										}
									}
								if($curAtt)
									{
									$curHtml.=$curAttVal.'"';
		//							$debug.='<br />Attval:'.$curAttVal;
									}
								$curAtt='';
								$curAttBegin=-1;
								$curAttVal='';
								$curAttValBegin=-1;
								$lastSpaceInAtt=-1;
								if($opClTag&&$string[$i]==' ')
									{
									$i++;
									}
								}
							}
						else if($string[$i]!=']')
							{
		//					$debug.='<p>Bad tag:"'.$curTag.'"</p>';
							$curHtml=substr($curHtml,0,strlen($curHtml)-strlen($curTag)-1);
							$curTextNode.='['.$curTag.$string[$i];
							continue;
							}
						while($opClTag&&$string[$i]!=']')
							$i++;
						if($opClTag)
							{
							$curHtml.=' /';
							$opClTag=false;
							}
						$curHtml.='>';
		//				$debug.='</p>';
						}
					else
						{
		//				$debug.='<p>Bad tag2:"'.$curTag.'"</p>';
						$curTextNode.='['.$curTag.$string[$i];
						continue;
						}
					}
				}
			else
				{
				$curTextNode.=$string[$i];
				}
			}
		if($curTextNode)
			{
		//	$debug.='<p>Textnode:"'.$curTextNode.'"</p>';
			$curHtml.=$curTextNode;
			}
		if($cleanup)
			{	// Kohnshita : Nettoyer
			$curHtml=preg_replace('/\[(?:[^\]]+)\]/i', '', $curHtml);
			}
	//	if((strpos($curHtml,'[')&&strpos($curHtml,']')))
	//		@mail('contact@elitwork.com','xbbcode error', '<h2>'.$_SERVER['REQUEST_URI'].'</h2>'.$debug.'<h2>HTML</h2>'.$curHtml);
		return $curHtml;
		}

	public static function xbbcodenb2Xbbcode($string)
		{
		$string = '[p]' . $string . '[/p]';
		$string = preg_replace('/(\r?\n)(\r?\n)([\r\n]*)/i', '[/p][p]', $string);
		$string = preg_replace('/(\r?\n)/i', '[br /]\n', $string);
		return $string;
		}

	public static function xbbcode2Text($string)
		{
		$string = preg_replace('/\[(?:[^\]]+)\]/i', '', $string);
		return $string;
		}

	public static function xhtmlnb2Xhtml($string)
		{
		$string = '<p>' . $string . '</p>';
		$string = preg_replace('/(\r?\n)(\r?\n)([\r\n]*)/i', '</p><p>', $string);
		$string = preg_replace('/(\r?\n)/i', '<br />', $string);
		return $string;
		}

	public static function xhtml2Text($string)
		{
		$string = preg_replace('/<(?:[^\]]+)>/i', '', $string);
		// French special conversion
		$string = str_replace('&#0160;:', ' :', $string);
		$string = str_replace('&#0160;!', ' !', $string); // Should be &#8201; but not recognized yet...
		$string = str_replace('&#0160;?', ' ?', $string); // Should be &#8201; but not recognized yet...
		// SQL Injection filter
		$string = str_replace('&quot;', '"', $string);
		$string = str_replace('&#039;', '\'', $string);
		// Converting template characters to entities
		$string = str_replace('&#35;', '#', $string);
		$string = str_replace('&#123;', '{', $string);
		$string = str_replace('&#125;', '}', $string);
		$string = str_replace('&#37;', '%', $string);
		$string = str_replace('&#64;', '@', $string);
		// XML 1.0 Specification entities conversion
		$string = str_replace('&lt;', '<', $string);
		$string = str_replace('&gt;', '>', $string);
		$string = str_replace('&amp;', '&', $string); // Must be the last
		$string=trim($string);
		return $string;
		}

	// Help functions
	public static function urlEncode($string) 
		{
		// Should use urlencode() + template caracters not converted conversion : have to study more...
		// Converting template characters to entities
		$string = str_replace('%', '%25', $string);
		$string = str_replace('#', '%23', $string);
		$string = str_replace('{', '%7B', $string);
		$string = str_replace('}', '%7D;', $string);
		$string = str_replace('@', '%40', $string);
		// XML 1.0 Specification entities conversion
		$string = str_replace('&', '%26', $string);
		$string = str_replace('<', '%3C', $string);
		$string = str_replace('>', '%3E', $string);
		// SQL Injection filter + Quotes escape for XML attributes
		if(ini_get('magic_quotes_gpc'))
			{
			$string = str_replace('\\"', '%22', $string);
			$string = str_replace('\\\'', '%60', $string); // Could be &#8217; in paragraphs ?
			}
		$string = str_replace('"', '%22', $string);
		$string = str_replace('\'', '%60', $string);
		// Converting template characters to entities
		$string = str_replace('%', '&#37;', $string);
		return $string;
		}
	public static function getOld($date) 
		{
		if((!$date)||($date=='0000-00-00')) { return 0; }
		else
			{
			ereg("^([0-9]{2,4})-([0-9]{1,2})-([0-9]{1,2})$",$date,$MDate);
			if ($MDate[1] < 1970)
				{
				$Plus = 1970 - $MDate[1];
				$Annee = 1970;
				}
			else
				{
				$Plus = 0;
				$Annee = $MDate[1];
				}
			$dateUtilisateur = gmmktime( 0 , 0 , 0 , $MDate[2] , $MDate[3] , $Annee );
			$datePrec = gmmktime( 0 , 0 , 0 , date( 'm' ) , date( 'd' ) , date( 'Y' ) );
			$Age = date( 'Y' , $datePrec - $dateUtilisateur ) - 1970;
			return $Age+$Plus;
			}
		}

	public static function showKeywords($string, $search)
		{
		if($search)
			{
			$keywords = explode(' ', $search);
			foreach($keywords as $V)
				{
				$string=preg_replace('/' . $V . '/i', '<em class="keyword">' . $V . '</em>', $string);
				}
			}
		return $string;
		}
	}
?>