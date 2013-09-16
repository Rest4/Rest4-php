<?php
class RestDbEntryDriver extends RestVarsDriver
	{
	static $drvInf;
	function __construct(RestRequest $request)
		{
		parent::__construct($request);
		}
	static function getDrvInf($methods=0)
		{
		$drvInf=parent::getDrvInf(RestMethods::GET|RestMethods::PUT|RestMethods::PATCH|RestMethods::DELETE);
		$drvInf->name='Db: Entry Driver';
		$drvInf->description='Get the content of an entry by it\'s numeric id.';
		$drvInf->usage='/db/database/table/id'.$drvInf->usage
			.'?mode=(count|light)field=field1&field=fiedl2&files=(count|list|include)';
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
		return $drvInf;
		}
	function head()
		{
		$this->core->db->selectDb($this->request->database);
		$this->core->db->query('SELECT id FROM ' . $this->request->table .' WHERE id="'.$this->request->entry.'"');
		if(!$this->core->db->numRows())
			throw new RestException(RestCodes::HTTP_410,'The given entry does\'nt exist.');

		return new RestVarsResponse(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	function get()
		{
		$res=new RestResource(new RestRequest(RestMethods::GET,
			'/db/'.$this->request->database.'/'.$this->request->table
				.'/list.'.$this->request->fileExt.'?'
				.($this->request->queryString?$this->request->queryString.'&':'')
				.'fieldsearch=id&fieldsearchval='.$this->request->entry.'&fieldsearchop=eq'));
		$response=$res->getResponse();
		if($response->code==RestCodes::HTTP_200)
			{
			if($response->vars->entries->count())
				{
				$response->vars->entry=$response->vars->entries[0];
				$response->vars->entries->offsetUnset(0);
				unset($response->vars->entries);
				}
			else
				{
				throw new RestException(RestCodes::HTTP_410,'The given entry does\'nt exist.');
				}
			}
		return $response;
		}
	function put()
		{
		// Retrieving main table schema
		$schema=RestDbHelper::getTableSchema($this->request->database,
			$this->request->table);
		// Building the request
		$sqlRequest='';
		$sqlRequest2='';
		$response=$this->head();
		// If the entry exists, we update her
		if($response->code==RestCodes::HTTP_200)
			{
			$sqlRequest.='UPDATE `'.$this->request->table.'` SET';
			foreach($schema->table->fields as $field)
				{
				if($field->name=='password'
					&&isset($this->request->content->entry->{$field->name})
					&&$this->request->content->entry->{$field->name})
					{
					$sqlRequest2.=($sqlRequest2?',':'').' `'.$field->name.'` = "'
						.sha1($this->request->content->entry->{$field->name}).'"';
					}
				else if($field->name!='id')
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
		// otherwise, we create one
		else
			{
			if($schema->table->nameField=='id')
				throw new RestException(RestCodes::HTTP_400,'The given table has no name field.');
			foreach($schema->table->fields as $field)
				{
				if($field->name!='id'&&$field->name!='password')
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
		$res=new RestResource(new RestRequest(RestMethods::GET,
			'/db/'.$this->request->database.'/'.$this->request->table
			.'/'.$this->request->entry.'.'.$this->request->fileExt.'?field=*'));
		$response=$res->getResponse();
		$response->code=RestCodes::HTTP_201;
		// Setting cache directives
		$uncache='/db/'.$this->request->database.'/'.$this->request->table.'/'
			.'|/fs/db/'.$this->request->database.'/'.$this->request->table.'/';
		foreach($schema->table->constraintFields as $field)
			{
			if(isset($field->joins))
				{
				foreach($field->joins as $join)
					{
					$uncache.='|/db/'.$this->request->database.'/'.$join->table.'/';
					}
				}
			elseif(isset($field->references))
				{
				foreach($field->references as $reference)
					{
					$uncache.='|/db/'.$this->request->database.'/'.$reference->table.'/';
					}
				}
			if(isset($field->link,$field->link->table))
				{
				$uncache.='|/db/'.$this->request->database.'/'.$field->link->table.'/';
				}
			}
		$response->setHeader('X-Rest-Uncache',$uncache);
		return $response;
		}
	function patch()
		{
		// Retrieving main table schema
		$schema=RestDbHelper::getTableSchema($this->request->database,
			$this->request->table);
		// Building the request
		$sqlRequest2='';
		$response=$this->head();
		if(isset($this->request->content->entry))
			{
			foreach($schema->table->fields as $field)
				{
				if(property_exists($this->request->content->entry,$field->name))
					{
					if($field->name=='password'||$field->name=='id')
						throw new RestException(RestCodes::HTTP_501,
							'Cannot modify fields like "'.$field->name.'" yet.');
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
				{
				$this->core->db->query('UPDATE `'.$this->request->table.'` SET'
					.$sqlRequest2.' WHERE id="'.$this->request->entry.'"');
				}
			}
		$response->code=RestCodes::HTTP_201;
		// Setting cache directives
		$uncache='/db/'.$this->request->database.'/'.$this->request->table.'/'
			.'|/fs/db/'.$this->request->database.'/'.$this->request->table.'/';
		foreach($schema->table->constraintFields as $field)
			{
			if(isset($field->joins))
				{
				foreach($field->joins as $join)
					{
					$uncache.='|/db/'.$this->request->database.'/'.$join->table.'/';
					}
				}
			elseif(isset($field->references))
				{
				foreach($field->references as $reference)
					{
					$uncache.='|/db/'.$this->request->database.'/'.$reference->table.'/';
					}
				}
			if(isset($field->link,$field->link->table))
				{
				$uncache.='|/db/'.$this->request->database.'/'.$field->link->table.'/';
				}
			}
		$response->setHeader('X-Rest-Uncache',$uncache);
		return $response;
		}
	function delete()
		{
		// Retrieving main table schema
		$schema=RestDbHelper::getTableSchema($this->request->database,
			$this->request->table);
		// Deleting the entry
		$this->core->db->selectDb($this->request->database);
		$this->core->db->query('DELETE FROM ' . $this->request->table .' WHERE id="'.$this->request->entry.'"');
		$res=new RestResource(new RestRequest(RestMethods::DELETE,'/fs/db/'.$this->request->database
			.'/'.$this->request->table.'/'.$this->request->entry.'/?recursive=yes'));
		$res=$res->getResponse();
		// Setting cache directives
		$uncache='/db/'.$this->request->database.'/'.$this->request->table.'/'
			.'|/fs/db/'.$this->request->database.'/'.$this->request->table.'/';
		foreach($schema->table->constraintFields as $field)
			{
			if(isset($field->joins))
				{
				foreach($field->joins as $join)
					{
					$uncache.='|/db/'.$this->request->database.'/'.$join->table.'/';
					}
				}
			elseif(isset($field->references))
				{
				foreach($field->references as $reference)
					{
					$uncache.='|/db/'.$this->request->database.'/'.$reference->table.'/';
					}
				}
			if(isset($field->link,$field->link->table))
				{
				$uncache.='|/db/'.$this->request->database.'/'.$field->link->table.'/';
				}
			}
		return new RestVarsResponse(RestCodes::HTTP_410,
			array('X-Rest-Uncache'=>$uncache,
				'Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	}
