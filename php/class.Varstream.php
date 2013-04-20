<?php
// Operations on data trees
class Varstream
	{
	// Get a value by it's key
	public static function get($root,$key)
		{
		$object=$root;
		// Loop on each key node
		foreach(explode('.',$key) as $node)
			{
			if($object instanceof ArrayObject) // ArrayObject
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
				throw new Exception('Data nodes should always extends ArrayObject or stdClass'
					.' (key:'.$key.'='.utf8_encode(print_r($object,true)).':'.$node.'.');
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
			if(($node=='+'||$node=='*'||$node=='!'
				||(is_numeric($node)&&intval($node)==$node))) // ArrayObject
				{
				if($node=='!') // Reset
					{
					if($object instanceof ArrayObject)
						$object->exchangeArray(array());
					else
						$object=new MergeArrayObject();
					$node=0;
					if($object instanceof MergeArrayObject)
						$object->setFlags($object->getFlags()|MergeArrayObject::ARRAY_MERGE_RESET);
					}
				else if($node=='+') // Append
					{
					if($object instanceof ArrayObject)
						$node=$object->count();
					else
						{
						$object=new MergeArrayObject();
						$node=0;
						}
					if($object instanceof MergeArrayObject)
						$object->setFlags($object->getFlags()|MergeArrayObject::ARRAY_MERGE_POP);
					}
				else if($node=='*') // Last item
					{
					if($object instanceof ArrayObject)
						$node=($object->count()?$object->count()-1:0);
					else
						{
						$object=new MergeArrayObject(array(),MergeArrayObject::ARRAY_MERGE_POP);
						$node=0;
						}
					}
				else // Numeric index
					{
					if(!($object instanceof ArrayObject))
						$object=new MergeArrayObject();
					}
				if(!isset($object[$node]))
					$object[$node]=false;
				// Changing objet reference to the current node
				$object=&$object[$node];
				}
			else
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
				if($object instanceof ArrayObject)
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
					throw new Exception('Data nodes should always extends ArrayObject or stdClass.');
				}
			// Processing current node
			if(($node=='+'||$node=='*'||$node=='!'
				||(is_numeric($node)&&intval($node)==$node))) // ArrayObject
				{
				if($node=='!') // Reset
					{
					if($object instanceof ArrayObject)
						$object->exchangeArray(array());
					else
						$object=new MergeArrayObject();
					$node=0;
					if($object instanceof MergeArrayObject)
						$object->setFlags($object->getFlags()|MergeArrayObject::ARRAY_MERGE_RESET);
					}
				else if($node=='+') // Append
					{
					if($object instanceof ArrayObject)
						$node=$object->count();
					else
						{
						$object=new MergeArrayObject();
						$node=0;
						}
					if($object instanceof MergeArrayObject)
						$object->setFlags($object->getFlags()|MergeArrayObject::ARRAY_MERGE_POP);
					}
				else if($node=='*') // Last item
					{
					if($object instanceof ArrayObject)
						$node=($object->count()?$object->count()-1:0);
					else
						{
						$object=new MergeArrayObject(array(),MergeArrayObject::ARRAY_MERGE_POP);
						$node=0;
						}
					}
				else // Numeric index
					{
					if(!($object instanceof ArrayObject))
						$object=new MergeArrayObject();
					}
				}
			else if(!($object instanceof stdClass))
				{
				$object=new stdClass();
				}
			$prevNode=$node;
			}
		
		$object2=$root;
		foreach(explode('.',$source) as $node)
			{
			if(($node=='+'||$node=='*'||$node=='!'
				||(is_numeric($node)&&intval($node)==$node))) // ArrayObject
				{
				if($node=='!') // Reset
					{
					if($object2 instanceof ArrayObject)
						$object2->exchangeArray(array());
					else
						$object2=new MergeArrayObject();
					$node=0;
					if($object2 instanceof MergeArrayObject)
						$object2->setFlags($object2->getFlags()|MergeArrayObject::ARRAY_MERGE_RESET);
					}
				else if($node=='+') // Append
					{
					if($object2 instanceof ArrayObject)
						$node=$object2->count();
					else
						{
						$object2=new MergeArrayObject();
						$node=0;
						}
					if($object2 instanceof MergeArrayObject)
						$object2->setFlags($object2->getFlags()|MergeArrayObject::ARRAY_MERGE_POP);
					}
				else if($node=='*') // Last item
					{
					if($object2 instanceof ArrayObject)
						$node=($object2->count()?$object2->count()-1:0);
					else
						{
						$object2=new MergeArrayObject(array(),MergeArrayObject::ARRAY_MERGE_POP);
						$node=0;
						}
					}
				else // Numeric index
					{
					if(!($object instanceof ArrayObject))
						$object=new MergeArrayObject();
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
		if($object instanceof ArrayObject)
			{
			if($object2 instanceof ArrayObject||$object2 instanceof stdClass)
				$object[$prevNode]=$object2;
			else
				{
				$object[$prevNode]=&$object2;
				}
			}
		else if($object instanceof stdClass)
			{
			if($object2 instanceof ArrayObject||$object2 instanceof stdClass)
				$object->$prevNode=$object2;
			else
				$object->$prevNode=&$object2;
			}
		else
			throw new Exception('Data nodes should always extends ArrayObject or stdClass.');
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
				// Escaping comments & malformed lines
				if($cnt[$i]=='#')//This next chars causes bugs !!!&&$cnt[$i]=="="&&$cnt[$i]=="&")
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
						$cNode=str_replace('+','*',str_replace('!','*',$cNode));
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
		return $root;
		}
	// Export an object content
	public static function export($object,&$parentNodes=array(),&$objects=array(),$compress=true)
		{
		$output=''; $lastPropWasAValue=false;
		// Backward compat
		if(is_string($parentNodes))
			{
			$parentNodes=array(0 => $parentNodes);
			trigger_error('Using export with a string as a second parameter is deprecated.');
			}
		// Threating each properties
		foreach($object as $propKey=>$propVal)
			{
			// Register if the property is an object
			// We register objects before replacing indexes by special indexes like + or ! cause it doesn't
			// work for backward references
			$objKey=false;
			if($propVal instanceof ArrayObject||$propVal instanceof stdClass)
				{
				// Looking for backward reference
				$objKey=array_search($propVal,$objects,true);
				// Registering object if not already done
				if($objKey===false)
					$objects[implode('.',$parentNodes).(sizeof($parentNodes)?'.':'').$propKey]=$propVal;
				}
			// Property is a MergeArrayObject : Applying merge rules
			if($object instanceof MergeArrayObject)
				{
				if($propKey==0&&$object->getFlags() & MergeArrayObject::ARRAY_MERGE_RESET)
					{
					$propKey='!';
					}
				else if($object->getFlags() & MergeArrayObject::ARRAY_MERGE_POP)
					{
					$propKey='+';//.$propKey;
					}
				}
			// Property is an object
			if($propVal instanceof ArrayObject||$propVal instanceof stdClass)
				{
				// Linking to backward reference
				if($objKey!==false)
					{
					$output.=($output?"\n":'');
					// Building the left side
					if(sizeof($parentNodes))
						{
						// Applying precedence shortcut
						if($compress&&$lastPropWasAValue)
							$output.='".';
						// Imploding nodes
						else
							{
							// Adding the value to the ouput
							$output.=implode('.',$parentNodes).'.';
							// Replacing special indexes '+'||'!' by the last index '*'
							while(($index=array_search('+',$parentNodes,true))!==false
								||($index=array_search('!',$parentNodes,true))!==false)
								$parentNodes[$index]='*';
							}
						}
					$output.=$propKey.'&='.$objKey;
					$lastPropWasAValue=true;
					}
				// Recursively export the object contents
				else
					{
					array_push($parentNodes,$propKey);
					$output.=($output?"\n":'').self::export($propVal,$parentNodes,$objects);
					array_pop($parentNodes);
					$lastPropWasAValue=false;
					}
				}
			// Property is a value
			// (could leave empty values but currently there is a bug with Javascript VarStream parser)
			else if($propVal!==''&&(is_bool($propVal)||is_string($propVal)
				||is_int($propVal)||is_float($propVal)))
				{
				$output.=($output?"\n":'');
				// Building the left side
				if(sizeof($parentNodes))
					{
					// Applying precedence shortcut
					if($compress&&$lastPropWasAValue)
						$output.='".';
					// Imploding nodes
					else
						{
						// Adding the value to the ouput
						$output.=implode('.',$parentNodes).'.';
						// Replacing special indexes '+'||'!' by the last index '*'
						while(($index=array_search('+',$parentNodes,true))!==false
							||($index=array_search('!',$parentNodes,true))!==false)
							$parentNodes[$index]='*';
						}
					}
				// Setting the value
				$output.=$propKey.'='.(is_bool($propVal)?($propVal?'true':'false'):
					str_replace("\r\n",'\\'."\n",$propVal));
				$lastPropWasAValue=true;
				}
			}
		return $output;
		}
	// Merge two objects
	public static function loadObject($root,$object,$mustexist=false)
		{
		if($object)
			{
			if(!($object instanceof stdClass||$object instanceof ArrayObject))
				throw new Exception('Object to load is not a stdClass or ArrayObject'
					.' instance (instance of '.get_class($object).').');
			// ArrayObject special load
			if($root instanceof ArrayObject&&$object instanceof ArrayObject)
				{
				// Emptying array if it has the reset flag
				if($object instanceof MergeArrayObject&&
					$object->getFlags()&MergeArrayObject::ARRAY_MERGE_RESET)
					{
					$root->exchangeArray($object);
					$root->setFlags($root->getFlags()|MergeArrayObject::ARRAY_MERGE_RESET);
					return true;
					}
				// Poping elements if the pop flag is set
				else if($object instanceof MergeArrayObject&&
					$object->getFlags()&MergeArrayObject::ARRAY_MERGE_POP)
					{
					foreach($object as $value)
						$root->append($value);
					return true;
					}
				// Combining indexes
				else
					{
					foreach($object as $key => $value)
						{
						if(isset($root[$key])
							&&$root[$key] instanceof ArrayObject
							&&$value instanceof ArrayObject)
							{
							self::loadObject($root[$key],$value);
							}
						else if(isset($root[$key])
							&&$root[$key] instanceof stdClass
							&&$value instanceof stdClass)
							{
							self::loadObject($root[$key],$value);
							}
						else
							$root[$key]=$value;
						}
					return true;
					}
				}
			else if($root instanceof stdClass&&$object instanceof stdClass)
				{
				foreach(get_object_vars($object) as $key =>$value)
					{
					if(isset($root->{$key})&&$root->{$key} instanceof stdClass
						&&$value instanceof stdClass)
						self::loadObject($root->{$key},$value,true);
					else if(isset($root->{$key})&&$root->{$key} instanceof ArrayObject
						&&$value instanceof ArrayObject)
						self::loadObject($root->{$key},$value,true);
					else
						$root->{$key}=$value;
					}
				}
			else
				throw new Exception('Root object and loaded object must have the'
					.' same type (stdClass or ArrayObject).');
			return true;
			}
		else if($mustexist)
			{
			throw new Exception('No object given to the script.');
			}
		return false;
		}
	}