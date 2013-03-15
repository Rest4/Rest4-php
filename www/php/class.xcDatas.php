<?php
// Operations on data trees
class xcDatas
	{
	// Get a value by it's key
	public static function get($root,$key)
		{
		$object=$root;
		// Loop on each key node
		foreach(explode('.',$key) as $node)
			{
			if($object instanceof xcObjectCollection) // ArrayObject
				{
				if($node=='*') // Last item
					{
					$node=($object->count()?$object->count()-1:0);
					}
				else if($node=='!') // First item (but not so pertinent, should remove ?)
					{
					$node=0;
					}
				if(isset($object[$node]))
					$object=$object[$node];
				else
					return null;
				}
			else if($object instanceof stdClass) // stdObject
				{
				if(isset($object->$node))
					$object=$object->$node;
				else
					return null;
				}
			else if($object)
				{
				throw new Exception('Data nodes should always extends xcObjectCollection or stdClass (key:'.$key.'='.utf8_encode(print_r($object,true)).':'.$node.'.');
				}
			}
		return $object;
		}
	// Set a value to a key
	public static function set($root,$key,$value)
		{
		$object=$root;
		// Loop on each key node
		foreach(explode('.',$key) as $node)
			{
			if(($node=='+'||$node=='*'||$node=='!'||(is_numeric($node)&&intval($node)==$node))) // ArrayObject
				{
				if($node=='!') // Reset
					{
					if($object instanceof xcObjectCollection)
						$object->exchangeArray(array());
					else
						$object=new xcObjectCollection();
					$node=0;
					$object->setFlags($object->getFlags()|xcObjectCollection::ARRAY_MERGE_RESET);
					}
				else if($node=='+') // Append
					{
					if($object instanceof xcObjectCollection)
						$node=$object->count();
					else
						{
						$object=new xcObjectCollection(array(),xcObjectCollection::ARRAY_MERGE_POP);
						$node=0;
						}
					}
				else if($node=='*') // Last item
					{
					if($object instanceof xcObjectCollection)
						$node=($object->count()?$object->count()-1:0);
					else
						{
						$object=new xcObjectCollection(array(),xcObjectCollection::ARRAY_MERGE_POP);
						$node=0;
						}
					}
				else // Specific item
					{
					if(!($object instanceof xcObjectCollection))
						$object=new xcObjectCollection();
					$object->setFlags($object->getFlags() & ~xcObjectCollection::ARRAY_MERGE_POP);
					}
				if(!isset($object[$node]))
					$object[$node]=false;
				// Changing objet reference to the current node
				$object=&$object[$node];
				}
			else // stdObject
				{
				if(!($object instanceof stdClass))
					$object=new stdClass();
				if(!isset($object->$node))
					{
					$object->$node=false;
					}
				// Changing objet reference to the current node
				$object=&$object->$node;
				}
			}
		return $object=$value;
		}
	// Link two nodes together
	public static function link($root,$target, $source)
		{
		$object=$root;
		// Loop on each key node of the target
		$prevNode=null;
		foreach(explode('.',$target) as $node)
			{
			// Changing objet reference to the previous node
			if($prevNode!==null)
				{
				if($object instanceof xcObjectCollection)
					{
					if(!isset($object[$prevNode]))
						$object[$prevNode]=false;
					$object=&$object[$prevNode];
					}
				else if($object instanceof stdClass)
					{
					if(!isset($object->$prevNode))
						$object->$prevNode=false;
					$object=&$object->$prevNode;
					}
				else
					throw new Exception('Data nodes should always extends xcObjectCollection or stdClass.');
				}
			// Processing current node
			if(($node=='+'||$node=='*'||$node=='!'||(is_numeric($node)&&intval($node)==$node))) // ArrayObject
				{
				if($node=='!') // Reset
					{
					if($object instanceof xcObjectCollection)
						$object->exchangeArray(array());
					else
						$object=new xcObjectCollection();
					$node=0;
					$object->setFlags($object->getFlags()|xcObjectCollection::ARRAY_MERGE_RESET);
					}
				else if($node=='+') // Append
					{
					if($object instanceof xcObjectCollection)
						$node=$object->count();
					else
						{
						$object=new xcObjectCollection(array(),xcObjectCollection::ARRAY_MERGE_POP);
						$node=0;
						}
					}
				else if($node=='*') // Last item
					{
					if($object instanceof xcObjectCollection)
						$node=($object->count()?$object->count()-1:0);
					else
						{
						$object=new xcObjectCollection(array(),xcObjectCollection::ARRAY_MERGE_POP);
						$node=0;
						}
					}
				else // Specific item
					{
					if(!($object instanceof xcObjectCollection))
						$object=new xcObjectCollection();
					$object->setFlags($object->getFlags() & ~xcObjectCollection::ARRAY_MERGE_POP);
					}
				}
			else if(!($object instanceof stdClass)) // stdObject
				{
				$object=new stdClass();
				}
			$prevNode=$node;
			}
		
		$object2=$root;
		foreach(explode('.',$source) as $node)
			{
			if(($node=='+'||$node=='*'||$node=='!'||(is_numeric($node)&&intval($node)==$node))) // ArrayObject
				{
				if($node=='!') // Reset
					{
					if($object2 instanceof xcObjectCollection)
						$object2->exchangeArray(array());
					else
						$object2=new xcObjectCollection();
					$node=0;
					$object2->setFlags($object2->getFlags()|xcObjectCollection::ARRAY_MERGE_RESET);
					}
				else if($node=='+') // Append
					{
					if($object2 instanceof xcObjectCollection)
						$node=$object2->count();
					else
						{
						$object2=new xcObjectCollection(array(),xcObjectCollection::ARRAY_MERGE_POP);
						$node=0;
						}
					}
				else if($node=='*') // Last item
					{
					if($object2 instanceof xcObjectCollection)
						$node=($object2->count()?$object2->count()-1:0);
					else
						{
						$object2=new xcObjectCollection(array(),xcObjectCollection::ARRAY_MERGE_POP);
						$node=0;
						}
					}
				else // Specific item
					{
					if(!($object instanceof xcObjectCollection))
						$object=new xcObjectCollection();
					$object->setFlags($object2->getFlags() & ~xcObjectCollection::ARRAY_MERGE_POP);
					}
				if(!isset($object2[$node]))
					$object2[$node]=false;
				// Changing objet reference to the current node
				$object2=&$object2[$node];
				}
			else // stdObject
				{
				if(!($object2 instanceof stdClass))
					$object2=new stdClass();
				if(!isset($object2->$node))
					$object2->$node=false;
				// Changing objet reference to the current node
				$object2=&$object2->$node;
				}
			}
		// Linking source to the target
		if($object instanceof xcObjectCollection)
			{
			if($object2 instanceof xcObjectCollection||$object2 instanceof stdClass)
				$object[$prevNode]=$object2;
			else
				{
				$object[$prevNode]=&$object2;
				}
			}
		else if($object instanceof stdClass)
			{
			if($object2 instanceof xcObjectCollection||$object2 instanceof stdClass)
				$object->$prevNode=$object2;
			else
				$object->$prevNode=&$object2;
			}
		else
			throw new Exception('Data nodes should always extends xcObjectCollection or stdClass.');
		}
	// Load a varstream
	public static function import($root,$cnt)
		{
		if($cnt)
			{
			$x=strlen($cnt);
			$prevCNode='';
			for($i=0; $i<$x; $i++)
				{
				$cNode='';
				$cNode2='';
				if($cnt[$i]=='#')//This next chars causes bugs !!!&&$cnt[$i]=="="&&$cnt[$i]=="&") // Escaping comments & malformed lines
					{
					while($i<$x&&$cnt[$i]!="\n"&&$cnt[$i]!="\r")
						{
						$i++;
						}
					}
				else if($cnt[$i]!="\n"&&$cnt[$i]!="\r")
					{
					if($cnt[$i]=='#')
						trigger_error('PUTAINNNNNNN !!!');
					for($i=$i; $i<$x; $i++) // Reading var name
						{
						if($cnt[$i]!='='&&$cnt[$i]!='&'&&$cnt[$i]!="\n"&&$cnt[$i]!="\r")
							$cNode.=$cnt[$i];
						else
							break;
						}
					if($cNode[0]=='"')
						{
						$cNode=substr($prevCNode,0,strrpos($prevCNode,'.')).substr($cNode,1);
						}
					if($i<$x&&$cnt[$i]=='&'&&$cnt[$i+1]=='=') // Linked vars
						{
						for($i=$i+2; $i<$x; $i++)
							{
							if($cnt[$i]!="\n"&&$cnt[$i]!="\r")
								$cNode2.=$cnt[$i];
							else
								break;
							}
						self::link($root,$cNode,$cNode2);
						$prevCNode=$cNode;
						}
					else if($i<$x&&$cnt[$i]=='=') // Var values
						{
						for($i=$i+1; $i<$x; $i++)
							{
							if($cnt[$i]=='\\'&&($cnt[$i+1]=="\n"||$cnt[$i+1]=="\r"))
								{
								$cNode2.="\n";
								$i=$i+2;
								}
							else if($cnt[$i]!="\n"&&$cnt[$i]!="\r")
								$cNode2.=$cnt[$i];
							else
								break;
							}
						if($cNode[0]=='#')
							trigger_error($cNode);
						if($cNode2=='false'||$cNode2=='null')
							{
							self::set($root,$cNode,false);
							}
						else if($cNode2=='true')
							{
							self::set($root,$cNode,true);
							}
						else
							{
							self::set($root,$cNode,$cNode2);
							}
						$prevCNode=$cNode;
						}
					}
				}
			}
		}
	// Export a node content
	public static function exportBranch($root,$context='',$output=array(),&$objects=array())
		{
		//$clean=false;
		foreach($root as $key=>$value)
			{/*
			if($root instanceof xcObjectCollection)
				{
				if($key==0&&$root->getFlags() & xcObjectCollection::ARRAY_MERGE_RESET)
					{
					$key='!';
					}
				else if($root->getFlags() & xcObjectCollection::ARRAY_MERGE_POP)
					{
					$key='+'.$key;
					}
				}*/
			if($value instanceof xcObjectCollection||$value instanceof stdClass)
				{
				$objKey=array_search($value,$objects,true);
				if($objKey!==false)
					{
					$output[($context?$context.'.':'').$key]='&'.$objKey;
					}
				else
					{
					$objects[($context?$context.'.':'').$key]=$value; // Register objects without +\! cause it doesn't work for back references
					$output=self::exportBranch($value,($context?$context.'.':'').$key,$output,$objects); // &$objects
					}
				}
			else if(is_bool($value))
				{
				$output[($context?$context.'.':'').$key]=($value?'true':'false');
				}
			else if(is_string($value)||is_int($value)||is_float($value))
				{
				$output[($context?$context.'.':'').$key]=$value;
				}/*
			if(!$clean)
				{
				$context=str_replace('+','*',$context);
				$clean=true;
				}*/
			}
		return $output;
		}
	// Export as a varstream
	public static function export($root,$context='')
		{
		$output='';
		$prevKey='';
		// Exporting each nodes recusively
		$values=self::exportBranch($root,$context);
		// Outputting varstream
		foreach($values as $key => $value)
			{
			$ref=false;
			if(strpos($value,'&')===0)
				{
				$ref=true;
				$value=substr($value,1);
				}
			//$key=preg_replace('/(^|\.)(\+|\*)([0-9]+)(\.|$)/','\1\2\4',$key);
			if(strrpos($key,'.')!==false&&substr($key,0,strrpos($key,'.'))==substr($prevKey,0,strrpos($prevKey,'.')))
				{
				$output.='"'.substr($key,strrpos($key,'.')).($ref?'&':'').'='.str_replace("\r\n",'\\'."\n",$value)."\n";
				}
			else
				{
				$output.=$key.($ref?'&':'').'='.str_replace("\r\n",'\\'."\n",$value)."\n";
				}
			$prevKey=$key;
			}
		return $output;
		}
	// Merge two objects
	public static function loadObject($root,$object,$mustexist=false)
		{
		if($object)
			{
			if(!($object instanceof stdClass||$object instanceof xcObjectCollection))
				throw new Exception('Object to load is not a stdClass or xcObjectCollection instance (instance of '.get_class($object).').');
			// Array object special load
			if($root instanceof xcObjectCollection&&$object instanceof xcObjectCollection)
				{
				// Poping elements if the pop flags is still set
				if($object->getFlags&xcObjectCollection::ARRAY_MERGE_POP)
					{
					foreach($object as $value)
						$root->append($value);
					return true;
					}
				// Emptying array if it has the reset flags
				else if($object->getFlags&xcObjectCollection::ARRAY_MERGE_RESET)
					{
					$root->exchangeArray($object);/*
					foreach($root as $key => $value)
						$root->offsetUnset($key);
					foreach($object as $key => $value)
						$root->offsetSet($key,$value);*/
					return true;
					}
				}
			foreach(($object instanceof stdClass?get_object_vars($object):$object) as $key =>$value)
				{
				if(($value instanceof stdClass&&($oldVal=self::get($root,$key)) instanceof stdClass)||
					($value instanceof xcObjectCollection&&($oldVal=self::get($root,$key)) instanceof xcObjectCollection))
					{
					self::loadObject($oldVal,$value,true);
					}
				else
					{
					self::set($root,$key,$value);
					}
				}
			return true;
			}
		else if($mustexist)
			{
			throw new Exception('stdClass -> loadDataObjectVars : No object given to the script.');
			}
		return false;
		}
	}
?>