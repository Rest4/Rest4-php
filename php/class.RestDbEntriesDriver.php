<?php
class RestDbEntriesDriver extends RestVarsDriver
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
		}
	static function getDrvInf($methods=0)
		{
		$drvInf=parent::getDrvInf(RestMethods::GET);
		$drvInf->name='DB:Database Entries Driver';
		$drvInf->description='List each entries of a table. Apply filters, sorting and searchs.';
		$drvInf->usage='/db/database/table/list'.$drvInf->usage
			.'?mode=(count|)'
			.'&field=([a-zA-Z0-9]+)'
			.'&files=(count|list|include)'
			.'&start=([0-9]+)&limit=([0-9]+)'
			.'&orderby=([a-z0-9]+)&dir=desc'
			.'&fieldsearch=([a-zA-Z0-9]+)'
			.'&fieldsearchval=([a-zA-Z0-9]+)'
			.'&fieldsearchop=eq|noteq|supeq|sup|infeq|inf|like|elike|slike|is';
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='mode';
		$drvInf->methods->get->queryParams[0]->values=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]->values[0]=
			$drvInf->methods->get->queryParams[0]->value='normal';
		$drvInf->methods->get->queryParams[0]->values[1]='count';
		$drvInf->methods->get->queryParams[0]->values[2]='light';
		$drvInf->methods->get->queryParams[1]=new stdClass();
		$drvInf->methods->get->queryParams[1]->name='field';
		$drvInf->methods->get->queryParams[1]->filter='cdata';
		$drvInf->methods->get->queryParams[1]->multiple=true;
		$drvInf->methods->get->queryParams[2]=new stdClass();
		$drvInf->methods->get->queryParams[2]->name='files';
		$drvInf->methods->get->queryParams[2]->values=new MergeArrayObject();
		$drvInf->methods->get->queryParams[2]->values[0]=
			$drvInf->methods->get->queryParams[2]->value='ignore';
		$drvInf->methods->get->queryParams[2]->values[1]='count';
		$drvInf->methods->get->queryParams[2]->values[2]='list';
		$drvInf->methods->get->queryParams[2]->values[3]='include';
		$drvInf->methods->get->queryParams[3]=new stdClass();
		$drvInf->methods->get->queryParams[3]->name='start';
		$drvInf->methods->get->queryParams[3]->type='number';
		$drvInf->methods->get->queryParams[3]->filter='int';
		$drvInf->methods->get->queryParams[3]->value=
			$drvInf->methods->get->queryParams[3]->min=0;
		$drvInf->methods->get->queryParams[4]=new stdClass();
		$drvInf->methods->get->queryParams[4]->name='limit';
		$drvInf->methods->get->queryParams[4]->type='number';
		$drvInf->methods->get->queryParams[4]->filter='int';
		$drvInf->methods->get->queryParams[4]->value='10';
		$drvInf->methods->get->queryParams[4]->min=0;
		$drvInf->methods->get->queryParams[5]=new stdClass();
		$drvInf->methods->get->queryParams[5]->name='orderby';
		$drvInf->methods->get->queryParams[5]->multiple=true;
		$drvInf->methods->get->queryParams[5]->filter='cdata';
		$drvInf->methods->get->queryParams[5]->orderless=true;
		$drvInf->methods->get->queryParams[6]=new stdClass();
		$drvInf->methods->get->queryParams[6]->name='dir';
		$drvInf->methods->get->queryParams[6]->multiple=true;
		$drvInf->methods->get->queryParams[6]->orderless=true;
		$drvInf->methods->get->queryParams[6]->values=new MergeArrayObject();
		$drvInf->methods->get->queryParams[6]->values[0]='asc';
		$drvInf->methods->get->queryParams[6]->values[1]='desc';
		$drvInf->methods->get->queryParams[7]=new stdClass();
		$drvInf->methods->get->queryParams[7]->name='fieldsearch';
		$drvInf->methods->get->queryParams[7]->filter='cdata';
		$drvInf->methods->get->queryParams[7]->multiple=true;
		$drvInf->methods->get->queryParams[8]=new stdClass();
		$drvInf->methods->get->queryParams[8]->name='fieldsearchval';
		$drvInf->methods->get->queryParams[8]->filter='cdata';
		$drvInf->methods->get->queryParams[8]->multiple=true;
		$drvInf->methods->get->queryParams[8]->orderless=true;
		$drvInf->methods->get->queryParams[9]=new stdClass();
		$drvInf->methods->get->queryParams[9]->name='fieldsearchop';
		$drvInf->methods->get->queryParams[9]->multiple=true;
		$drvInf->methods->get->queryParams[9]->orderless=true;
		$drvInf->methods->get->queryParams[10]=new stdClass();
		$drvInf->methods->get->queryParams[10]->name='fieldsearchor';
		$drvInf->methods->get->queryParams[10]->value='';
		return $drvInf;
		}
	function head()
		{
		// If no RestException throwed before, the database exists
		return new RestResponse(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	function get()
		{
		// Retrieving main table schema
		$schema=RestDbHelper::getTableSchema($this->request->database,
			$this->request->table);
		// Constraints schemas
		$contraintsSchemas=new stdClass();
		$contraintsSchemas->{$this->request->table}=$schema;
		// Request clauses build vars
		$orderbyClause='';
		$subOrderbyClause='';
		$mainReqFields=array();
		$suscribedJoins=array();
		// Preparing the response
		$response=new RestVarsResponse(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		// Checking mode count parameters
		if($this->queryParams->mode=='count')
			{
			for($i=1; $i<7; $i++)
				{
				if(isset($this->queryParams->{$this::$drvInf->methods->get->queryParams[$i]->name})
					&&(((isset($this::$drvInf->methods->get->queryParams[$i]->multiple)
						&&$this::$drvInf->methods->get->queryParams[$i]->multiple==true))
						||$this->queryParams->{$this::$drvInf->methods->get->queryParams[$i]->name}!=
							$this::$drvInf->methods->get->queryParams[$i]->value))
					throw new RestException(RestCodes::HTTP_400,
						'The count mode doesn\'t accept '.$this::$drvInf->methods->get->queryParams[$i]->name
							.' parameter.');
				}
			}
		// Fieldsearchop is usable with at least 2 fieldsearches
		if($this->queryParams->fieldsearchor&&$this->queryParams->fieldsearch->count()<2)
			{
			throw new RestException(RestCodes::HTTP_400, 'The fieldsearchop'
				.' parameter must be used with at least 2 fieldsearches.');
			}
		// Processing order by clause
		// There must be as much dir than orderby params
		if(isset($this->queryParams->orderby)||isset($this->queryParams->dir))
			{
			if((!isset($this->queryParams->orderby,$this->queryParams->dir))
				||$this->queryParams->orderby->count()!=$this->queryParams->dir->count())
				{
				throw new RestException(RestCodes::HTTP_400,
					'Orderby and dir parameters are linked, give as much orderby params '
						.' than dir params.');
				}
			// Checking orderby specified field existance
			for($i=$this->queryParams->orderby->count()-1; $i>=0; $i--)
				{
				$exists=false;
				// Searching
				foreach($schema->table->fields as $field)
					{
					// Table fields
					if($field->name==$this->queryParams->orderby[$i])
						{
						$this->appendMainReqField($mainReqFields,
							$this->queryParams->orderby[$i]);
						$subOrderbyClause.=($subOrderbyClause?', '."\n\t":'')
													.$this->request->table
													.'.'.$this->queryParams->orderby[$i]
													.' '.strtoupper($this->queryParams->dir[$i]);
						$orderbyClause.=($orderbyClause?', '."\n\t":'')
													.'temp_'.$this->request->table
													.'.'.$this->queryParams->orderby[$i]
													.' '.strtoupper($this->queryParams->dir[$i]);
						$exists=true; break;
						}
					// Constraints fields
					// Joins : fieldJoinsTableField.field
					if(isset($field->joins)&&0===strpos($this->queryParams->orderby[$i],
							$field->name.'Joins'))
						{
						// Looking for the right join constraint
						foreach($field->joins as $join)
							{
							if(0===strpos($this->queryParams->orderby[$i],$join->name))
								{
								// Retrieving the constraint schema if not yet retrieved
								if(!isset($contraintsSchemas->{$join->table}))
									{
									$contraintsSchemas->{$join->table}=
										RestDbHelper::getTableSchema(
											$this->request->database, $join->table);
									}
								foreach($contraintsSchemas->{$join->table}->table->fields as $cField)
									{
									if($this->queryParams->orderby[$i]==$join->name
											.'.'.$cField->name)
										{
										// Suscribe
										array_push($suscribedJoins, $join->name);
										// Looking for the field in that schemas
										$orderbyClause.=($orderbyClause?', '."\n\t":'')
																	.$join->name.'.'.$cField->name
																	.' '.strtoupper($this->queryParams->dir[$i]);
										$exists=true; break 3;
										}
									}
								}
							}
						}
					// References : fieldRefsTableField.field
					if(isset($field->references)&&0===strpos($this->queryParams->orderby[$i],
							$field->name.'References'))
						{
						// Looking for the right join constraint
						foreach($field->references as $reference)
							{
							if(0===strpos($this->queryParams->orderby[$i], $reference->name))
								{
								// The field cannot be the referring field
								if($this->queryParams->orderby[$i]==$reference->name
									.'.'.$reference->field)
									{
									throw new RestException(RestCodes::HTTP_400,
										'The orderby field cannot be the linked field, simply specify'
										.' '.$field->name.' as an orderby parameter.');
									}
								// Retrieving the constraint schema if not yet retrieved
								if(!isset($contraintsSchemas->{$reference->table}))
									{
									$contraintsSchemas->{$reference->table}=
										RestDbHelper::getTableSchema(
											$this->request->database, $reference->table);
									}
								foreach($contraintsSchemas->{$reference->table}->table->fields as $cField)
									{
									if($this->queryParams->orderby[$i]===$reference->name
										.'.'.$cField->name)
										{
										// Suscribe
										array_push($suscribedJoins,$reference->name);
										// Looking for the field in that schemas
										$orderbyClause.=($orderbyClause?', '."\n\t":'')
											.$reference->name.'.'.$cField->name
											.' '.strtoupper($this->queryParams->dir[$i]);
										$exists=true; break 3;
										}
									}
								}
							}
						}
					// Links : fieldLinkTableField.field
					if(isset($field->linkTo,$field->linkTo->table)
						&&0===strpos($this->queryParams->orderby[$i], $field->name.'Link'))
						{
						// The field cannot be the linked field
						if($this->queryParams->orderby[$i]==$field->linkTo->name
							.'.'.$field->linkTo->field)
							{
							throw new RestException(RestCodes::HTTP_400,
								'The orderby field cannot be the linked field, simply specify'
								.' '.$field->name.' as an orderby parameter.');
							}
						// Retrieving the constraint schema if not yet retrieved
						if(!isset($contraintsSchemas->{$field->linkTo->table}))
							{
							$contraintsSchemas->{$field->linkTo->table}=RestDbHelper::getTableSchema(
									$this->request->database, $field->linkTo->table);
							}
						foreach($contraintsSchemas->{$field->linkTo->table}->table->fields as $cField)
							{
							if($this->queryParams->orderby[$i]==$field->linkTo->name
								.'.'.$cField->name)
								{
								// Suscribe
								array_push($suscribedJoins, $field->linkTo->name);
								// Looking for the field in that schemas
								$orderbyClause.=($orderbyClause?', '."\n\t":'')
									.$field->linkTo->name.'.'.$cField->name
									.' '.strtoupper($this->queryParams->dir[$i]);
								$exists=true; break 2;
								}
							}
						}
					}
				// If not found, raise a RestException
				if(!$exists)
					{
					throw new RestException(RestCodes::HTTP_400,
						'Entered a bad orderby field ('.$this->queryParams->orderby[$i].').');
					}
				}
			}
		// Prepare fields searches
		$searchClause='';
		$subSearchClause='';
		$searchClausesFields=array();
		$searchJoinsClauses=new stdClass();
		if(isset($this->queryParams->fieldsearch))
		for($i=0, $j=$this->queryParams->fieldsearch->count(); $i<$j; $i++)
			{
			if($this->queryParams->fieldsearchop[$i]==self::OP_IS
				&&$this->queryParams->fieldsearchval[$i]!='null'
				&&$this->queryParams->fieldsearchval[$i]!='notnull')
				{
				throw new RestException(RestCodes::HTTP_400,
					'"is" fieldsearchop only accept null/notnull fieldsearchval ('
					.$this->queryParams->fieldsearch[$i].').');
				}
			$exists=false;
			// Searching
			foreach($schema->table->fields as $field)
				{
				// Table fields
				if($field->name==$this->queryParams->fieldsearch[$i])
					{
					$this->appendMainReqField($mainReqFields, $field->name);
					$subSearchClause.=($subSearchClause?"\n\t"
												.($this->queryParams->fieldsearchor?'OR':'AND'):'')
												.' '.$this->request->table
												.'.'.$this->queryParams->fieldsearch[$i];
					switch($this->queryParams->fieldsearchop[$i])
						{
						case self::OP_EQUAL:
							$subSearchClause.='=';
							break;
						case self::OP_NOTEQUAL:
							$subSearchClause.='!=';
							break;
						case self::OP_SUPEQUAL:
							$subSearchClause.='>=';
							break;
						case self::OP_SUPERIOR:
							$subSearchClause.='>';
							break;
						case self::OP_INFEQUAL:
							$subSearchClause.='<=';
							break;
						case self::OP_INFERIOR:
							$subSearchClause.='<';
							break;
						case self::OP_LIKE:
							$subSearchClause.=' LIKE "%' .$this->queryParams->fieldsearchval[$i] . '%"';
							break;
						case self::OP_ENDLIKE:
							$subSearchClause.=' LIKE "%' .$this->queryParams->fieldsearchval[$i] . '"';
							break;
						case self::OP_STARTLIKE:
							$subSearchClause.=' LIKE "' .$this->queryParams->fieldsearchval[$i] . '%"';
							break;
						case self::OP_IS:
							$subSearchClause.=' IS '
								.($this->queryParams->fieldsearchval[$i]=='null'?'NULL':'NOT NULL');
							break;
						default:
							throw new RestException(RestCodes::HTTP_400,
								'Entered a bad fieldsearchop value (num:'.$i.', fieldsearchop:'
									.$this->queryParams->fieldsearchop[$i].').');
							break;
						}
					switch($this->queryParams->fieldsearchop[$i])
						{
						case self::OP_EQUAL:
						case self::OP_NOTEQUAL:
						case self::OP_SUPEQUAL:
						case self::OP_SUPERIOR:
						case self::OP_INFEQUAL:
						case self::OP_INFERIOR:
							$subSearchClause.='"'.$this->queryParams->fieldsearchval[$i].'"';
							break;
						}
					$exists=true; break;
					}
				// Constraints fields
				// Joins : fieldJoinsTableField
				if(isset($field->joins)&&0===strpos($this->queryParams->fieldsearch[$i],
						$field->name.'Joins'))
					{
					// Looking for the right join constraint
					foreach($field->joins as $join)
						{
						if(0===strpos($this->queryParams->fieldsearch[$i],$join->name))
							{
							// Retrieving the constraint schema if not yet retrieved
							if(!isset($contraintsSchemas->{$join->table}))
								{
								$contraintsSchemas->{$join->table}=
									RestDbHelper::getTableSchema(
										$this->request->database, $join->table);
								}
							foreach($contraintsSchemas->{$join->table}->table->fields as $cField)
								{
								if(0===strpos($this->queryParams->fieldsearch[$i],
									$join->name.'.'.$cField->name))
									{
									// Suscribe
									array_push($suscribedJoins,$join->name);
									// Performing the search
									$searchClause.=($subSearchClause?"\n\t"
												.($this->queryParams->fieldsearchor?'OR':'AND'):'')
												.' '.$join->name.'.'.$cField->name;
									switch($this->queryParams->fieldsearchop[$i])
										{
										case self::OP_EQUAL:
											$searchClause.='=';
											break;
										case self::OP_NOTEQUAL:
											$searchClause.='!=';
											break;
										case self::OP_SUPEQUAL:
											$searchClause.='>=';
											break;
										case self::OP_SUPERIOR:
											$searchClause.='>';
											break;
										case self::OP_INFEQUAL:
											$searchClause.='<=';
											break;
										case self::OP_INFERIOR:
											$searchClause.='<';
											break;
										case self::OP_LIKE:
											$searchClause.=' LIKE "%' .$this->queryParams->fieldsearchval[$i] . '%"';
											break;
										case self::OP_ENDLIKE:
											$searchClause.=' LIKE "%' .$this->queryParams->fieldsearchval[$i] . '"';
											break;
										case self::OP_STARTLIKE:
											$searchClause.=' LIKE "' .$this->queryParams->fieldsearchval[$i] . '%"';
											break;
										case self::OP_IS:
											$searchClause.=' IS '
												.($this->queryParams->fieldsearchval[$i]=='null'?'NULL':'NOT NULL');
											break;
										default:
											throw new RestException(RestCodes::HTTP_400,
												'Entered a bad fieldsearchop value (num:'.$i.', fieldsearchop:'
													.$this->queryParams->fieldsearchop[$i].').');
											break;
										}
									switch($this->queryParams->fieldsearchop[$i])
										{
										case self::OP_EQUAL:
										case self::OP_NOTEQUAL:
										case self::OP_SUPEQUAL:
										case self::OP_SUPERIOR:
										case self::OP_INFEQUAL:
										case self::OP_INFERIOR:
											$searchClause.='"'.$this->queryParams->fieldsearchval[$i].'"';
											break;
										}
									$exists=true; break 3;
									}
								}
							}
						}
					}
				// References : fieldRefsTableField
				if(isset($field->references)&&0===strpos($this->queryParams->fieldsearch[$i],
						$field->name.'Refs'))
					{
					// Looking for the right join constraint
					foreach($field->references as $reference)
						{
						if(0===strpos($this->queryParams->fieldsearch[$i],$reference->name))
							{
							// The field cannot be the referring field
							if($this->queryParams->fieldsearch[$i]==$reference->name
								.'.'.$reference->field)
								{
								throw new RestException(RestCodes::HTTP_400,
									'The searched field cannot be the linked field, simply specify'
									.' '.$field->name.' as a fieldsearch parameter.');
								}
							// Retrieving the constraint schema if not yet retrieved
							if(!isset($contraintsSchemas->{$reference->table}))
								{
								$contraintsSchemas->{$reference->table}=
									RestDbHelper::getTableSchema(
										$this->request->database, $reference->table);
								}
							foreach($contraintsSchemas->{$reference->table}->table->fields as $cField)
								{
								if(0===strpos($this->queryParams->fieldsearch[$i],
									$reference->name.'.'.$cField->name))
									{
									// Suscribe
									array_push($suscribedJoins, $reference->name);
									// Performing the search
									$searchClause.=($searchClause?"\n\t"
												.($this->queryParams->fieldsearchor?'OR':'AND'):'')
												.' '.$reference->name.'.'.$cField->name;
									switch($this->queryParams->fieldsearchop[$i])
										{
										case self::OP_EQUAL:
											$searchClause.='=';
											break;
										case self::OP_NOTEQUAL:
											$searchClause.='!=';
											break;
										case self::OP_SUPEQUAL:
											$searchClause.='>=';
											break;
										case self::OP_SUPERIOR:
											$searchClause.='>';
											break;
										case self::OP_INFEQUAL:
											$searchClause.='<=';
											break;
										case self::OP_INFERIOR:
											$searchClause.='<';
											break;
										case self::OP_LIKE:
											$searchClause.=' LIKE "%' .$this->queryParams->fieldsearchval[$i] . '%"';
											break;
										case self::OP_ENDLIKE:
											$searchClause.=' LIKE "%' .$this->queryParams->fieldsearchval[$i] . '"';
											break;
										case self::OP_STARTLIKE:
											$searchClause.=' LIKE "' .$this->queryParams->fieldsearchval[$i] . '%"';
											break;
										case self::OP_IS:
											$searchClause.=' IS '
												.($this->queryParams->fieldsearchval[$i]=='null'?'NULL':'NOT NULL');
											break;
										default:
											throw new RestException(RestCodes::HTTP_400,
												'Entered a bad fieldsearchop value (num:'.$i.', fieldsearchop:'
													.$this->queryParams->fieldsearchop[$i].').');
											break;
										}
									switch($this->queryParams->fieldsearchop[$i])
										{
										case self::OP_EQUAL:
										case self::OP_NOTEQUAL:
										case self::OP_SUPEQUAL:
										case self::OP_SUPERIOR:
										case self::OP_INFEQUAL:
										case self::OP_INFERIOR:
											$searchClause.='"'.$this->queryParams->fieldsearchval[$i].'"';
											break;
										}
									$exists=true; break 3;
									}
								}
							}
						}
					}
				// Links : fieldLinkTableField
				if(isset($field->linkTo,$field->linkTo->table)
					&&0===strpos($this->queryParams->fieldsearch[$i], xcUtils::camelCase(
						$field->name,'link',$field->linkTo->table,$field->linkTo->field)))
					{
					// The field cannot be the linked field
					if($this->queryParams->fieldsearch[$i]==xcUtils::camelCase(
						$field->name,'link',$field->linkTo->table,$field->linkTo->field)
						.'.'.$field->linkTo->field)
						{
						throw new RestException(RestCodes::HTTP_400,
							'The searched field cannot be the linked field, simply specify'
							.' '.$field->name.' as a fieldsearch parameter.');
						}
					// Retrieving the constraint schema if not yet retrieved
					if(!isset($contraintsSchemas->{$field->linkTo->table}))
						{
						$contraintsSchemas->{$field->linkTo->table}=
							RestDbHelper::getTableSchema(
								$this->request->database, $field->linkTo->table);
						}
					foreach($contraintsSchemas->{$field->linkTo->table}->table->fields as $cField)
						{
						if($this->queryParams->fieldsearch[$i]==$field->linkTo->name
							.'.'.$cField->name)
							{
							// Suscribe
							array_push($suscribedJoins, xcUtils::camelCase($field->name,
								'link',$field->linkTo->table,$field->linkTo->field));
							// Performing the search
							$searchClause.=($searchClause?"\n\t"
										.($this->queryParams->fieldsearchor?'OR':'AND'):'')
										.' '.$field->linkTo->name.'.'.$cField->name;
							switch($this->queryParams->fieldsearchop[$i])

								{
								case self::OP_EQUAL:
									$searchClause.='=';
									break;
								case self::OP_NOTEQUAL:
									$searchClause.='!=';
									break;
								case self::OP_SUPEQUAL:
									$searchClause.='>=';
									break;
								case self::OP_SUPERIOR:
									$searchClause.='>';
									break;
								case self::OP_INFEQUAL:
									$searchClause.='<=';
									break;
								case self::OP_INFERIOR:
									$searchClause.='<';
									break;
								case self::OP_LIKE:
									$searchClause.=' LIKE "%' .$this->queryParams->fieldsearchval[$i] . '%"';
									break;
								case self::OP_ENDLIKE:
									$searchClause.=' LIKE "%' .$this->queryParams->fieldsearchval[$i] . '"';
									break;
								case self::OP_STARTLIKE:
									$searchClause.=' LIKE "' .$this->queryParams->fieldsearchval[$i] . '%"';
									break;
								case self::OP_IS:
									$searchClause.=' IS '
										.($this->queryParams->fieldsearchval[$i]=='null'?'NULL':'NOT NULL');
									break;
								default:
									throw new RestException(RestCodes::HTTP_400,
										'Entered a bad fieldsearchop value (num:'.$i.', fieldsearchop:'
											.$this->queryParams->fieldsearchop[$i].').');
									break;
								}
							switch($this->queryParams->fieldsearchop[$i])
								{
								case self::OP_EQUAL:
								case self::OP_NOTEQUAL:
								case self::OP_SUPEQUAL:
								case self::OP_SUPERIOR:
								case self::OP_INFEQUAL:
								case self::OP_INFERIOR:
									$searchClause.='"'.$this->queryParams->fieldsearchval[$i].'"';
									break;
								}
							$exists=true; break 2;
							}
						}
					}
				}
			// If not found, raise a RestException
			if(!$exists)
				{
				throw new RestException(RestCodes::HTTP_400,
					'Entered a bad fieldsearch field ('.$this->queryParams->fieldsearch[$i].').');
				}
			}
		// Checking fields to retrieve
		if($this->queryParams->mode=='normal')
			{
			// An array containing the appended fields to avoid to check
			$appendedFields=new ArrayObject();
			// Testing fields
			if(!isset($this->queryParams->field))
				{
				throw new RestException(RestCodes::HTTP_400,
					'You must provide at least one field parameter to use this driver'
					.' in the normal mode.');
				}
			for($i=0, $j=$this->queryParams->field->count(); $i<$j; $i++)
				{
				$exists=false;
				// Looking for the id field
				if('id'==$this->queryParams->field[$i])
					{
					throw new RestException(RestCodes::HTTP_400,
						'The "id" field is systematically included, please remove it.');
					}
				// Looking for the label special field
				if('label'==$this->queryParams->field[$i])
					{
					if((!isset($schema->table->labelFields))
						||!$schema->table->labelFields->count())
						{
						throw new RestException(RestCodes::HTTP_400,
							'This table has no label fields.');
						}
					$this->queryParams->field[$i]='%';
					foreach($schema->table->labelFields as $labelField)
						{
						if($this->queryParams->field[$i]=='%')
							{
							$this->queryParams->field[$i]=$this->request->table
								.'.'.$labelField;
							$this->appendMainReqField($mainReqFields, $labelField);
							}
						else
							{
							$appendedFields->append($this->request->table.'.'.$labelField);
							$this->appendMainReqField($mainReqFields,$labelField);
							}
						}
					continue;
					}
				// Looking for the global wildcard
				if('*.*'==$this->queryParams->field[$i])
					{
					if($j!=1)
						{
						throw new RestException(RestCodes::HTTP_400,
							'When set, the global widlcard field ("*.*") must be the only field.');
						}
					$this->queryParams->field[$i]='*';
					foreach($schema->table->constraintFields as $field)
						{
						// Joined entries
						if(isset($field->joins))
							{
							$this->appendMainReqField($mainReqFields, $field->name);
							// Looping through the join constraints
							foreach($field->joins as $join)
								{
								array_push($suscribedJoins, $join->name);
								$this->queryParams->field->append($join->name.'.*'); $j++;
								}
							}
						// Referring entries
						if(isset($field->references))
							{
							$this->appendMainReqField($mainReqFields, $field->name);
							// Looking through the referencing constraints
							foreach($field->references as $reference)
								{
								array_push($suscribedJoins, $reference->name);
								$this->queryParams->field->append($reference->name.'.*'); $j++;
								}
							}
						// Linked entries
						if(isset($field->linkTo))
							{
							$this->appendMainReqField($mainReqFields, $field->name);
							array_push($suscribedJoins, $field->linkTo->name);
							$this->queryParams->field->append($field->linkTo->name.'.*'); $j++;
							}
						}
					}
				// Looking for the wildcard
				if('*'==$this->queryParams->field[$i])
					{
					// Translating the wildcard
					foreach($schema->table->fields as $field)
						{
						if($field->name=='id')
							continue;
						if(in_array($this->request->table.'.'.$field->name, (array) $this->queryParams->field))
							{
							throw new RestException(RestCodes::HTTP_400,
								'The field "'.$this->request->table.'.'.$field.' is already'
								.' retrieved with the wildcard "*", please remove him."');
							}
						if($this->queryParams->field[$i]=='*')
							{
							$this->queryParams->field[$i]=$this->request->table
								.'.'.$field->name;
							$this->appendMainReqField($mainReqFields, $field->name);
							}
						else
							{
							$appendedFields->append($this->request->table.'.'.$field->name);
							$this->appendMainReqField($mainReqFields,$field->name);
							}
						}
					continue;
					}
				// Searching
				foreach($schema->table->fields as $field)
					{
					// Table fields
					if($field->name==$this->queryParams->field[$i])
						{
						$this->appendMainReqField($mainReqFields, $field->name);
						$this->queryParams->field[$i]=$this->request->table
							.'.'.$this->queryParams->field[$i];
						// mode light only ?
						$exists=true; break;
						}
					// Constraints fields
					// check if all fields are retrieved and raise exception ?
					// Joins : fieldJoinsTableField
					if(isset($field->joins)&&0===strpos($this->queryParams->field[$i],
							$field->name.'Joins'))
						{
						// Looking for the right join constraint
						foreach($field->joins as $join)
							{
							if(0===strpos($this->queryParams->field[$i], $join->name))
								{
								// Retrieving the constraint schema if not yet retrieved
								if(!isset($contraintsSchemas->{$join->table}))
									{
									$contraintsSchemas->{$join->table}=
										RestDbHelper::getTableSchema(
											$this->request->database, $join->table);
									}
								// Testing the wildcard
								if($this->queryParams->field[$i]===$join->name.'.*')
									{
									// Suscribe
									array_push($suscribedJoins, $join->name);
									// Hunt other fields of the same table and prohibit
									foreach($this->queryParams->field as $field2)
										{
										if(0===strpos($field2, $join->name)
											&&$this->queryParams->field[$i]!=$field2)
												{
												throw new RestException(RestCodes::HTTP_400,
													'The field '.$fields2.' is already included'
													.' by the field '.$this->queryParams->field[$i].'.');
												}
										}
									// Add all fields
									foreach($contraintsSchemas->{$join->table}->table->fields
										as $cField)
										{
										if($this->queryParams->field[$i]===$join->name.'.*')
											{
											$this->queryParams->field[$i]=$join->name.'.'.$cField->name;
											}
										else
											{
											$appendedFields->append($join->name.'.'.$cField->name);
											}
										}
									$exists=true; break 2;
									}
								// Testing the table fields
								foreach($contraintsSchemas->{$join->table}->table->fields as $cField)
									{
									if($this->queryParams->field[$i]===$join->name
										.'.'.$cField->name)
										{
										// Suscribe
										array_push($suscribedJoins, $join->name);
										$exists=true; break 3;
										}
									}
								}
							}
						}
					// References : fieldRefsTableField
					if(isset($field->references)&&0===strpos($this->queryParams->field[$i],
							$field->name.'Refs'))
						{
						// Looking for the right join constraint
						foreach($field->references as $reference)
							{
							if(0===strpos($this->queryParams->field[$i], $reference->name .'.'))
								{
								// Retrieving the constraint schema if not yet retrieved
								if(!isset($contraintsSchemas->{$reference->table}))
									{
									$contraintsSchemas->{$reference->table}=
										RestDbHelper::getTableSchema(
											$this->request->database, $reference->table);
									}
								// The field cannot be the referring field
								if($this->queryParams->field[$i]==$reference->name
									.'.'.$reference->field)
									{
									throw new RestException(RestCodes::HTTP_400,
										'Required field cannot be the linked field.');
									}
								// Converting the wildcard
								if($this->queryParams->field[$i]===$reference->name.'.*')
									{
									// Suscribe
									array_push($suscribedJoins, $reference->name);
									// Hunt other fields of the same table and prohibit
									foreach($this->queryParams->field as $field2)
										{
										if(0===strpos($field2, $reference->name)
											&&$this->queryParams->field[$i]!=$field2)
												{
												throw new RestException(RestCodes::HTTP_400,
													'The field '.$fields2.' is already included'
													.' by the field '.$this->queryParams->field[$i].'.');
												}
										}
									// Add all fields
									foreach($contraintsSchemas->{$reference->table}->table->fields
										as $cField)
										{
										if($cField->name==$reference->field)
											{
											continue;
											}
										if($this->queryParams->field[$i]===$reference->name.'.*')
											{
											$this->queryParams->field[$i]=$reference->name
												.'.'.$cField->name;
											}
										else
											{
											$appendedFields->append($reference->name
												.'.'.$cField->name);
											}
										}
									$exists=true; break 2;
									}
								// Testing the table fields
								foreach($contraintsSchemas->{$reference->table}->table->fields as $cField)
									{
									if($this->queryParams->field[$i]===$reference->name
										.'.'.$cField->name)
										{
										// Suscribe
										array_push($suscribedJoins, $reference->name);
										$exists=true; break 3;
										}
									}
								}
							}
						}
					// Links : fieldLinkTableField
					if(isset($field->linkTo,$field->linkTo->table)
						&&0===strpos($this->queryParams->field[$i], $field->linkTo->name))
						{
						// Retrieving the constraint schema if not yet retrieved
						if(!isset($contraintsSchemas->{$field->linkTo->table}))
							{
							$contraintsSchemas->{$field->linkTo->table}=
								RestDbHelper::getTableSchema(
									$this->request->database, $field->linkTo->table);
							}
						// Converting the wildcard
						if($this->queryParams->field[$i]===$field->linkTo->name.'.*')
							{
							// Suscribe
							array_push($suscribedJoins, $field->linkTo->name);
							// Hunt other fields of the same table and prohibit
							foreach($this->queryParams->field as $field2)
								{
								if(0===strpos($field2, $field->linkTo->name)
									&&$this->queryParams->field[$i]!=$field2)
										{
										throw new RestException(RestCodes::HTTP_400,
											'The field "'.$fields2.'" is already included'
											.' by the field "'.$this->queryParams->field[$i].'".');
										}
								}
							// Add all fields
							foreach($contraintsSchemas->{$field->linkTo->table}
								->table->fields as $cField)
								{
								if($cField->name===$field->linkTo->field)
									{
									continue;
									}
								if($this->queryParams->field[$i]===$field->linkTo->name.'.*')
									{
									$this->queryParams->field[$i]=$field->linkTo->name
										.'.'.$cField->name;
									}
								else
									{
									$appendedFields->append($field->linkTo->name
										.'.'.$cField->name);
									}
								}
							$exists=true; break;
							}
						// Converting the label
						if($this->queryParams->field[$i]===$field->linkTo->name.'.label')
							{
							if((!isset($contraintsSchemas->{$field->linkTo->table}
								->table->labelFields))||!$contraintsSchemas->{$field->linkTo->table}
								->table->labelFields->count())
								{
								throw new RestException(RestCodes::HTTP_400,
									'The join table has no label field.');
								}
							// Suscribe
							array_push($suscribedJoins, $field->linkTo->name);
							// Add all label fields
							$erased=false;
							foreach($contraintsSchemas->{$field->linkTo->table}
								->table->labelFields as $cField)
								{
								if(false===$erased)
									{
									$erased=true;
									$this->queryParams->field[$i]=$field->linkTo->name
										.'.'.$cField;
									}
								else
									{
									$appendedFields->append($field->linkTo->name
										.'.'.$cField);
									}
								}
							$exists=true; break;
							}
						// The field cannot be the linked field
						if($this->queryParams->field[$i]==$field->linkTo->name
								.'.'.$field->linkTo->field)
							{
							throw new RestException(RestCodes::HTTP_400,
								'The required field cannot be the linked field');
							}
						// Testing the table fields
						foreach($contraintsSchemas->{$field->linkTo->table}->table->fields as $cField)
							{
							if($this->queryParams->field[$i]==$field->linkTo->name.'.'.$cField->name)
								{
								// Suscribe
								array_push($suscribedJoins, $field->linkTo->name);
								$exists=true; break 2;
								}
							}
						}
					}
				// If not found, raise a RestException
				if(!$exists)
					{
					throw new RestException(RestCodes::HTTP_400,
						'Required a bad field ('.$this->queryParams->field[$i].').');
					}
				}
			// Merging the fields
			foreach($appendedFields as $field)
				{
				$this->queryParams->field->append($field);
				}
			// Adding the id field
			$this->queryParams->field->append($this->request->table.'.id');
			$this->appendMainReqField($mainReqFields, 'id');
			}
		// No field specified
		else
			{
			if(isset($this->queryParams->field))
				{
				throw new RestException(RestCodes::HTTP_400,
					'The field parameter is not usable with the count mode.');
				}
			$this->queryParams->field=new MergeArrayObject();
			if($this->queryParams->mode=='count')
				{
				$this->queryParams->field->append($this->request->table.'.id');
				$this->appendMainReqField($mainReqFields, 'id');
				}
			else if($this->queryParams->mode=='light')
				{
				$this->queryParams->field->append($this->request->table.'.id');
				$this->appendMainReqField($mainReqFields, 'id');
				if(isset($schema->table->nameField)
					&&$schema->table->nameField!='id')
					{
					$this->appendMainReqField($mainReqFields,
						$schema->table->nameField);
					$this->queryParams->field->append($this->request->table
						.'.'.$schema->table->nameField);
					}
				if(isset($schema->table->labelFields))
				foreach($schema->table->labelFields as $field)
					{
					$this->appendMainReqField($mainReqFields, $field);
					$this->queryParams->field->append($this->request->table.'.'.$field);
					}
				}
			}
		// Preparing joins
		$sqlJoins='';
		if(sizeof($suscribedJoins))
			{
			foreach($schema->table->constraintFields as $field)
				{
				// Joined entries
				if(isset($field->joins))
					{
					// Looping through the join constraints
					foreach($field->joins as $join)
						{
						if(false!==in_array($join->name, $suscribedJoins))
							{
							$this->appendMainReqField($mainReqFields, $field->name);
							$this->queryParams->field->append($join->bridge.'.id');
							$sqlJoins.=
								"\n".'LEFT JOIN '.$join->bridge
								.' ON '.$join->bridge.'.'.$this->request->table.'_id'
								.'=temp_'.$this->request->table.'.'.$field->name
								."\n".'LEFT JOIN '.$join->table
								.' AS '.$join->name.' ON '.$join->name.'.'.$join->field
								.'='.$join->bridge.'.'.$join->table.'_id';
							}
						}
					}
				// Referring entries
				if(isset($field->references))
					{
					// Looking through the referencing constraints
					foreach($field->references as $reference)
						{
						if(false!==in_array($reference->name,$suscribedJoins))
							{
							$this->appendMainReqField($mainReqFields, $field->name);
							$sqlJoins.="\n".'LEFT JOIN '.$reference->table
								.' AS '.$reference->name.' ON temp_'.$this->request->table
								.'.'.$field->name.'='.$reference->name.'.'.$reference->field;
							}
						}
					}
				// Linked entries
				if(isset($field->linkTo)&&(false!==in_array($field->linkTo->name,
					$suscribedJoins)))
					{
					$this->appendMainReqField($mainReqFields, $field->name);
					$this->queryParams->field->append($this->request->table.'.'.$field->name);
					$sqlJoins.="\n".'LEFT JOIN '.$field->linkTo->table
						.' AS '.$field->linkTo->name.' ON temp_'.$this->request->table
						.'.'.$field->name.'='.$field->linkTo->name.'.'.$field->linkTo->field;
					}
				}
			}
		// Preparing the main request
		$mainRequest='';
		foreach($mainReqFields as $field)
			{
			$mainRequest.=($mainRequest?','."\n\t":'').$this->request->table.'.'.$field;
			}
		$mainRequest="\n\t".'SELECT '.$mainRequest
			."\n\t".'FROM ' . $this->request->table
			.($subSearchClause?"\n\t".'WHERE '.$subSearchClause:'');
		// Setting main request order clause and limit/start parameters
		if($this->queryParams->mode!='count')
			{
			$mainRequest.=($subOrderbyClause?"\n\t".'ORDER BY '.$subOrderbyClause:'')
					.($this->queryParams->limit&&!$searchClause?
						"\n\t".' LIMIT '.$this->queryParams->start.', '
						.$this->queryParams->limit.'':''
					);
			}
		// Preparing the final request
		if($this->queryParams->mode=='count')
			{
			$sqlRequest='DISTINCT temp_'.$this->request->table.'.id';
			}
		else
			{
			$sqlRequest='';
			foreach($this->queryParams->field as $field)
				{
				if(strpos($field,$this->request->table.'.')===0)
					{
					$sqlRequest.=($sqlRequest?','."\n\t":'').'temp_'.$field;
					}
				else
					{
					$sqlRequest.=($sqlRequest?','."\n\t":'').$field.' AS '.
						str_replace('.','',$field);
					}
				}
			}
		$sqlRequest='SELECT '.$sqlRequest
			.' FROM ('.$mainRequest."\n".') temp_'.$this->request->table
			.$sqlJoins
			.($searchClause?"\n".'WHERE '.$searchClause:'');

		// Setting request order by clause
		if($this->queryParams->mode!='count'&&$orderbyClause)
			{
			$sqlRequest.=($orderbyClause?"\n\t".'ORDER BY '.$orderbyClause:'');
			}
		// Setting final request limit/start parameters
		if($this->queryParams->mode!='count'&&$searchClause)
			{
			$sqlRequest.=($this->queryParams->limit?"\n".'LIMIT '.$this->queryParams->start
				.', '.$this->queryParams->limit.'':'');
			}
		$this->core->db->selectDb($this->request->database);
		$query=$this->core->db->query($sqlRequest);
		// Filling entries
		$response->vars->entries=new MergeArrayObject(array(),
			MergeArrayObject::ARRAY_MERGE_POP);
		// Return empty response if no entries
		if($this->queryParams->mode=='count')
			{
			$response->vars->count=$this->core->db->numRows();
			}
		else
			{
			if($this->core->db->numRows())
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
						$entry->id=$row['id'];
						if(isset($row[$schema->table->nameField]))
							{
							$entry->name=$row[$schema->table->nameField];
							}
						$entry->label='';
						$response->vars->entries->append($entry);
						}
					// Retrieving fields
					foreach($schema->table->fields as $field)
						{
						// Main table fields
						if((!$looped)&&isset($row[$field->name]))
							{
							// Multiple main fields
							if(isset($field->multiple)&&$field->multiple)
								{
								$entry->{$field->name} = new MergeArrayObject();
								foreach(explode(',',$row[$field->name]) as $val)
									$entry->{$field->name}->append($val);
								}
							// Single fields
							else if($field->name!='password')
								{
								$entry->{$field->name} = $row[$field->name];
								}
							}
						// Linked fields
						if((!$looped)&&isset($field->linkTo,$field->linkTo->table,
								$contraintsSchemas->{$field->linkTo->table}))
							{
							$entry->{$field->name} = new stdClass();
							if(isset($row[$field->name]))
								{
								$entry->{$field->name}->{$field->linkTo->field} =
									$row[$field->name];
								}
							// Searching each fields of the linked entry
							foreach($contraintsSchemas->{$field->linkTo->table}
								->table->fields as $cField)
								{
								if(isset($row[$field->linkTo->name.$cField->name])
									&&$cField->name!='password')
									{
									$entry->{$field->name}->{$cField->name} =
										$row[$field->linkTo->name.$cField->name];
									}
								}
							// Labels
							if(isset($contraintsSchemas->{$field->linkTo->table}
								->table->labelFields))
							foreach($contraintsSchemas->{$field->linkTo->table}
								->table->labelFields as $cField)
								{
								if($cField!='label'&&isset($row[$field->linkTo->name.$cField]))
									{
									$entry->{$field->name}->label.=
										(isset($entry->{$field->name}->label)?' ':'')
										.$row[$field->linkTo->name.$cField];
									}
								}
							}
						// Reading join or referring fields values
						// Joined entries
						if(isset($field->joins))
							{
							// Looping through the join constraints
							foreach($field->joins as $join)
								{
								if(!isset($row[$join->name.$join->field]))
									{
									continue;
									}
								if(isset($entry->{$join->name})&&$entry->{$join->name}->count())
									{
									foreach($entry->{$join->name} as $joinedEntry)
										{
										if($joinedEntry->{$join->field}==$row[$join->name.$join->field])
											continue 2;
										}
									}
								$joinedEntry=new stdClass();
								$joinedEntry->joinId=$row[$join->bridge.'id'];
								foreach($contraintsSchemas->{$join->table}->table->fields
									as $cField)
									{
									if(isset($row[$join->name.$cField->name])
										&&$cField->name!='password')
										{
										$joinedEntry->{$cField->name} =
											$row[$join->name.$cField->name];
										}
									}
								if((!isset($entry->{$join->name}))||
									!($entry->{$join->name} instanceof ArrayObject))
									{
									$entry->{$join->name}=new MergeArrayObject();
									}
								$entry->{$join->name}->append($joinedEntry);
								}
							}
						// Referring entries
						if(isset($field->references))
							{
							// Looking through the referencing constraints
							foreach($field->references as $reference)
								{
								if(!isset($row[$reference->name.'id']))
									{
									continue;
									}
								if(isset($joinedEntry)
									&&$joinedEntry->id==$row[$reference->name.'id'])
									{
									continue;
									}
								$joinedEntry=new stdClass();
								foreach($contraintsSchemas->{$reference->table}->table->fields
									as $cField)
									{
									if(isset($row[$reference->name.$cField->name])
										&&$cField->name!='password')
										{
										$joinedEntry->{$cField->name} =
											$row[$reference->name.$cField->name];
										}
									}
								if((!isset($entry->{$reference->name}))||
									!($entry->{$reference->name} instanceof ArrayObject))
									{
									$entry->{$reference->name}=new MergeArrayObject();
									}
								$entry->{$reference->name}->append($joinedEntry);
								}
							}
						}
					// Setting label
					if(!$looped)
						{
						if(isset($schema->table->labelFields))
						foreach($schema->table->labelFields as $field)
							{
							if($field!='label'&&isset($row[$field]))
								$entry->label.=($entry->label?' ':'').$row[$field];
							}
						}
					// Retrieving attached files
					if((!$looped)&&$this->queryParams->files!='ignore')
						{
						$res=new RestResource(new RestRequest(RestMethods::GET,
							'/fsi/db/'.$this->request->database.'/'.$this->request->table.'/'
							.$entry->id.'/files.dat?mode=light'
							.($this->queryParams->files=='include'?
								'&format=datauri':'')));
						$res=$res->getResponse();
						if($res->code==RestCodes::HTTP_200)
							{
							if($this->queryParams->files=='count') {
								$entry->numFiles=$res->vars->files->count();
								}
							else if($this->queryParams->files=='list')
								{
								$entry->attachedFiles=$res->vars->files;
								}
							else if($this->queryParams->files=='include')
								{
								throw new RestException(RestCodes::HTTP_501,
									'File include not yet implemented, but feel free to do it ;)');
								}
							}
						$response->appendToHeader('X-Rest-Uncacheback',
							'/fs/db/'.$this->request->database.'/'.$this->request->table
							.'/'.$entry->id.'/files/');
						}
					}
				}
			}
		$this->core->db->freeResult();
		return $response;
		}
	// Helper to fill fields to retrieve
	function appendMainReqField(&$fields,$field)
		{
		// Look for the field in clauses fields
		if(false===($index = array_search('*', $fields))
			&&false===($index = array_search($field, $fields)))
			{
			array_push($fields,$field);
			}
		}
	}
