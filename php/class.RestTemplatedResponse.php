<?php
class RestTemplatedResponse extends RestStreamedResponse
	{
	private $template;
	private $scope;
	private $includeName;
	function __construct($code, $headers, $template, $scope)
		{
		$this->template=$template;
		$this->scope=$scope;
		parent::__construct($code, $headers);
		}
	function pump()
		{
		// If it's the end of the template, checking for errors
		if(!$this->template)
			{
			switch(preg_last_error())
				{
				case PREG_BACKTRACK_LIMIT_ERROR;
					trigger_error('Backtrack limit was exhausted '
						.'('.$this->core->server->location.$_SERVER['REQUEST_URI'].')');
				case PREG_RECURSION_LIMIT_ERROR;
					trigger_error('Recursion limit was exhausted'
						.' ('.$this->core->server->location.$_SERVER['REQUEST_URI'].')');
				}
			if(preg_match('/@([\/]?)([a-z0-9_\.!|]+)@/i', $this->template,$dregs))
				trigger_error('A loop ('.$dregs[2].') has not been interpreted'
					.' ('.$this->core->server->location.$_SERVER['REQUEST_URI'].')');
			if(preg_match('/\%([\/]?)([a-z0-9_\.!|]+)\%/i', $this->template,$dregs))
				trigger_error('A condition ('.$dregs[2].') has not been interpreted'
					.' ('.$this->core->server->location.$_SERVER['REQUEST_URI'].')');
			return '';
			}
		$chunk='';
		$curOffset=$newOffset=0;
		while($curOffset>=0&&strlen($chunk)<=$this->bufferSize)
			{
			// Parsing template instructions
			$curOffset=Template::parseConditions($this->core,$this->template);
			if(($newOffset=Template::parseLoops($this->core,$this->template,$this->bufferSize))>=0
				&&($curOffset==-1||$newOffset<$curOffset))
				$curOffset=$newOffset;
			if(($newOffset=Template::parseIncludes($this->core,$this->template))>=0
				&&($curOffset==-1||$newOffset<$curOffset))
				$curOffset=$newOffset;
			if(($newOffset=Template::parseVars($this->core,$this->template))>=0
				&&($curOffset==-1||$newOffset<$curOffset))
				$curOffset=$newOffset;
			if(($newOffset=Template::parseConditions($this->core,$this->template))>=0
				&&($curOffset==-1||$newOffset<$curOffset))
				$curOffset=$newOffset;
			if($curOffset!==-1)
				$curOffset=Template::getSafeOffset($this->core,$this->template);
			// Truncating template content and filling the chunk
			if($curOffset>=0)
				{
				$chunk.=substr($this->template,0,$curOffset);
				$this->template=substr($this->template,$curOffset);
				}
			else
				{
				$chunk.=$this->template;
				$this->template='';
				}
			}
		return $chunk;
		}
	}
