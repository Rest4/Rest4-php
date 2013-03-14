<?php
class xcDataObject extends stdClass  // Replace with stdObject
	{
	function loadTextVarfile($path,$mustexist=false) // Remove when XCMS is merged with RESTfor
		{
		$file=new xcFileTextVar($path);
		if($file->existsWithPaths())
			{
			$file->getVars($this);
			}
		else if($mustexist)
			{
			throw new Exception('xcDataObject -> loadTextVarfile : File "'.$path.'" doesn\'t exist.');
			}
		unset($file);
		}
	function loadXmlVarfile($path,$mustexist=false) // Remove when XCMS is merged with RESTfor
		{
		$file=new xcFileXmlVar($path);
		if($file->existsWithPaths())
			{
			$file->getVars($this);
			}
		else if($mustexist)
			{
			throw new Exception('xcDataObject -> loadXmlVarfile : File "'.$path.'" doesn\'t exist.');
			}
		unset($file);
		}
	function loadObject($object,$mustexist=true) // Remove when XCMS is merged with RESTfor
		{
		if($object)
			{
			return xcDatas::loadObject($this,$object);
			}
		else if($mustexist)
			{
			throw new Exception('xcDataObject -> loadDataObjectVars : No object given to the script.');
			}
		return false;
		}
	function loadDataObjectVars($object,$mustexist=true) // Remove when XCMS is merged with RESTfor
		{
		$this->loadObject($object,$mustexist);
		}
	function exportContent($context='') // Remove when XCMS is merged with RESTfor
		{
		return xcDatas::export($this,$context);
		}
	function import($cnt) // Remove when XCMS is merged with RESTfor
		{
		return xcDatas::import($this,$cnt);
		}
	function getVar($key,$ref=false) // Remove when XCMS is merged with RESTfor
		{
		return xcDatas::get($this,$key);
		}
	function setVar($key,$value) // Remove when XCMS is merged with RESTfor
		{
		return xcDatas::set($this,$key,$value);
		}
	function linkVars($target, $source) // Remove when XCMS is merged with RESTfor
		{
		xcDatas::link($this,$target, $source);
		}
	}
?>