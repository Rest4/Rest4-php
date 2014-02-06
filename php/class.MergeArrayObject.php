<?php
// Special ArrayObject used to handle varstreams with no merge information loss
class MergeArrayObject extends ArrayObject
{
  // Destination ArrayObject must be empty before merge (array1=array2)
  const ARRAY_MERGE_RESET = 4;
  // Array content must be added when merged (array1.+=array2.*)
  const ARRAY_MERGE_POP = 8;
  // Array content must be merged by combining indexes (array1.i=array2.i)
  const ARRAY_MERGE_COMBINE = 16;
  public function __construct($input=array(),
                              $flags=0, $iterator_class='ArrayIterator') {
    if(!($flags&self::ARRAY_MERGE_RESET
         ||$flags&self::ARRAY_MERGE_POP
         ||$flags&self::ARRAY_MERGE_COMBINE)) {
      $flags=$flags|self::ARRAY_MERGE_COMBINE;
    }
    parent::__construct($input,
                        ArrayObject::ARRAY_AS_PROPS|$flags, $iterator_class);
  }
  public function has($value)
  {
    return in_array($value,(array) $this);
  }
}

