<?php
class RestDbEntryDriver extends RestVarsDriver
	{
	static $drvInf;
	private $_schema;
	function __construct(RestRequest $request)
		{
		parent::__construct($request);
		// Retrieving main table schema
		$res=new RestResource(new RestRequest(RestMethods::GET,'/db/'.$this->request->database.'/'.$this->request->table.'.dat'));
		$res=$res->getResponse();
		if($res->code!=RestCodes::HTTP_200)
			throw new RestException(RestCodes::HTTP_400,'Can\'t retrieve an entry of an unexisting table.');
		$this->_schema=$res->vars;
		}
	static function getDrvInf($methods=0)
		{
		$drvInf=parent::getDrvInf(RestMethods::GET|RestMethods::PUT|RestMethods::PATCH|RestMethods::DELETE);
		$drvInf->name='Db: Entry Driver';
		$drvInf->description='Get the content of an entry by it\'s numeric id.';
		$drvInf->usage='/db/database/table/id'.$drvInf->usage
			.'?mode=(light|extend|join|fulljoin)&joinMode=(joined|refered)';
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='mode';
		$drvInf->methods->get->queryParams[0]->values=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]->values[0]=
			$drvInf->methods->get->queryParams[0]->value='normal';
		$drvInf->methods->get->queryParams[0]->values[1]='light';
		$drvInf->methods->get->queryParams[0]->values[2]='extend';
		$drvInf->methods->get->queryParams[0]->values[3]='join';
		$drvInf->methods->get->queryParams[0]->values[4]='fulljoin';
		$drvInf->methods->get->queryParams[1]=new stdClass();
		$drvInf->methods->get->queryParams[1]->name='joinMode';
		$drvInf->methods->get->queryParams[1]->values=new MergeArrayObject();
		$drvInf->methods->get->queryParams[1]->values[0]=
			$drvInf->methods->get->queryParams[1]->value='all';
		$drvInf->methods->get->queryParams[1]->values[1]='joined';
		$drvInf->methods->get->queryParams[1]->values[2]='refered';
		$drvInf->methods->get->queryParams[2]=new stdClass();
		$drvInf->methods->get->queryParams[2]->name='fileMode';
		$drvInf->methods->get->queryParams[2]->values=new MergeArrayObject();
		$drvInf->methods->get->queryParams[2]->values[0]=
			$drvInf->methods->get->queryParams[2]->value='normal';
		$drvInf->methods->get->queryParams[2]->values[1]='datauri';
		return $drvInf;
		}
	function head()
		{
		$this->core->db->query('SELECT id FROM ' . $this->request->table .' WHERE id="'.$this->request->entry.'"');
		if(!$this->core->db->numRows())
			throw new RestException(RestCodes::HTTP_410,'The given entry does\'nt exist.');

		return new RestVarsResponse(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	function get()
		{
		$mainRequest='SELECT '.$this->request->table.'.*';
		$mainRequest.=' FROM ' . $this->request->table;
		$mainRequest.=' WHERE '.$this->request->table.'.id="'.$this->request->entry.'"';
		$sqlFields='temp_'.$this->request->table.'.*';
		$sqlJoins='';
		// Adding linkedTables joins & fields
		if($this->queryParams->mode!='light')
			{
			foreach($this->_schema->table->linkedTables as $table)
				{
				$res=new RestResource(new RestRequest(RestMethods::GET,
					'/db/'.$this->request->database.'/'.$table.'.dat'));
				$res=$res->getResponse();
				if($res->code!=RestCodes::HTTP_200)
					return $res;
				Varstream::import(${$table.'_schema'}=new stdClass(),$res->getContents());
				$sqlJoinConditions='';
				// Finding main table fields joined with current linkedTable
				foreach($this->_schema->table->fields as $field)
					{
					if($this->queryParams->mode=='join'||$this->queryParams->mode=='fulljoin')
						{
						// Joined fields
						if(isset($field->joinTable)&&$field->linkedTable==$table
							&&($this->queryParams->joinMode=='all'||$this->queryParams->joinMode=='joined'))
							//||strpos($field->name,'joined_')===0
							{
							$sqlFields.=', '.$field->joinTable.'.'.$field->linkedTable.'_id as '
								.$field->linkedTable.'_join, '.$field->joinTable.'.id as '.$field->linkedTable.'_join_id';
							if($this->queryParams->mode=='fulljoin')
								$sqlJoinConditions.=($sqlJoinConditions?' OR':'').' '.$field->linkedTable
									.'.id='.$field->joinTable.'.'.$field->linkedTable.'_id';
							$sqlJoins.=' LEFT JOIN '.$field->joinTable.' ON '.$field->joinTable
								.'.'.$this->request->table.'_id=temp_'.$this->request->table.'.id';
							}
						// Refered fields
						if(isset($field->referedField)&&$field->linkedTable==$table
							&&($this->queryParams->joinMode=='all'||$this->queryParams->joinMode=='refered'))
							{
							$sqlFields.=', '.$field->linkedTable.'.id as '.$field->linkedTable.'_join';
							$sqlJoinConditions.=($sqlJoinConditions?' OR':'').' '.$table.'.'.$field->linkedField
								.'=temp_'.$this->request->table.'.id';
							}
						}
					// Linked fields
					if(($this->queryParams->mode=='extend'||$this->queryParams->mode=='join'
						||$this->queryParams->mode=='fulljoin')&&isset($field->linkedTable)
						&&$field->linkedTable==$table&&!(strpos($field->name,'joined_')===0
						||strpos($field->name,'refered_')===0))
						{
						$sqlJoinConditions.=($sqlJoinConditions?' OR':'').' '.$table.'.id=temp_'
							.$this->request->table.'.'.$field->name;
						}
					}
				// If there are 
				if($sqlJoinConditions)
					{
					$sqlJoins.=' LEFT JOIN '.$table.' ON'.$sqlJoinConditions;
					foreach(${$table.'_schema'}->table->fields as $field)
						{
						if(!(strpos($field->name,'joined_')===0||strpos($field->name,'refered_')===0))
							$sqlFields.=', '.$table.'.'.$field->name.' as join_'.$table.'_'.$field->name;
						}
					}
				}
			}
		$sqlRequest='SELECT '.$sqlFields.' FROM ('.$mainRequest.') temp_'
			.$this->request->table . $sqlJoins;
		$query=$this->core->db->query($sqlRequest);

		$response=new RestVarsResponse(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));

		if($this->core->db->numRows())
			{
			while ($row = $this->core->db->fetchArray($query))
				{
				$looped=false;
				if(isset($response->vars->entry))
					{
					$looped=true;
					}
				else
					{
					$response->vars->entry=new stdClass();
					$response->vars->entry->label='';
					}
				foreach($this->_schema->table->fields as $field)
					{
					// Reading joined or refered fields values
					if(strpos($field->name,'joined_')===0||strpos($field->name,'refered_')===0)
						{
						if(($this->queryParams->mode=='join'||$this->queryParams->mode=='fulljoin')
							&&((strpos($field->name,'joined_')===0
							&&($this->queryParams->joinMode=='all'||$this->queryParams->joinMode=='joined'))
							||(strpos($field->name,'refered_')===0
							&&($this->queryParams->joinMode=='all'||$this->queryParams->joinMode=='refered'))))
							{
							if(!isset($response->vars->entry->{$field->name}))
								$response->vars->entry->{$field->name}=new MergeArrayObject();
							$isIn=false;
							foreach($response->vars->entry->{$field->name} as $lField)
								{
								if((isset($lField->join_id,$row[$field->linkedTable.'_join_id'])
									&&$lField->join_id==$row[$field->linkedTable.'_join_id'])
									||$lField->id==$row[$field->linkedTable.'_join'])
									$isIn=true;
								}
							if(!$isIn)
								{
								$lField=new stdClass();
								if(isset($row[$field->linkedTable.'_join_id']))
									{
									$lField->join_id=$row[$field->linkedTable.'_join_id'];
									}
								$lField->id=$row[$field->linkedTable.'_join'];
								if($this->queryParams->mode=='fulljoin')
									{
									$lField->label='';
									foreach(${$field->linkedTable.'_schema'}->table->fields as $field2)
										{
										if($field2->name!='password'&&!(strpos($field2->name,'joined_')===0
											||strpos($field2->name,'refered_')===0))
											$lField->{$field2->name}=$row['join_'.$field->linkedTable.'_'.$field2->name];
										}
									if(${$field->linkedTable.'_schema'}->table->nameField)
										$lField->name=$lField->{${$field->linkedTable.'_schema'}->table->nameField};
									if(isset(${$field->linkedTable.'_schema'}->table->labelFields)
										&&${$field->linkedTable.'_schema'}->table->labelFields->count())
										{
										foreach(${$field->linkedTable.'_schema'}->table->labelFields as $field2)
											{
											if($field2!='label')
												$lField->label.=($lField->label?' ':'').$lField->{$field2};
											}
										}
									}
								$response->vars->entry->{$field->name}->append($lField);
								}
							}
						}
					// Multiple main fields
					else if($this->queryParams->mode!='light'&&isset($field->multiple)&&$field->multiple&&!$looped)
						{
						$response->vars->entry->{$field->name} = new MergeArrayObject();
						foreach(explode(',',$row[$field->name]) as $val)
							$response->vars->entry->{$field->name}->append($val);
						}
					else if($field->name!='password'&&($this->queryParams->mode!='light'
						||$field->name=='label'||$field->name=='id'||$field->name=='name'))
						{
						// Linked fields
						if(isset($field->linkedTable)&&$field->linkedTable)
							{
							$response->vars->entry->{$field->name} = $row[$field->name];
							if($this->queryParams->mode=='extend'||$this->queryParams->mode=='join'
								||$this->queryParams->mode=='fulljoin')
								{
								if($row['join_'.$field->linkedTable.'_id']==$row[$field->name])
									{
									$response->vars->entry->{$field->name.'_label'}='';
									foreach(${$field->linkedTable.'_schema'}->table->fields as $field2)
										{
										if($field2->name!='password'&&!(strpos($field2->name,'joined_')===0
											||strpos($field2->name,'refered_')===0))
											$response->vars->entry->{$field->name.'_'.$field2->name} = 
												$row['join_'.$field->linkedTable.'_'.$field2->name];
										}
									if(!$response->vars->entry->{$field->name.'_label'})
									foreach(${$field->linkedTable.'_schema'}->table->labelFields as $field2)
										{
										if($field2)
											$response->vars->entry->{$field->name.'_label'}.=
												($response->vars->entry->{$field->name.'_label'}?' ':'')
												.$response->vars->entry->{$field->name.'_'.$field2};
										}
									}
								}
							}
						// Main fields
						else if(!$looped)
							$response->vars->entry->{$field->name} = $row[$field->name];
						}
					}
				if(!$looped)
					{
					if($this->_schema->table->nameField)
						$response->vars->entry->name=
							$response->vars->entry->{$this->_schema->table->nameField};
					if(isset($this->_schema->table->labelFields)
						&&$this->_schema->table->labelFields->count())
						{
						foreach($this->_schema->table->labelFields as $field)
							{
							if($field!='label')
								$response->vars->entry->label.=($response->vars->entry->label?' ':'')
									.$response->vars->entry->{$field};
							}
						}
					}
				}
			$res=new RestResource(new RestRequest(RestMethods::GET,
				'/fsi/db/'.$this->request->database.'/'.$this->request->table.'/'
				.$this->request->entry.'/files.dat?mode=light'
				.($this->queryParams->fileMode!='normal'?'&format='.$this->queryParams->fileMode:'')));
			$res=$res->getResponse();
			if($res->code==RestCodes::HTTP_200)
				{
				$response->vars->entry->attached_files=$res->vars->files;
				}
			$response->setHeader('X-Rest-Uncacheback','/fs/db/'.$this->request->database
				.'/'.$this->request->table.'/'.$this->request->entry.'/files/');
			}
		else
			{
			$response->code=RestCodes::HTTP_410;
			}
		return $response;
		}
	function put()
		{
		$sqlRequest='';
		$sqlRequest2='';
		$response=$this->head();
		if($response->code==RestCodes::HTTP_200)
			{
			$sqlRequest.='UPDATE `'.$this->request->table.'` SET';
			foreach($this->_schema->table->fields as $field)
				{
				if($field->name=='password'
					&&isset($this->request->content->entry->{$field->name})
					&&$this->request->content->entry->{$field->name})
					{
					$sqlRequest2.=($sqlRequest2?',':'').' `'.$field->name.'` = "'
						.sha1($this->request->content->entry->{$field->name}).'"';
					}
				else if(strpos($field->name,'joined_')!==0
					&&strpos($field->name,'refered_')!==0&&$field->name!='id')
					{
					if(isset($field->multiple)&&$field->multiple)
						{
						$sqlRequest2.=($sqlRequest2?',':'').' `'.$field->name.'` = "';
						$sqlRequest3='';
						foreach($this->request->content->entry->{$field->name} as $entry)
							{
							$sqlRequest3.=($sqlRequest3?',':'').xcUtilsInput::filterValue(
								$entry->value,$field->type,$field->filter);
							}
						$sqlRequest2.=$sqlRequest3.'"';
						}
					else
						{
						$value=(isset($this->request->content->entry->{$field->name})?
							xcUtilsInput::filterValue($this->request->content->entry->{$field->name},
								$field->type,$field->filter):'');
						if(isset($field->required)&&$field->required
							&&!($value||$value===0||$value===floatval(0)||$value==='0'))
							{
							if($field->type=='date')
								$value=date('Y-m-d');
							else if($field->type=='timestamp'||$field->type=='datetime')
								$value=date('Y-m-d H:i:s');
							}
						if($value||$value===0||$value===floatval(0)||$value==='0')
							$sqlRequest2.=($sqlRequest2?',':'').' `'.$field->name.'` = "'.$value.'"';
						else if(isset($field->required)&&$field->required&&$field->name!='password')
							throw new RestException(RestCodes::HTTP_400,'The field '.$field->name.' is required.');
						else if($field->name!='password')
							$sqlRequest2.=($sqlRequest2?',':'').' `'.$field->name.'` = NULL';
						}
					}
				}
			$sqlRequest.=$sqlRequest2.' WHERE id="'.$this->request->entry.'"';
			$this->core->db->query($sqlRequest);
			}
		else
			{
			if($this->_schema->table->nameField=='id')
				throw new RestException(RestCodes::HTTP_400,'The given table has no name field.');
			foreach($this->_schema->table->fields as $field)
				{
				if(strpos($field->name,'joined_')!==0&&strpos($field->name,'refered_')!==0
					&&$field->name!='id'&&$field->name!='password')
					{
					$sqlRequest.=($sqlRequest?',':'').'`'.$field->name.'`';
					$sqlRequest2.=($sqlRequest2?',':'').'"'.xcUtilsInput::filterValue(
						$this->request->content->entry->{$field->name},$field->type,$field->filter).'"';
					}
				}
			$sqlRequest='INSERT INTO `'.$this->request->table . '` (' . $sqlRequest . ') VALUES ('.$sqlRequest2.')';
			$this->core->db->query($sqlRequest);
			$this->request->entry = $this->core->db->insertId();
			}
		$res=null;
		foreach($this->_schema->table->fields as $field)
			{
			if(strpos($field->name,'joined_')===0&&isset($this->request->content->entry->{$field->name}))
				{
				foreach($this->request->content->entry->{$field->name} as $entry)
					{
					if(isset($entry->value)
						&&(xcUtilsInput::filterValue($entry->value,$field->type,$field->filter)
							||xcUtilsInput::filterValue($entry->value,$field->type,$field->filter)===0))
						{
						if(!$res)
							{
							$res=new RestResource(new RestRequest(RestMethods::GET,
								'/db/'.$this->request->database.'/'.$this->request->table.'/'
								.$this->request->entry.'.dat?mode=join'));
							$response=$res->getResponse();
							}
						$inside=false;
						foreach($response->vars->entry->{$field->name} as $joined)
							{
							if($entry->value==$joined->id)
								{
								$inside=true;
								}
							}
						if(!$inside)
							$this->core->db->query('INSERT INTO '.$field->joinTable
								.' ('.$field->linkedTable.'_id,'.$this->request->table.'_id)'
								.' VALUES ('.xcUtilsInput::filterValue(
									$entry->value,$field->type,$field->filter)
								.','.$this->request->entry.')');
						}
					}
				}
			}
		$res=new RestResource(new RestRequest(RestMethods::GET,
			'/db/'.$this->request->database.'/'.$this->request->table.'/'.$this->request->entry.'.dat'));
		$response=$res->getResponse();
		$response->code=RestCodes::HTTP_201;
		$uncache='/db/'.$this->request->database.'/'.$this->request->table.'/'
			.'|/fs/db/'.$this->request->database.'/'.$this->request->table.'/';
		foreach($this->_schema->table->joinFields as $field)
			$uncache.='|/db/'.$this->request->database.'/'.$field->linkedTable.'/';
		$response->setHeader('X-Rest-Uncache',$uncache);
		return $response;
		}
	function patch()
		{
		$sqlRequest2='';
		$response=$this->head();
		if(isset($this->request->content->entry))
			{
			foreach($this->_schema->table->fields as $field)
				{
				if(property_exists($this->request->content->entry,$field->name))
					{
					if($field->name=='password'||$field->name=='id'
						||strpos($field->name,'joined_')===0)
						throw new RestException(RestCodes::HTTP_501,'Cannot modify fields like "'.$field->name.'" yet.');
					if(isset($field->multiple)&&$field->multiple)
						{
						$sqlRequest2.=($sqlRequest2?',':'').' `'.$field->name.'` = "';
						$sqlRequest3='';
						foreach($this->request->content->entry->{$field->name} as $entry)
							{
							$sqlRequest3.=($sqlRequest3?',':'').xcUtilsInput::filterValue(
								$entry->value,$field->type,$field->filter);
							}
						$sqlRequest2.=$sqlRequest3.'"';
						}
					else
						{
						if($this->request->content->entry->{$field->name}===null)
							$sqlRequest2.=($sqlRequest2?',':'').' `'.$field->name.'` = NULL';
						else
							{
							$value=xcUtilsInput::filterValue($this->request->content->entry->{$field->name},
								$field->type,$field->filter);
							if($value||$value===0||$value===floatval(0)||$value==='0')
								$sqlRequest2.=($sqlRequest2?',':'').' `'.$field->name.'` = "'.$value.'"';
							}
						}
					}
				}
			if($sqlRequest2)
				$this->core->db->query('UPDATE `'.$this->request->table.'` SET'.$sqlRequest2.' WHERE id="'.$this->request->entry.'"');
			}
		$response->code=RestCodes::HTTP_201;
		$uncache='/db/'.$this->request->database.'/'.$this->request->table.'/'
			.'|/fs/db/'.$this->request->database.'/'.$this->request->table.'/';
		foreach($this->_schema->table->joinFields as $field)
			$uncache.='|/db/'.$this->request->database.'/'.$field->linkedTable.'/';
		$response->setHeader('X-Rest-Uncache',$uncache);
		return $response;
		}
	function delete()
		{
		$this->core->db->query('DELETE FROM ' . $this->request->table .' WHERE id="'.$this->request->entry.'"');
		$res=new RestResource(new RestRequest(RestMethods::DELETE,'/fs/db/'.$this->request->database
			.'/'.$this->request->table.'/'.$this->request->entry.'/?recursive=yes'));
		$res=$res->getResponse();
		$uncache='/db/'.$this->request->database.'/'.$this->request->table.'/'
			.'|/fs/db/'.$this->request->database.'/'.$this->request->table.'/';
		foreach($this->_schema->table->joinFields as $field)
			$uncache.='|/db/'.$this->request->database.'/'.$field->linkedTable.'/';
		return new RestVarsResponse(RestCodes::HTTP_410,
			array('X-Rest-Uncache'=>$uncache,
				'Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	}
