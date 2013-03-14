<?php
class xcObjectCollection extends ArrayObject
	{
	const ARRAY_MERGE_RESET = 4 ;  // Array content must be resetted before merged
	const ARRAY_MERGE_POP = 8 ;  // Array content must be added when merged
	public function __construct($input=array(), $flags=0, $iterator_class='ArrayIterator')
		{
		parent::__construct($input, ArrayObject::ARRAY_AS_PROPS|$flags, $iterator_class); // |self::ARRAY_MERGE_POP
		}
	public function has($value)
		{
		return in_array($value,(array)$this);
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
		return xcDatas::link($this,$target, $source);
		}
	}
?>