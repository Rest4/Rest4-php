<?php
class MergeArrayObject extends ArrayObject
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
	}
?>
