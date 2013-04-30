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
				$template = substr($template,0,$offset)
					.substr($template,$offset+strlen($condName)+2,$endOffset-$offset-strlen($condName)-2)
					.substr($template,$endOffset+strlen($condName)+3);
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
				{
				$template = substr($template,0,$offset).$thevar->count()
					.substr($template,strlen($varName)+2);
				}
			else if($thevar instanceof stdClass)
				{
				$template = substr($template,0,$offset).'(stdClass)'
					.substr($template,strlen($varName)+2);
				}
			else if($thevar||$thevar===0||$thevar==='0')
				{
				$template = substr($template,0,$offset).$thevar
					.substr($template,$offset+strlen($varName)+2);
				}
			else
				{
				$template = substr($template,0,$offset)
					.substr($template,$offset+strlen($varName)+2);
				}
			}
		return $offset;
		}

	// Loops replacement
	static function parseLoops(&$scope,&$template)
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
				if(Varstream::get($scope,'user.candebug')&&preg_match('/\%!@'.$loopName.':([a-z0-9_\.|]+)\%(.*)\%\/!@'.$loopName.':\1\%/Usi', $loopContent,$dregs)) // XCMS Specific remove ?
					trigger_error('Malformed loop condition ('.$loopName.':'.$dregs[1].') in '.$scope->site->name.' at the document '.$scope->document->href.' ('.$scope->site->location.'/'.$_SERVER['REQUEST_URI'].')');
				$tList='';
				$itemN=0;
				foreach($thevar as $key => $value)
					{
					$tItem = $loopContent;
					if($value instanceof stdClass&&preg_match('/@@' . $loopName . ':([a-z0-9_]+)@@/Usi', $tItem, $oregs))
						{
						$value2=Varstream::get($scope,$loopName.'.'.$key.'.'.$oregs[1]);
						if($value2)
							{
							$tItem = preg_replace('/@@' . $loopName . ':' . $oregs[1] . '@@/Usi', '@' . $loopName .'.'. $key .'.'. $oregs[1] . '@' . preg_replace('/@' . $loopName . '/Us', '@' . $loopName .'.'. $key .'.'. $oregs[1], $loopContent) . '@/' . $loopName .'.'. $key .'.'. $oregs[1] . '@', $tItem);
							}
						else
							$tItem = preg_replace('/@@' . $loopName . ':' . $oregs[1] . '@@/Usi','',$tItem);
						}
					$changes2=true;
					while($changes2===true)
						{
						$changes2=false;
						while(preg_match('/@'.$loopName.':([a-z0-9_\.|]+)@/Usi', $tItem, $itemregs))
							{
							$changes2=true;
							if($itemregs[1]=='n')
								{
								$tItem = preg_replace('/@' . $loopName . ':' . $itemregs[1] . '@/Usi', $key, $tItem);
								}
							else if(($value3=Varstream::get($scope,$loopName.'.'.$key.'.'.$itemregs[1])) instanceof ArrayObject)
								{
								$tItem = preg_replace('/@' . $loopName . ':' . $itemregs[1] . '@/Usi', ''.$value3->count(), $tItem);
								}
							else if($value3||$value3==='0'||$value3===0)
								{
								$tItem = preg_replace('/@' . $loopName . ':' . $itemregs[1] . '@/Usi', ''.$value3, $tItem);
								}
							else
								{
								$tItem = preg_replace('/@' . $loopName . ':' . $itemregs[1] . '@/Usi', '', $tItem);
								}
							}
						while(preg_match('/\%@'.$loopName.':([a-z0-9!_\.|]+)\%(.*)\%\/@'.$loopName.':\1\%/Usi', $tItem, $itemregs))
							{
							$changes2=true;
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
					}
				$template = substr($template,0,$offset).$tList
					.substr($template,$endOffset+strlen($loopName)+3);
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
