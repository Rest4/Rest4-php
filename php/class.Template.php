<?php
// Set of templating functions
class Template
	{
	// Detect templates special chars to find a safe offset
	static function getSafeOffset(&$scope,&$template)
		{
		$offset=strlen($template);
		if(($newOffset=strpos($template,'%'))!==false
			&&($offset==-1||$newOffset<$offset))
				$offset=$newOffset;
		if(($newOffset=strpos($template,'@'))!==false
			&&($offset==-1||$newOffset<$offset))
				$offset=$newOffset;
		if(($newOffset=strpos($template,'#'))!==false
			&&($offset==-1||$newOffset<$offset))
				$offset=$newOffset;
		if(($newOffset=strpos($template,'{'))!==false
			&&($offset==-1||$newOffset<$offset))
				$offset=$newOffset;
		return $offset;
		}

	// Replace includes
	static function parseIncludes(&$scope,&$template)
		{
		$offset=-1;
		while(preg_match('/#([a-z0-9_\.]+)#/i', $template, $regs, PREG_OFFSET_CAPTURE))
			{
			$offset=$regs[0][1];
			$includeName=$loopName[0];
			$thevar=Varstream::get($scope,$includeName);
			// Templates are rarely used more than 1 time we're
			// replacing them with the help of the caught offset
			if(isset($thevar)&&$thevar&&$thevar instanceof RestResponse)
				{
				$template = substr($template,0,$offset).$thevar->getContents()
					.substr($template,$offset+strlen($includeName)+2);
				}
			else
				{
				$template = substr($template,0,$offset)
					.substr($template,$offset+strlen($includeName)+2);
				}
			}
		return $offset;
		}

	// Conditions replacement
	static function parseConditions(&$scope,&$template)
		{
		$offset=-1;
		while(preg_match('/\%([a-z0-9!_\.|]+)\%(?:[^%]*)(\%\/\1\%)/si', $template, $regs,PREG_OFFSET_CAPTURE))
			{
			$offset=$regs[0][1];
			$condName=$regs[1][0];
			$endOffset=$regs[2][1];
			$conds=explode('|',$condName);
			$result=false;
			for($i=sizeof($conds)-1; $i>=0; $i--)
				{
				$inverse=false;
				if(strpos($conds[$i],'!')===0)
					{
					$inverse=true;
					$conds[$i]=substr($conds[$i],1);
					}
				$thevar=Varstream::get($scope,$conds[$i]);
				if(((!$inverse)&&isset($thevar)&&$thevar&&((!$thevar instanceof ArrayObject)
					||$thevar->count()))||((!(isset($thevar)&&$thevar
					&&((!$thevar instanceof ArrayObject)||$thevar->count())))&&$inverse))
					$result=true;
				}
			$replace=str_replace('|','\|',$condName);
			if($result)
				{
				// If cond is complicated, it should not appear twice
				if(strlen($condName)>20)
					{
					$template = substr($template,0,$offset)
						.substr($template,$offset+strlen($condName)+2,$endOffset-$offset-strlen($condName)-2)
						.substr($template,$endOffset+strlen($condName)+3);
					}
				// If it's short, it's probably often used
				else
					{
					$template = str_replace('%' . $condName . '%', '', $template);
					$template = str_replace('%/' . $condName . '%', '', $template);
					}
				}
			else
				{
				$template = substr($template,0,$offset)
					.substr($template,$endOffset+strlen($condName)+3);
				}
			}
		return $offset;
		}

	// Vars replacement
	static function parseVars(&$scope,&$template)
		{
		$offset=-1;
		while(preg_match('/\{([a-z0-9_\.]+)\}/i', $template, $regs,PREG_OFFSET_CAPTURE))
			{
			$offset=$regs[0][1];
			$varName=$regs[1][0];
			$thevar=Varstream::get($scope,$varName);
			if($thevar instanceof ArrayObject)
				$template = str_replace('{' . $varName . '}', $thevar->count(), $template);
			else if($thevar instanceof stdClass)
				$template = str_replace('{' . $varName . '}', '(stdClass)', $template);
			else if($thevar||$thevar===0||$thevar==='0')
				$template = str_replace('{' . $varName . '}', $thevar, $template);
			else
				$template = str_replace('{' . $varName . '}', '', $template);
			}
		return $offset;
		}

	// Loops replacement
	static $curLoopIndex=0;
	static function parseLoops(&$scope,&$template, $bufferSize)
		{
		$offset=-1;
		if(preg_match('/@((?:[a-z0-9_])(?:[a-z0-9_\.]+)(?:[a-z0-9_]))@(.*)(@\/\1@)/Usi', $template, $regs, PREG_OFFSET_CAPTURE))
			{
			$offset=$regs[0][1];
			$loopName=$regs[1][0];
			$loopContent=$regs[2][0];
			$endOffset=$regs[3][1];
			$thevar=Varstream::get($scope,$loopName);
			if(isset($thevar)&&$thevar)
				{
				$tList='';
				$itemN=0;
				// Looping throught each entries
				foreach($thevar as $key => $value)
					{
					if($itemN!=self::$curLoopIndex)
						continue;
					// Init the entry content to the loopcontent
					$tItem = $loopContent;
					// Recursion: substitute recursion declarations by adapted loopcontent
					if($value instanceof stdClass&&preg_match('/@@' . $loopName . ':([a-z0-9_]+)@@/Usi', $tItem, $oregs))
						{
						$value2=Varstream::get($scope,$loopName.'.'.$key.'.'.$oregs[1]);
						if($value2)
							{
							$tItem = str_replace('@@' . $loopName . ':' . $oregs[1] . '@@',
								'@' . $loopName .'.'. $key .'.'. $oregs[1] . '@'
								.str_replace('@' . $loopName . '', '@' . $loopName .'.'. $key .'.'. $oregs[1], $loopContent)
								.'@/' . $loopName .'.'. $key .'.'. $oregs[1] . '@',
								$tItem);
							}
						else
							$tItem = str_replace('@@' . $loopName . ':' . $oregs[1] . '@@','',$tItem);
						}
					// Find vars and conditions
					$changes=true;
					while($changes===true)
						{
						$changes=false;
						// Replace vars
						while(preg_match('/@'.$loopName.':([a-z0-9_\.|]+)@/Usi', $tItem, $itemregs))
							{
							$changes=true;
							if($itemregs[1]=='n')
								{
								$tItem = str_replace('@' . $loopName . ':' . $itemregs[1] . '@', $key, $tItem);
								}
							else if(($value3=Varstream::get($scope,$loopName.'.'.$key.'.'.$itemregs[1])) instanceof ArrayObject)
								{
								$tItem = str_replace('@' . $loopName . ':' . $itemregs[1] . '@', ''.$value3->count(), $tItem);
								}
							else if($value3||$value3==='0'||$value3===0)
								{
								$tItem = str_replace('@' . $loopName . ':' . $itemregs[1] . '@', ''.$value3, $tItem);
								}
							else
								{
								$tItem = str_replace('@' . $loopName . ':' . $itemregs[1] . '@', '', $tItem);
								}
							}
						// Replace conditions
						while(preg_match('/\%@'.$loopName.':([a-z0-9!_\.|]+)\%(.*)\%\/@'.$loopName.':\1\%/Usi', $tItem, $itemregs))
							{
							$changes=true;
							$conds=explode('|',$itemregs[1]);
							$result=false;
							for($i=sizeof($conds)-1; $i>=0; $i--)
								{
								$inverse=false;
								if(strpos($conds[$i],'!')===0)
									{
									$inverse=true;
									$conds[$i]=substr($conds[$i],1);
									}
								if($conds[$i]=='1st')
									{
									$thevar2=false;
									if($itemN==0)
										{
										$thevar2=true;
										}
									}
								else if($conds[$i]=='last')
									{
									$thevar2=false;
									if($itemN==$thevar->count()-1)
										{
										$thevar2=true;
										}
									}
								else if(ctype_digit($conds[$i]))
									{
									$thevar2=false;
									if($itemN%$conds[$i]!==0)
										{
										$thevar2=true;
										}
									}
								else if(ctype_digit($conds[$i][0])&&preg_match('/^([0-9]+)on([0-9]+)$/', $conds[$i], $condMatches))
									{
									$thevar2=false;
									if(($itemN+1)%$condMatches[2]==$condMatches[1])
										{
										$thevar2=true;
										}
									}
								else if(strpos($conds[$i],'laston')===0&&preg_match('/^laston([0-9]+)$/', $conds[$i], $condMatches))
									{
									$thevar2=false;
									if(($itemN+1)%$condMatches[1]==$condMatches[1]-1)
										{
										$thevar2=true;
										}
									}
								else
									$thevar2=Varstream::get($scope,$loopName.'.'.$key.'.'.$conds[$i]);
								if(((!$inverse)&&isset($thevar2)&&$thevar2)||((!(isset($thevar2)&&$thevar2))&&$inverse))
									$result=true;
								}
							$replace=str_replace('.','\.',str_replace('|','\|',$itemregs[1]));
							if($result)
								{
								$tItem = str_replace('%@' . $loopName . ':' . $itemregs[1] . '%', '', $tItem);
								$tItem = str_replace('%/@' . $loopName . ':' . $itemregs[1] . '%', '', $tItem);
								}
							else
								{
								$tItem = preg_replace('/\%@' . $loopName . ':' . $replace . '\%(.*)\%\/@' . $loopName . ':' . $replace . '\%/Us', '', $tItem);
								}
							}
						}
					$tList .= $tItem;
					$itemN++;
					self::$curLoopIndex++;
					// Exists the loop and save the index if buffer size exceeded
					if(sizeof($tList)>$bufferSize)
						{
						break;
						}
					}
				// Loops ended
				if(count((array)$thevar)>=$itemN)
					{
					self::$curLoopIndex=0;
					$template = substr($template,0,$offset).$tList
						.substr($template,$endOffset+strlen($loopName)+3);
					}
				else
					{
					$template = substr($template,0,$offset).$tList
						.substr($template,$offset);
					}
				}
			else
				{
				$template = substr($template,0,$offset)
					.substr($template,$endOffset+strlen($loopName)+3);
				}
			}
		return $offset;
		}
	}
