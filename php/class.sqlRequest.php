<?php
class sqlRequest
{
  const SELECT='SELECT';
  const DELETE='DELETE';
  const UPDATE='UPDATE';
  const SHOW='SHOW';
  const DISTINCT='DISTINCT';
  const WHERE='WHERE';
  const FROM='FROM';
  const LIMIT='LIMIT';
  const ORDERBY='ORDER BY';
  const OPERATOR_AND='AND';
  const OPERATOR_OR='OR';
  const OPERATOR_ON='ON';
  const JOIN='JOIN';
  const LEFT='LEFT';
  const RIGHT='RIGHT';
  private $type=sqlRequest::SELECT;
  private $options=array();
  private $tables=array();
  private $fields=array();
  private $join_clauses=array();
  private $where_clauses=array();
  private $sort_clauses=array();
  private $limit=0;
  private $start=0;
  public function __construct($request='')
  {
    if ($request!='') {
      $this->setRequest($request);
    }
  }
  public function __destruct()
  { }
  public function setType($type)
  {
    $this->type=$type;
  }
  public function setOptions()
  {
    $this->options=array_merge(func_get_args(),$this->options);
  }
  public function setTables()
  {
    $this->tables=array_merge(func_get_args(),$this->tables);
  }
  public function setFields()
  {
    foreach (func_get_args() as $field) {
      if (($pos=strpos($field,'.'))>0
        &&!in_array(substr($field,0,$pos),$this->tables)) {
        $this->setTables(substr($field,0,$pos));
      }
      array_push($this->fields,$field);
    }
  }
  public function resetFields()
  {
    $this->fields=array();
  }
  public function setJoinClause($table, $clauses, $merge='')
  {
    $this->join_clauses[]=array("table"=>$table,"clauses"=>$clauses,"merge"=>$merge);
  }
  public function setWhereClauses()
  {
    $this->where_clauses=array_merge(func_get_args(),$this->where_clauses);
  }
  public function setSortClauses()
  {
    $params=func_get_args();
    $this->sort_clauses=array_merge($this->sort_clauses,$params);
  }
  public function setLimit($limit)
  {
    $this->limit=$limit;
  }
  public function setStart($start)
  {
    $this->start=$start;
  }
  public function getRequest()
  {
    $request='';
    if ($this->type==sqlRequest::SELECT) {
      $request.=sqlRequest::SELECT;
      $option_size=sizeof($this->options);
      if ($this->type==sqlRequest::SELECT&&$option_size>0) {
        for ($i=0; $i<$option_size; $i++) {
          $request.=' '.$this->options[$i];
        }
      }
      $field_size=sizeof($this->fields);
      if ($field_size>0) {
        for ($i=0; $i<$field_size; $i++) {
          $request.=' '.$this->fields[$i].($i<$field_size-1?',':'');
        }
      } else {
        $request.=' *';
      }
    }

    if ($this->type==sqlRequest::DELETE) {
      $request.=sqlRequest::DELETE;
    }
    $join_size=sizeof($this->join_clauses);
    if ($this->type==sqlRequest::SELECT||$this->type==sqlRequest::DELETE) {
      $request.=' '.sqlRequest::FROM;
      $table_size=sizeof($this->tables);
      if ($table_size>0) {
        for ($i=0; $i<$table_size; $i++) {
          if ($join_size>0) {
            for ($j=0; $j<$join_size; $j++) {
              if ($this->tables[$i]==$this->join_clauses[$j]['table']) {
                $i++;
                $j=-1;
              }
            }
          }
          $request.=' '.$this->tables[$i].($i<$table_size-1?',':'');
        }
      }
    }
    if ($join_size>0) {
      for ($i=0; $i<$join_size; $i++) {
        $request.=' '.($this->join_clauses[$i]['merge']?
          $this->join_clauses[$i]['merge'].' ':'').sqlRequest::JOIN;
        $request.=' '.$this->join_clauses[$i]['table']
          .' '.sqlRequest::OPERATOR_ON;
        $request.=' '.$this->join_clauses[$i]['clauses'];
      }
    }
    $where_size=sizeof($this->where_clauses);
    if ($where_size>0) {
      $request.=' '.sqlRequest::WHERE;
      for ($i=0; $i<$where_size; $i++) {
        $request.=' '.$this->where_clauses[$i]
          .($i<$where_size-1?' '.sqlRequest::OPERATOR_AND :'');
      }
    }
    $sort_size=sizeof($this->sort_clauses);
    if ($sort_size>0) {
      $request.=' '.sqlRequest::ORDERBY;
      for ($i=0; $i<$sort_size; $i++) {
        $request.=' '.$this->sort_clauses[$i].($i<$sort_size-1?',':'');
      }
    }
    if ($this->limit) {
      $request.=' '.sqlRequest::LIMIT . ' '
        . ($this->start?$this->start.', ':'') . $this->limit;
    }

    return $request;
  }
  public function setRequest($request)
  {
  }
}

