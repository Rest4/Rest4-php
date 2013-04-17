<?php
// A better json encoder/decoder with ArrayObjects support
class Json
	{
	public static function encode($tree,$options=0)
		{
		if(version_compare(PHP_VERSION, '5.3.0') >= 0)
			return json_encode(self::exportTree($tree),$options);
		return json_encode(self::exportTree($tree));
		}
	public static function decode($json, $assoc=false,$depth=512,$options=0)
		{
		if(version_compare(PHP_VERSION, '5.4.0') >= 0)
			return self::importTree(json_decode($json,$assoc,$depth,$options));
		else if(version_compare(PHP_VERSION, '5.3.0') >= 0)
			return self::importTree(json_decode($json,$assoc,$depth));
		return self::importTree(json_decode($json,$assoc));
		}
	// Convert every arrays in a data tree to ArrayObject instances
	public static function importTree($root,$mustexist=false)
		{
		if($root instanceof stdClass)
			{
			foreach(get_object_vars($root) as $key =>$value)
				{
				if($value instanceof stdClass||is_array($value))
					$root->{$key}=self::importTree($value,true);
				}
			return $root;
			}
		else if(is_array($root))
			{
			$root=new ArrayObject($root);
			foreach($root as $key => $value)
				{
				if($value instanceof stdClass||is_array($value))
					$root[$key]=self::importTree($value,true);
				}
			return $root;
			}
		else if($mustexist)
			{
			throw new Exception('No or invalid object given to the function.');
			}
		return new stdClass();
		}
	// Convert every ArrayObject instances in a data tree to arrays
	public static function exportTree($root,$mustexist=false)
		{
		if($root instanceof stdClass)
			{
			foreach(get_object_vars($root) as $key =>$value)
				{
				if($value instanceof stdClass||$value instanceof ArrayObject)
					$root->{$key}=self::exportTree($value,true);
				}
			return $root;
			}
		else if($root instanceof ArrayObject)
			{
			$root=$root->getArrayCopy();
			foreach($root as $key => $value)
				{
				if($value instanceof stdClass||$value instanceof ArrayObject)
					$root[$key]=self::exportTree($value,true);
				}
			return $root;
			}
		else if($mustexist)
			{
			throw new Exception('No or invalid object given to the function.');
			}
		return new stdClass();
		}
	}