<?php
class RestDbEntriesDriver extends RestDriver
	{
	static $drvInf;
	const OP_EQUAL='eq';
	const OP_NOTEQUAL='noteq';
	const OP_SUPEQUAL='supeq';
	const OP_SUPERIOR='sup';
	const OP_INFEQUAL='infeq';
	const OP_INFERIOR='inf';
	const OP_LIKE='like';
	const OP_ENDLIKE='elike';
	const OP_STARTLIKE='slike';
	const OP_IS='is';
	function __construct(RestRequest $request)
		{
		parent::__construct($request);
		// Retrieving main table schema
		$res=new RestResource(new RestRequest(RestMethods::GET,'/db/'.$this->request->database.'/'.$this->request->table.'.dat'));
		$res=$res->getResponse();
		if($res->code!=RestCodes::HTTP_200)
			throw new RestException(RestCodes::HTTP_400,'Can\'t list entries of an unexisting table.');
		$this->_schema=$res->content;
		}
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='DB:Database Entries Driver';
		$drvInf->description='List each entries of a table. Apply filters, sorting and searchs.';
		$drvInf->usage='/db/database/table/list(.ext)?mode=(count|light|extend|join|fulljoin)&joinMode=(joined|refered)&joinField=([a-zA-Z0-9]+)&fileMode=(count|join)&start=([0-9]+)&limit=([0-9]+)&orderby=([a-z0-9]+)&dir=desc';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='application/internal';
		$drvInf->methods->head=new stdClass();
		$drvInf->methods->head->outputMimes='application/internal';
		$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='application/internal';
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='mode';
		$drvInf->methods->get->queryParams[0]->value='normal';
		$drvInf->methods->get->queryParams[1]=new stdClass();
		$drvInf->methods->get->queryParams[1]->name='joinMode';
		$drvInf->methods->get->queryParams[1]->value='all';
		$drvInf->methods->get->queryParams[2]=new stdClass();
		$drvInf->methods->get->queryParams[2]->name='joinField';
		$drvInf->methods->get->queryParams[2]->filter='iparameter';
		$drvInf->methods->get->queryParams[2]->multiple=true;
		$drvInf->methods->get->queryParams[3]=new stdClass();
		$drvInf->methods->get->queryParams[3]->name='fileMode';
		$drvInf->methods->get->queryParams[3]->value='none';
		$drvInf->methods->get->queryParams[4]=new stdClass();
		$drvInf->methods->get->queryParams[4]->name='start';
		$drvInf->methods->get->queryParams[4]->type='number';
		$drvInf->methods->get->queryParams[4]->filter='int';
		$drvInf->methods->get->queryParams[4]->value='0';
		$drvInf->methods->get->queryParams[5]=new stdClass();
		$drvInf->methods->get->queryParams[5]->name='limit';
		$drvInf->methods->get->queryParams[5]->type='number';
		$drvInf->methods->get->queryParams[5]->filter='int';
		$drvInf->methods->get->queryParams[5]->value='10';
		$drvInf->methods->get->queryParams[6]=new stdClass();
		$drvInf->methods->get->queryParams[6]->name='orderby';
		$drvInf->methods->get->queryParams[6]->filter='iparameter';
		$drvInf->methods->get->queryParams[6]->value='id';
		$drvInf->methods->get->queryParams[7]=new stdClass();
		$drvInf->methods->get->queryParams[7]->name='dir';
		$drvInf->methods->get->queryParams[7]->value='asc';
		$drvInf->methods->get->queryParams[8]=new stdClass();
		$drvInf->methods->get->queryParams[8]->name='search';
		$drvInf->methods->get->queryParams[8]->filter='cdata';
		$drvInf->methods->get->queryParams[8]->value='';
		$drvInf->methods->get->queryParams[9]=new stdClass();
		$drvInf->methods->get->queryParams[9]->name='searchop';
		$drvInf->methods->get->queryParams[9]->value=RestDbEntriesDriver::OP_EQUAL;
		$drvInf->methods->get->queryParams[10]=new stdClass();
		$drvInf->methods->get->queryParams[10]->name='fieldsearch';
		$drvInf->methods->get->queryParams[10]->filter='iparameter';
		$drvInf->methods->get->queryParams[10]->multiple=true;
		$drvInf->methods->get->queryParams[11]=new stdClass();
		$drvInf->methods->get->queryParams[11]->name='fieldsearchval';
		$drvInf->methods->get->queryParams[11]->filter='cdata';
		$drvInf->methods->get->queryParams[11]->multiple=true;
		$drvInf->methods->get->queryParams[11]->orderless=true;
		$drvInf->methods->get->queryParams[12]=new stdClass();
		$drvInf->methods->get->queryParams[12]->name='fieldsearchop';
		$drvInf->methods->get->queryParams[12]->multiple=true;
		$drvInf->methods->get->queryParams[12]->orderless=true;
		$drvInf->methods->get->queryParams[13]=new stdClass();
		$drvInf->methods->get->queryParams[13]->name='fieldsearchor';
		$drvInf->methods->get->queryParams[13]->value='';
		return $drvInf;
		}
	function head()
		{
		// If no RestException throwed before, the database exists
		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'application/internal')
			);
		}
	function get()
		{
		// Initializing the main request search
		$mainRequestSearch='';
		$sqlWhereConditions='';
		$hasJoinedConditions=false;
		$hasReferedConditions=false;
		$hasLinkedConditions=false;
		$mainOrderby='';
		$linkedOrderby='';
		// Processing order by clause
		if(isset($this->queryParams->orderby)&&$this->queryParams->orderby)
			{
			$fieldFound=false;
			foreach($this->_schema->table->fields as $field)
				{
				// Linked fields
				if(isset($field->linkedTable)&&strpos($this->queryParams->orderby,'linked_'.$field->linkedTable.'_')===0)
					{
					${$field->linkedTable.'Res'}=new RestResource(new RestRequest(RestMethods::GET,'/db/'.$this->request->database.'/'.$field->linkedTable.'.dat'));
					${$field->linkedTable.'Res'}=${$field->linkedTable.'Res'}->getResponse();
					if(${$field->linkedTable.'Res'}->code!=RestCodes::HTTP_200)
						return ${$field->linkedTable.'Res'};
					foreach(${$field->linkedTable.'Res'}->content->table->fields as $tField)
						{
						if($this->queryParams->orderby=='linked_'.$field->linkedTable.'_'.$tField->name&&strpos($tField->name,'joined_')!==0&&strpos($tField->name,'refered_')!==0)
							{
							$linkedOrderby=$field->linkedTable.'.'.$tField->name;
							$hasLinkedConditions=true;
							$fieldFound=true;
							}
						}
					}
				// Main request fieldsearches
				else if($this->queryParams->orderby==$field->name)
					{
					$mainOrderby=$this->queryParams->orderby;
					$fieldFound=true;
					}
				}
			if(!$fieldFound)
				throw new RestException(RestCodes::HTTP_400,'Entered a bad orderby field name ('.$this->queryParams->orderby.').');
			}
		// Fieldsearchop
		if($this->queryParams->fieldsearchor&&$this->queryParams->fieldsearch->count()<2)
			throw new RestException(RestCodes::HTTP_400,'The fieldsearchop parameter must be used with at least 2 fieldsearches.');
		// Processing field searches
		if(isset($this->queryParams->fieldsearch)&&$this->queryParams->fieldsearch)
			{
			for($i=$this->queryParams->fieldsearch->count()-1; $i>=0; $i--)
				{
				if(!(isset($this->queryParams->fieldsearchval[$i])&&isset($this->queryParams->fieldsearchop[$i])))
					throw new RestException(RestCodes::HTTP_400,'The fieldsearch parameter must be used with fieldsearchval and fieldsearchop values (num:'.$i.', field:'.$this->queryParams->fieldsearch[$i].'.');
				$fieldFound=false;
				foreach($this->_schema->table->fields as $field)
					{
					if($this->queryParams->fieldsearchop[$i]==self::OP_IS&&$this->queryParams->fieldsearchval[$i]!='null'&&$this->queryParams->fieldsearchval[$i]!='notnull')
						throw new RestException(RestCodes::HTTP_400,'Bad fieldsearchval for this fieldsearchop (num:'.$i.', field:'.$this->queryParams->fieldsearch[$i].'.');
					// Joined fieldsearches
					if(strpos($field->name,'joined_')===0&&$this->queryParams->fieldsearch[$i]=='joined_'.$field->linkedTable)
						{
						$hasJoinedConditions=true;
						${$field->linkedTable.'HasJoinedConditions'}=true;
						$fieldFound=true;
						switch($this->queryParams->fieldsearchop[$i])
							{
							case self::OP_EQUAL:
								$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->joinTable.'.'.$field->linkedTable.'_id="' .$this->queryParams->fieldsearchval[$i] . '"';
								break;
							case self::OP_NOTEQUAL:
								$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->joinTable.'.'.$field->linkedTable.'_id!="' .$this->queryParams->fieldsearchval[$i] . '"';
								break;
							case self::OP_SUPEQUAL:
								$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->joinTable.'.'.$field->linkedTable.'_id>="' .$this->queryParams->fieldsearchval[$i] . '"';
								break;
							case self::OP_SUPERIOR:
								$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->joinTable.'.'.$field->linkedTable.'_id>"' .$this->queryParams->fieldsearchval[$i] . '"';
								break;
							case self::OP_INFEQUAL:
								$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->joinTable.'.'.$field->linkedTable.'_id<="' .$this->queryParams->fieldsearchval[$i] . '"';
								break;
							case self::OP_INFERIOR:
								$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->joinTable.'.'.$field->linkedTable.'_id<"' .$this->queryParams->fieldsearchval[$i] . '"';
								break;
							default:
								throw new RestException(RestCodes::HTTP_400,'Entered a bad fieldsearchop value (num:'.$i.', fieldsearchop:'.$this->queryParams->fieldsearchop[$i].').');
								break;
							}
						}
					// Refered fields
					else if(strpos($field->name,'refered_')===0&&strpos($this->queryParams->fieldsearch[$i],'refered_'.$field->linkedTable.'_')===0)
						{
						${$field->linkedTable.'Res'}=new RestResource(new RestRequest(RestMethods::GET,'/db/'.$this->request->database.'/'.$field->linkedTable.'.dat'));
						${$field->linkedTable.'Res'}=${$field->linkedTable.'Res'}->getResponse();
						if(${$field->linkedTable.'Res'}->code!=RestCodes::HTTP_200)
							return ${$field->linkedTable.'Res'};
						foreach(${$field->linkedTable.'Res'}->content->table->fields as $tField)
							{
							if($this->queryParams->fieldsearch[$i]=='refered_'.$field->linkedTable.'_'.$tField->name&&strpos($tField->name,'joined_')!==0&&strpos($tField->name,'refered_')!==0)
								{
								$hasReferedConditions=true;
								$fieldFound=true;
								switch($this->queryParams->fieldsearchop[$i])
									{
									case self::OP_EQUAL:
										$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.'.$tField->name.'="' .$this->queryParams->fieldsearchval[$i] . '"';
										break;
									case self::OP_NOTEQUAL:
										$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.'.$tField->name.'!="' .$this->queryParams->fieldsearchval[$i] . '"';
										break;
									case self::OP_SUPEQUAL:
										$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.'.$tField->name.'>="' .$this->queryParams->fieldsearchval[$i] . '"';
										break;
									case self::OP_SUPERIOR:
										$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.'.$tField->name.'>"' .$this->queryParams->fieldsearchval[$i] . '"';
										break;
									case self::OP_INFEQUAL:
										$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.'.$tField->name.'<="' .$this->queryParams->fieldsearchval[$i] . '"';
										break;
									case self::OP_INFERIOR:
										$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.'.$tField->name.'<"' .$this->queryParams->fieldsearchval[$i] . '"';
										break;
									case self::OP_LIKE:
										$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.'.$tField->name.' LIKE "%' .$this->queryParams->fieldsearchval[$i] . '%"';
										break;
									case self::OP_ENDLIKE:
										$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.'.$tField->name.' LIKE "%' .$this->queryParams->fieldsearchval[$i] . '"';
										break;
									case self::OP_STARTLIKE:
										$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.'.$tField->name.' LIKE "' .$this->queryParams->fieldsearchval[$i] . '%"';
										break;
									case self::OP_IS:
										$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.'.$tField->name.' IS '.($this->queryParams->fieldsearchval[$i]=='null'?'NULL':'NOT NULL');
										break;
									default:
										throw new RestException(RestCodes::HTTP_400,'Entered a bad fieldsearchop value (num:'.$i.', fieldsearchop:'.$this->queryParams->fieldsearchop[$i].').');
										break;
									}
								}
							}
						}
					else if(strpos($field->name,'refered_')===0&&strpos($this->queryParams->fieldsearch[$i],'refered_'.$field->linkedTable)===0)
						{
						// Should be removed
						$hasReferedConditions=true;
						$fieldFound=true;
						switch($this->queryParams->fieldsearchop[$i])
							{
							case self::OP_EQUAL:
								$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.id="' .$this->queryParams->fieldsearchval[$i] . '"';
								break;
							case self::OP_NOTEQUAL:
								$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.id!="' .$this->queryParams->fieldsearchval[$i] . '"';
								break;
							case self::OP_SUPEQUAL:
								$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.id>="' .$this->queryParams->fieldsearchval[$i] . '"';
								break;
							case self::OP_SUPERIOR:
								$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.id>"' .$this->queryParams->fieldsearchval[$i] . '"';
								break;
							case self::OP_INFEQUAL:
								$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.id<="' .$this->queryParams->fieldsearchval[$i] . '"';
								break;
							case self::OP_INFERIOR:
								$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.id<"' .$this->queryParams->fieldsearchval[$i] . '"';
								break;
							default:
								throw new RestException(RestCodes::HTTP_400,'Entered a bad fieldsearchop value (num:'.$i.', fieldsearchop:'.$this->queryParams->fieldsearchop[$i].').');
								break;
							}
						}
					// Linked fields
					else if(isset($field->linkedTable)&&strpos($this->queryParams->fieldsearch[$i],'linked_'.$field->linkedTable.'_')===0)
						{
						${$field->linkedTable.'Res'}=new RestResource(new RestRequest(RestMethods::GET,'/db/'.$this->request->database.'/'.$field->linkedTable.'.dat'));
						${$field->linkedTable.'Res'}=${$field->linkedTable.'Res'}->getResponse();
						if(${$field->linkedTable.'Res'}->code!=RestCodes::HTTP_200)
							return ${$field->linkedTable.'Res'};
						foreach(${$field->linkedTable.'Res'}->content->table->fields as $tField)
							{
							if($this->queryParams->fieldsearch[$i]=='linked_'.$field->linkedTable.'_'.$tField->name&&strpos($tField->name,'joined_')!==0&&strpos($tField->name,'refered_')!==0)
								{
								$hasLinkedConditions=true;
								$fieldFound=true;
								switch($this->queryParams->fieldsearchop[$i])
									{
									case self::OP_EQUAL:
										$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.'.$tField->name.'="' .$this->queryParams->fieldsearchval[$i] . '"';
										break;
									case self::OP_NOTEQUAL:
										$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.'.$tField->name.'!="' .$this->queryParams->fieldsearchval[$i] . '"';
										break;
									case self::OP_SUPEQUAL:
										$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.'.$tField->name.'>="' .$this->queryParams->fieldsearchval[$i] . '"';
										break;
									case self::OP_SUPERIOR:
										$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.'.$tField->name.'>"' .$this->queryParams->fieldsearchval[$i] . '"';
										break;
									case self::OP_INFEQUAL:
										$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.'.$tField->name.'<="' .$this->queryParams->fieldsearchval[$i] . '"';
										break;
									case self::OP_INFERIOR:
										$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.'.$tField->name.'<"' .$this->queryParams->fieldsearchval[$i] . '"';
										break;
									case self::OP_LIKE:
										$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.'.$tField->name.' LIKE "%' .$this->queryParams->fieldsearchval[$i] . '%"';
										break;
									case self::OP_ENDLIKE:
										$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.'.$tField->name.' LIKE "%' .$this->queryParams->fieldsearchval[$i] . '"';
										break;
									case self::OP_STARTLIKE:
										$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.'.$tField->name.' LIKE "' .$this->queryParams->fieldsearchval[$i] . '%"';
										break;
									case self::OP_IS:
										$sqlWhereConditions.=($sqlWhereConditions?' '.($this->queryParams->fieldsearchor?'OR':'AND'):'').' '.$field->linkedTable.'.'.$tField->name.' IS '.($this->queryParams->fieldsearchval[$i]=='null'?'NULL':'NOT NULL');
										break;
									default:
										throw new RestException(RestCodes::HTTP_400,'Entered a bad fieldsearchop value (num:'.$i.', fieldsearchop:'.$this->queryParams->fieldsearchop[$i].').');
										break;
									}
								}
							}
						}
					// Main request fieldsearches
					else if($this->queryParams->fieldsearch[$i]==$field->name)
						{
						$fieldFound=true;
						switch($this->queryParams->fieldsearchop[$i])
							{
							case self::OP_EQUAL:
								$mainRequestSearch.=' '.($mainRequestSearch?($this->queryParams->fieldsearchor?'OR':'AND'):'WHERE') . ' ' . $this->request->table.'.' . $this->queryParams->fieldsearch[$i] . '="' .$this->queryParams->fieldsearchval[$i] . '"';
								break;
							case self::OP_NOTEQUAL:
								$mainRequestSearch.=' '.($mainRequestSearch?($this->queryParams->fieldsearchor?'OR':'AND'):'WHERE') . ' ' . $this->request->table.'.' . $this->queryParams->fieldsearch[$i] . '!="' .$this->queryParams->fieldsearchval[$i] . '"';
								break;
							case self::OP_SUPEQUAL:
								$mainRequestSearch.=' '.($mainRequestSearch?($this->queryParams->fieldsearchor?'OR':'AND'):'WHERE') . ' ' . $this->request->table.'.' . $this->queryParams->fieldsearch[$i] . '>="' .$this->queryParams->fieldsearchval[$i] . '"';
								break;
							case self::OP_SUPERIOR:
								$mainRequestSearch.=' '.($mainRequestSearch?($this->queryParams->fieldsearchor?'OR':'AND'):'WHERE') . ' ' . $this->request->table.'.' . $this->queryParams->fieldsearch[$i] . '>"' .$this->queryParams->fieldsearchval[$i] . '"';
								break;
							case self::OP_INFEQUAL:
								$mainRequestSearch.=' '.($mainRequestSearch?($this->queryParams->fieldsearchor?'OR':'AND'):'WHERE') . ' ' . $this->request->table.'.' . $this->queryParams->fieldsearch[$i] . '<="' .$this->queryParams->fieldsearchval[$i] . '"';
								break;
							case self::OP_INFERIOR:
								$mainRequestSearch.=' '.($mainRequestSearch?($this->queryParams->fieldsearchor?'OR':'AND'):'WHERE') . ' ' . $this->request->table.'.' . $this->queryParams->fieldsearch[$i] . '<"' .$this->queryParams->fieldsearchval[$i] . '"';
								break;
							case self::OP_LIKE:
								$mainRequestSearch.=' '.($mainRequestSearch?($this->queryParams->fieldsearchor?'OR':'AND'):'WHERE') . ' ' . $this->request->table.'.' . $this->queryParams->fieldsearch[$i] . ' LIKE "%' .$this->queryParams->fieldsearchval[$i] . '%"';
								break;
							case self::OP_ENDLIKE:
								$mainRequestSearch.=' '.($mainRequestSearch?($this->queryParams->fieldsearchor?'OR':'AND'):'WHERE') . ' ' . $this->request->table.'.' . $this->queryParams->fieldsearch[$i] . ' LIKE "%' .$this->queryParams->fieldsearchval[$i] . '"';
								break;
							case self::OP_STARTLIKE:
								$mainRequestSearch.=' '.($mainRequestSearch?($this->queryParams->fieldsearchor?'OR':'AND'):'WHERE') . ' ' . $this->request->table.'.' . $this->queryParams->fieldsearch[$i] . ' LIKE "' .$this->queryParams->fieldsearchval[$i] . '%"';
								break;
							case self::OP_IS:
								$mainRequestSearch.=' '.($mainRequestSearch?($this->queryParams->fieldsearchor?'OR':'AND'):'WHERE') . ' ' . $this->request->table.'.' . $this->queryParams->fieldsearch[$i] .' IS '.($this->queryParams->fieldsearchval[$i]=='null'?'NULL':'NOT NULL');
								break;
							default:
								throw new RestException(RestCodes::HTTP_400,'Entered a bad fieldsearchop value (num:'.$i.', fieldsearchop:'.$this->queryParams->fieldsearchop[$i].').');
								break;
							}
						}
					}
				if(!$fieldFound)
					throw new RestException(RestCodes::HTTP_400,'Entered a bad fieldsearch name (num:'.$i.', fieldsearch:'.$this->queryParams->fieldsearch[$i].').');
				}
			}
		// Creating the main request
		$mainRequest='SELECT '.($this->queryParams->mode=='count'&&!$hasLinkedConditions?'id':$this->request->table.'.*');
		$mainRequest.=' FROM ' . $this->request->table;
		$mainRequest.=$mainRequestSearch;
		// Setting main request limit
		if($this->queryParams->mode!='count')
			{
			$mainRequest.=($mainOrderby?' ORDER BY '.$this->queryParams->orderby.' '.strtoupper($this->queryParams->dir):'').($this->queryParams->limit&&!($hasJoinedConditions||$hasReferedConditions||$hasLinkedConditions)?' LIMIT '.$this->queryParams->start.', '.$this->queryParams->limit.'':'');
			}
		else
			{
			for($i=1; $i<6; $i++)
				{
				if(isset($this->queryParams->{$this::$drvInf->methods->get->queryParams[$i]->name})
					&&(((isset($this::$drvInf->methods->get->queryParams[$i]->multiple)&&$this::$drvInf->methods->get->queryParams[$i]->multiple==true))
						||$this->queryParams->{$this::$drvInf->methods->get->queryParams[$i]->name}!=$this::$drvInf->methods->get->queryParams[$i]->value))
					throw new RestException(RestCodes::HTTP_400,'The count mode doesn\'t accept '.$this::$drvInf->methods->get->queryParams[$i]->name.' parameter.');
				}
			}
		// Setting table joins
		$sqlFields='';
		$sqlJoins='';
		if(isset($this->_schema->table->linkedTables))
			{
			foreach($this->_schema->table->linkedTables as $table)
				{
				if(!isset(${$table.'Res'}))
					{
					${$table.'Res'}=new RestResource(new RestRequest(RestMethods::GET,'/db/'.$this->request->database.'/'.$table.'.dat'));
					${$table.'Res'}=${$table.'Res'}->getResponse();
					if(${$table.'Res'}->code!=RestCodes::HTTP_200)
						return ${$table.'Res'};
					}
				$sqlJoinConditions='';
				// Finding main table fields joined with current linkedTable
				foreach($this->_schema->table->fields as $field)
					{
					// Joined fields
					if((($this->queryParams->mode=='join'||$this->queryParams->mode=='fulljoin')&&($this->queryParams->joinMode=='all'||$this->queryParams->joinMode=='joined')&&((!isset($this->queryParams->joinField))||(!$this->queryParams->joinField->count())||($this->queryParams->joinField->has($field->name))))||(isset($field->linkedTable,${$field->linkedTable.'HasJoinedConditions'})&&${$field->linkedTable.'HasJoinedConditions'}))
						{
						if(isset($field->joinTable)&&$field->linkedTable==$table)
							{
							$sqlFields.=', '.$field->joinTable.'.'.$field->linkedTable.'_id as '.$field->linkedTable.'_join, '.$field->joinTable.'.id as '.$field->linkedTable.'_join_id';
							if($this->queryParams->mode=='fulljoin')
								$sqlJoinConditions.=($sqlJoinConditions?' OR':'').' '.$field->linkedTable.'.id='.$field->joinTable.'.'.$field->linkedTable.'_id';
							$sqlJoins.=' LEFT JOIN '.$field->joinTable.' ON '.$field->joinTable.'.'.$this->request->table.'_id=temp_'.$this->request->table.'.id';
							}
						}
					// Refered fields
					if((($this->queryParams->mode=='join'||$this->queryParams->mode=='fulljoin')&&($this->queryParams->joinMode=='all'||$this->queryParams->joinMode=='refered'))||$hasReferedConditions)
						{
						if(isset($field->referedField)&&$field->linkedTable==$table)
							{
							$sqlFields.=', '.$field->linkedTable.'.id as '.$field->linkedTable.'_join';
							$sqlJoinConditions.=($sqlJoinConditions?' OR':'').' '.$table.'.'.$field->linkedField.'=temp_'.$this->request->table.'.id';
							}
						}
					}
				// Linked fields
				if($this->queryParams->mode=='extend'||$this->queryParams->mode=='join'||$this->queryParams->mode=='fulljoin'||$hasLinkedConditions)
					{
					foreach($this->_schema->table->fields as $field)
						{
						if(isset($field->linkedTable)&&$field->linkedTable==$table&&!(strpos($field->name,'joined_')===0||strpos($field->name,'refered_')===0))
								{
								$sqlJoinConditions.=($sqlJoinConditions?' OR':'').' '.$table.'.id=temp_'.$this->request->table.'.'.$field->name;
								}
						}
					}
				if($sqlJoinConditions)
					{
					$sqlJoins.=' LEFT JOIN '.$table.' ON'.$sqlJoinConditions;
					foreach(${$table.'Res'}->content->table->fields as $field)
						{
						if(!(strpos($field->name,'joined_')===0||strpos($field->name,'refered_')===0))
							{
							$sqlFields.=', '.$table.'.'.$field->name.' as join_'.$table.'_'.$field->name;
							}
						}
					}
				}
			}

		$sqlRequest='SELECT '.($this->queryParams->mode=='count'?'DISTINCT temp_'.$this->request->table.'.id':'temp_'.$this->request->table.'.*'.$sqlFields).' FROM ('.$mainRequest.') temp_'.$this->request->table . $sqlJoins	. ($sqlWhereConditions?' WHERE'.$sqlWhereConditions:'').($linkedOrderby?' ORDER BY '.$linkedOrderby.' '.strtoupper($this->queryParams->dir):($mainOrderby?' ORDER BY temp_'.$this->request->table.'.'.$this->queryParams->orderby.' '.strtoupper($this->queryParams->dir):''));
		// Setting request sort and limit
		if($this->queryParams->mode!='count'&&($hasJoinedConditions||$hasReferedConditions||$hasLinkedConditions))
			{
			$sqlRequest.=($this->queryParams->limit?' LIMIT '.$this->queryParams->start.', '.$this->queryParams->limit.'':'');
			}
		$query=$this->core->db->query($sqlRequest);
		//echo $sqlRequest; exit;
			
		$response=new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'application/internal')
			);
		
		$response->content=new stdClass();
		$response->content->entries=new MergeArrayObject();

		if($this->core->db->numRows())
			{
			if($this->queryParams->mode=='count')
				{
				$response->content=new stdClass();
				$response->content->count=$this->core->db->numRows();
				}
			else
				{
				while ($row = $this->core->db->fetchArray($query))
					{
					$looped=false;
					if(isset($entry)&&$entry->id==$row['id'])
						{
						$looped=true;
						}
					else
						{
						$entry=new stdClass();
						$entry->label='';
						}
					foreach($this->_schema->table->fields as $field)
						{
						// Reading joined or refered fields values
						if(strpos($field->name,'joined_')===0||strpos($field->name,'refered_')===0)
							{
							if(($this->queryParams->mode=='join'||$this->queryParams->mode=='fulljoin')&&((strpos($field->name,'joined_')===0&&($this->queryParams->joinMode=='all'||$this->queryParams->joinMode=='joined'))||(strpos($field->name,'refered_')===0&&($this->queryParams->joinMode=='all'||$this->queryParams->joinMode=='refered')))&&(isset($row[$field->linkedTable.'_join_id'])||isset($row[$field->linkedTable.'_join'])))
								{
								if(!isset($entry->{$field->name}))
									$entry->{$field->name}=new MergeArrayObject();
								$isIn=false;
								foreach($entry->{$field->name} as $lField)
									{
									if((isset($lField->join_id,$row[$field->linkedTable.'_join_id'])&&$lField->join_id==$row[$field->linkedTable.'_join_id'])||(isset($row[$field->linkedTable.'_join'])&&$lField->id==$row[$field->linkedTable.'_join']))
										$isIn=true;
									}
								if(!$isIn)
									{
									$lField=new stdClass();
									if(isset($row[$field->linkedTable.'_join_id']))
										{
										$lField->join_id=$row[$field->linkedTable.'_join_id'];
										}
									$lField->id=(isset($row[$field->linkedTable.'_join'])?$row[$field->linkedTable.'_join']:'');
									if($this->queryParams->mode=='fulljoin')
										{
										$lField->label='';
										foreach(${$field->linkedTable.'Res'}->content->table->fields as $field2)
											{
											if($field2->name!='password'&&!(strpos($field2->name,'joined_')===0||strpos($field2->name,'refered_')===0))
												$lField->{$field2->name}=$row['join_'.$field->linkedTable.'_'.$field2->name];
											}
										if(${$field->linkedTable.'Res'}->content->table->nameField)
											$lField->name=$lField->{${$field->linkedTable.'Res'}->content->table->nameField};
										if(isset(${$field->linkedTable.'Res'}->content->table->labelFields)&&${$field->linkedTable.'Res'}->content->table->labelFields->count())
											{
											foreach(${$field->linkedTable.'Res'}->content->table->labelFields as $field2)
												{
												if($field2!='label')
													$lField->label.=($lField->label?' ':'').$lField->{$field2};
												}
											}
										}
									$entry->{$field->name}->append($lField);
									}
								}
							}
						// Multiple main fields
						else if($this->queryParams->mode!='light'&&isset($field->multiple)&&$field->multiple&&!$looped)
							{
							$entry->{$field->name} = new MergeArrayObject();
							foreach(explode(',',$row[$field->name]) as $val)
								$entry->{$field->name}->append($val);
							}
						else if($field->name!='password'&&($this->queryParams->mode!='light'||$field->name=='label'||$field->name=='id'||$field->name=='name'))
							{
							// Linked fields
							if(isset($field->linkedTable)&&$field->linkedTable)
								{
								$entry->{$field->name} = $row[$field->name];
								if($this->queryParams->mode=='extend'||$this->queryParams->mode=='join'||$this->queryParams->mode=='fulljoin')
									{
									if($row['join_'.$field->linkedTable.'_id']==$row[$field->name])
										{
										$entry->{$field->name.'_label'}='';
										foreach(${$field->linkedTable.'Res'}->content->table->fields as $field2)
											{
											if($field2->name!='password'&&!(strpos($field2->name,'joined_')===0||strpos($field2->name,'refered_')===0))
												$entry->{$field->name.'_'.$field2->name} = $row['join_'.$field->linkedTable.'_'.$field2->name];
											}
										if((!$entry->{$field->name.'_label'})&&isset(${$field->linkedTable.'Res'}->content->table->labelFields))
										foreach(${$field->linkedTable.'Res'}->content->table->labelFields as $field2)
											{
											if($field2)
												$entry->{$field->name.'_label'}.=($entry->{$field->name.'_label'}?' ':'').$entry->{$field->name.'_'.$field2};
											}
										}
									}
								}
							// Main fields
							else if(!$looped)
								$entry->{$field->name} = $row[$field->name];
							}
						}
					if(!$looped)
						{
						if($this->_schema->table->nameField)
							$entry->name=$row[$this->_schema->table->nameField];
						if(isset($this->_schema->table->labelFields)&&$this->_schema->table->labelFields->count())
							{
							foreach($this->_schema->table->labelFields as $field)
								{
								if($field!='label')
									$entry->label.=($entry->label?' ':'').$row[$field];
								}
							}
						if($this->queryParams->fileMode!='none')
							{
							$res=new RestResource(new RestRequest(RestMethods::GET,'/fsi/db/'.$this->request->database.'/'.$this->request->table.'/'.$entry->id.'/files.dat?mode=light'));
							$res=$res->getResponse();
							if($res->code==RestCodes::HTTP_200)
								{
								if($this->queryParams->fileMode=='join')
									$entry->attached_files=$res->content->files;
								else if($this->queryParams->fileMode=='count')
									$entry->num_files=$res->content->files->count();
								else
									throw new RestException(RestCodes::HTTP_400,'Given a bad fileMode ('.$this->queryParams->fileMode.').');
								}
							$response->appendToHeader('X-Rest-Uncacheback','/fs/db/'.$this->request->database.'/'.$this->request->table.'/'.$entry->id.'/files/');
							}
						$response->content->entries->append($entry);
						}
					}
				$this->core->db->freeResult();
				}
			}

		return $response;
		}
	}
RestDbEntriesDriver::$drvInf=RestDbEntriesDriver::getDrvInf();