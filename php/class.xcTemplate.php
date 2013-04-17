<?php
class xcTemplate
	{
	private $core;
	private $content;

	// A bit of historical mess, maybe should make a RestResource for templates parsing ?
	// Probably a bad idea cause we'll have to send template vars
	// Should use data driven templates in fact
	function __construct($template,$core)
		{
		$this->core=$core;
		if(isset($template)&&$template&&$template instanceof xcIntPrintableObject)
			{
			$this->content=$template->getContents();
			}
		else if($template)
			$this->content=$template;
		}
	function getContents()
		{
		$this->parse();
		return $this->content;
		}

	// Parse for replacements till none left
	function parse()
		{
		while($this->parseConditions()||$this->parseLoops()||$this->parseIncludes()||$this->parseVars()||$this->parseConditions())
			continue;
		switch(preg_last_error())
			{
			case PREG_BACKTRACK_LIMIT_ERROR;
				trigger_error('Backtrack limit was exhausted ('.$regs[1].') in '.$this->core->site->name.' at the document '.$this->core->document->href.' ('.$this->core->site->location.'/'.$_SERVER['REQUEST_URI'].')');
			case PREG_RECURSION_LIMIT_ERROR;
				trigger_error('Recursion limit was exhausted ('.$regs[1].') in '.$this->core->site->name.' at the document '.$this->core->document->href.' ('.$this->core->site->location.'/'.$_SERVER['REQUEST_URI'].')');
			}
		if(preg_match('/@([\/]?)([a-z0-9_\.!|]+)@/i', $this->content,$dregs))
			trigger_error('A loop ('.$dregs[2].') has not been interpreted in '.$this->core->site->name.' at the document '.$this->core->document->href.' ('.$this->core->site->location.'/'.$_SERVER['REQUEST_URI'].')');
		if(preg_match('/\%([\/]?)([a-z0-9_\.!|]+)\%/i', $this->content,$dregs))
			trigger_error('A condition ('.$dregs[2].') has not been interpreted in '.$this->core->site->name.' at the document '.$this->core->document->href.' ('.$this->core->site->location.'/'.$_SERVER['REQUEST_URI'].')');
		}

	// Replace includes
	function parseIncludes()
		{
		$changes=false;
		while(preg_match('/#([a-z0-9_\.]+)#/i', $this->content, $regs))
			{
			$thevar=Varstream::get($this->core,$regs[1]);
			if(isset($thevar)&&$thevar&&$thevar instanceof xcIntPrintableObject)
				{
				$this->content = str_replace('#' . $regs[1] . '#', $thevar->getContents(), $this->content);
				}
			else
				{
				$this->content = str_replace('#' . $regs[1] . '#', '', $this->content);
				}
			$changes=true;
			}
		return $changes;
		}

	// Loops replacement
	function parseLoops()
		{
		$changes=false;
		while(preg_match('/@((?:[a-z0-9_])(?:[a-z0-9_\.]+)(?:[a-z0-9_]))@(.*)@\/\1@/Usi', $this->content, $regs))
			{
			$thevar=Varstream::get($this->core,$regs[1]);
			if(isset($thevar)&&$thevar)
				{
				if(Varstream::get($this->core,'user.candebug')&&preg_match('/\%!@'.$regs[1].':([a-z0-9_\.|]+)\%(.*)\%\/!@'.$regs[1].':\1\%/Usi', $regs[2],$dregs)) // XCMS Specific remove ?
					trigger_error('Malformed loop condition ('.$regs[1].':'.$dregs[1].') in '.$this->core->site->name.' at the document '.$this->core->document->href.' ('.$this->core->site->location.'/'.$_SERVER['REQUEST_URI'].')');
				$tList='';
				$itemN=0;
				foreach($thevar as $key => $value)
					{
					$tItem = $regs[2];
					if($value instanceof stdClass&&preg_match('/@@' . $regs[1] . ':([a-z0-9_]+)@@/Usi', $tItem, $oregs))
						{
						$value2=Varstream::get($this->core,$regs[1].'.'.$key.'.'.$oregs[1]);
						if($value2)
							{
							$tItem = preg_replace('/@@' . $regs[1] . ':' . $oregs[1] . '@@/Usi', '@' . $regs[1] .'.'. $key .'.'. $oregs[1] . '@' . preg_replace('/@' . $regs[1] . '/Us', '@' . $regs[1] .'.'. $key .'.'. $oregs[1], $regs[2]) . '@/' . $regs[1] .'.'. $key .'.'. $oregs[1] . '@', $tItem);
							}
						else
							$tItem = preg_replace('/@@' . $regs[1] . ':' . $oregs[1] . '@@/Usi','',$tItem);
						}
					$changes2=true;
					while($changes2===true)
						{
						$changes2=false;
						while(preg_match('/@'.$regs[1].':([a-z0-9_\.|]+)@/Usi', $tItem, $itemregs))
							{
							$changes2=true;
							if($itemregs[1]=='n')
								{
								$tItem = preg_replace('/@' . $regs[1] . ':' . $itemregs[1] . '@/Usi', $key, $tItem);
								}
							else if(($value3=Varstream::get($this->core,$regs[1].'.'.$key.'.'.$itemregs[1])) instanceof ArrayObject)
								{
								$tItem = preg_replace('/@' . $regs[1] . ':' . $itemregs[1] . '@/Usi', ''.$value3->count(), $tItem);
								}
							else if($value3||$value3==='0'||$value3===0)
								{
								$tItem = preg_replace('/@' . $regs[1] . ':' . $itemregs[1] . '@/Usi', ''.$value3, $tItem);
								}
							else
								{
								$tItem = preg_replace('/@' . $regs[1] . ':' . $itemregs[1] . '@/Usi', '', $tItem);
								}
							}
						while(preg_match('/\%@'.$regs[1].':([a-z0-9!_\.|]+)\%(.*)\%\/@'.$regs[1].':\1\%/Usi', $tItem, $itemregs))
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
									$thevar2=Varstream::get($this->core,$regs[1].'.'.$key.'.'.$conds[$i]);
								if(((!$inverse)&&isset($thevar2)&&$thevar2)||((!(isset($thevar2)&&$thevar2))&&$inverse))
									$result=true;
								}
							$replace=str_replace('.','\.',str_replace('|','\|',$itemregs[1]));
							if($result)
								{
								$tItem = str_replace('%@' . $regs[1] . ':' . $itemregs[1] . '%', '', $tItem);
								$tItem = str_replace('%/@' . $regs[1] . ':' . $itemregs[1] . '%', '', $tItem);
								}
							else
								{
								$tItem = preg_replace('/\%@' . $regs[1] . ':' . $replace . '\%(.*)\%\/@' . $regs[1] . ':' . $replace . '\%/Us', '', $tItem);
								}
							}
						}
					$tList .= $tItem;
					$itemN++;
					}
				$this->content = str_replace('@' . $regs[1] . '@' . $regs[2] . '@/' . $regs[1] . '@', $tList, $this->content);
				}
			else
				{
				$this->content = str_replace('@' . $regs[1] . '@' . $regs[2] . '@/'. $regs[1] . '@', '', $this->content);
				}
			$changes=true;
			}
		return $changes;
		}

	// Vars replacement
	function parseVars()
		{
		$changes=false;
		while(preg_match('/\{([a-z0-9_\.]+)\}/i', $this->content, $regs))
			{
			$thevar=Varstream::get($this->core,$regs[1]);
			if($thevar instanceof ArrayObject)
				{
				$this->content = str_replace('{' . $regs[1] . '}', $thevar->count(), $this->content);
				}
			else if($thevar instanceof stdClass)
				{
				trigger_error('Attempted to print a DataObject in a template ('.$regs[1].') in '.$this->core->site->name.' at the document '.$this->core->document->href.' ('.$this->core->site->location.'/'.$_SERVER['REQUEST_URI'].')');
				$this->content = str_replace('{' . $regs[1] . '}', '', $this->content);
				}
			else if($thevar||$thevar===0||$thevar==='0')
				{
				$this->content = str_replace('{' . $regs[1] . '}', $thevar, $this->content);
				}
			else
				{
				$this->content = str_replace('{' . $regs[1] . '}', '', $this->content);
				}
			$changes=true;
			}
		return $changes;
		}

	// Conditions replacement
	function parseConditions()
		{
		$changes=false;
		while(preg_match('/\%([a-z0-9!_\.|]+)\%([^%]*)\%\/\1\%/si', $this->content, $regs))
			{
			$conds=explode('|',$regs[1]);
			$result=false;
			for($i=sizeof($conds)-1; $i>=0; $i--)
				{
				$inverse=false;
				if(strpos($conds[$i],'!')===0)
					{
					$inverse=true;
					$conds[$i]=substr($conds[$i],1);
					}
				$thevar=Varstream::get($this->core,$conds[$i]);
				if(((!$inverse)&&isset($thevar)&&$thevar&&((!$thevar instanceof ArrayObject)
					||$thevar->count()))||((!(isset($thevar)&&$thevar
					&&((!$thevar instanceof ArrayObject)||$thevar->count())))&&$inverse))
					$result=true;
				}
			$replace=str_replace('|','\|',$regs[1]);
			if($result)
				{
				$this->content = str_replace('%' . $regs[1] . '%', '', $this->content);
				$this->content = str_replace('%/' . $regs[1] . '%', '', $this->content);
				}
			else
				{
				$this->content = preg_replace('/\%' . $replace . '\%([^%]*)\%\/' . $replace . '\%/si', '', $this->content);
				}
			$changes=true;
			}
		return $changes;
		}
	}