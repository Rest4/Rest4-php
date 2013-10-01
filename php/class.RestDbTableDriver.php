<?php
class RestDbTableDriver extends RestVarsDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=parent::getDrvInf(RestMethods::GET|RestMethods::POST|
			RestMethods::PUT|RestMethods::DELETE);
		$drvInf->name='Db: Database Table Driver';
		$drvInf->description='Manage a table, list it\'s fields and add lines.';
		$drvInf->usage='/db/database/table'.$drvInf->usage;
		return $drvInf;
		}
	function head()
		{
		$this->core->db->selectDb('INFORMATION_SCHEMA');
		$this->core->db->query(
				'SELECT cols.COLUMN_NAME AS columnName, cols.ORDINAL_POSITION AS columnPosition,'
				.'	cols.COLUMN_DEFAULT AS defaultValue, cols.IS_NULLABLE as isNull, cols.DATA_TYPE AS dataType,'
				.'	cols.CHARACTER_MAXIMUM_LENGTH AS max, cols.CHARACTER_OCTET_LENGTH as min,'
				.'	cols.NUMERIC_PRECISION AS numericPrecision, cols.NUMERIC_SCALE AS scale,'
				.'	cols.COLUMN_TYPE AS colType, cols.COLUMN_KEY AS columnKey, cols.EXTRA AS extra,'
				.'	cols.COLUMN_COMMENT AS comment,'
				.'	refs.TABLE_NAME AS referredTable, refs.COLUMN_NAME AS referredColumn,'
				.'	cRefs.UPDATE_RULE AS referredUpdRule, cRefs.DELETE_RULE AS referredDelRule,'
				.'	links.REFERENCED_TABLE_NAME AS linkedTable,'
				.'	links.REFERENCED_COLUMN_NAME AS linkedColumn,'
				.'	cLinks.UPDATE_RULE AS linkedUpdRule, cLinks.DELETE_RULE AS linkedDelRule,'
				.'  joins.TABLE_NAME AS joinedTable,'
				.'	joins.COLUMN_NAME AS joinedColumn,'
				.'  cJoins.UPDATE_RULE AS joinedUpdRule, cJoins.DELETE_RULE AS joinedDelRule'
				.'	FROM `COLUMNS` as cols'
				// Linked fields
				.'	LEFT JOIN `KEY_COLUMN_USAGE` AS links'
				.'		ON links.REFERENCED_TABLE_SCHEMA="'.$this->request->database.'"'
				.'		AND links.TABLE_SCHEMA="'.$this->request->database.'"'
				.'    AND links.TABLE_NAME="'.$this->request->table.'"'
				.'    AND links.COLUMN_NAME=cols.COLUMN_NAME'
				.'    AND links.REFERENCED_TABLE_NAME NOT LIKE "%\_%"'
				.'	LEFT JOIN REFERENTIAL_CONSTRAINTS AS cLinks'
				.'		ON cLinks.CONSTRAINT_SCHEMA="'.$this->request->database.'"'
				.'    AND cLinks.CONSTRAINT_NAME=links.CONSTRAINT_NAME'
				// Referred fields
				.'	LEFT JOIN `KEY_COLUMN_USAGE` AS refs'
				.'		ON refs.REFERENCED_TABLE_SCHEMA="'.$this->request->database.'"'
				.'		AND refs.TABLE_SCHEMA="'.$this->request->database.'"'
				.'    AND refs.REFERENCED_TABLE_NAME="'.$this->request->table.'"'
				.'    AND refs.REFERENCED_COLUMN_NAME=cols.COLUMN_NAME'
				.'    AND refs.TABLE_NAME NOT LIKE "%\_%"'
				.'	LEFT JOIN REFERENTIAL_CONSTRAINTS AS cRefs'
				.'		ON cRefs.CONSTRAINT_SCHEMA="'.$this->request->database.'"'
				.'    AND cRefs.CONSTRAINT_NAME=refs.CONSTRAINT_NAME'
				// Joined fields
				.'	LEFT JOIN `KEY_COLUMN_USAGE` AS joins'
				.'		ON joins.REFERENCED_TABLE_SCHEMA="'.$this->request->database.'"'
				.'    AND joins.TABLE_SCHEMA="'.$this->request->database.'"'
				.'    AND joins.REFERENCED_TABLE_NAME="'.$this->request->table.'"'
				.'    AND joins.REFERENCED_COLUMN_NAME=cols.COLUMN_NAME'
				.'    AND joins.TABLE_NAME LIKE "%\_%"'
				//.'	LEFT JOIN IF(SUBSTRING_INDEX(joins.TABLE_NAME,"_", 1)<>"'.$this->request->table.'",
				//			SUBSTRING_INDEX(joins.TABLE_NAME,"_", 1),
				//			SUBSTRING_INDEX(joins.TABLE_NAME,"_", -1)
				//			) AS fJoins'
				//.'	ON'
				.'	LEFT JOIN REFERENTIAL_CONSTRAINTS AS cJoins'
				.'		ON cJoins.CONSTRAINT_SCHEMA="'.$this->request->database.'"'
				.'    AND cJoins.CONSTRAINT_NAME=joins.CONSTRAINT_NAME'
				.'	WHERE cols.TABLE_SCHEMA="'.$this->request->database.'"'
				.'	AND cols.TABLE_NAME="'.$this->request->table.'"'
				//.'	GROUP BY cols.COLUMN_NAME, cJoins.CONSTRAINT_NAME, cRefs.CONSTRAINT_NAME, cLinks.CONSTRAINT_NAME'
				//.'	ORDER BY cols.ORDINAL_POSITION ASC, cJoins.CONSTRAINT_NAME, cRefs.CONSTRAINT_NAME, cLinks.CONSTRAINT_NAME'
			);
		if(!$this->core->db->numRows())
			{
			throw new RestException(RestCodes::HTTP_410,'The given table does\'nt exist'
				.' ('.$this->request->database.'.'.$this->request->table.')');
			}
		return new RestVarsResponse(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	function get()
		{
		// Types	Subtypes
		// Inputs : input select 	
		
		$response=$this->head();
		if($response->code==RestCodes::HTTP_200)
			{
			// Setting table defaults
			$response->vars->table=new stdClass();
			$response->vars->table->nameField='';
			$response->vars->table->fields=new MergeArrayObject(array(),
				MergeArrayObject::ARRAY_MERGE_RESET|MergeArrayObject::ARRAY_MERGE_POP);
			$response->vars->table->constraintFields=new MergeArrayObject(array(),
				MergeArrayObject::ARRAY_MERGE_RESET|MergeArrayObject::ARRAY_MERGE_POP);
			$response->vars->table->labelFields=new MergeArrayObject(array(),
				MergeArrayObject::ARRAY_MERGE_RESET|MergeArrayObject::ARRAY_MERGE_POP);
			$response->vars->table->hasCounter=false;
			$response->vars->table->hasOwner=false;
			$response->vars->table->isLocalized=false;
			$response->vars->table->hasStatus=false;
			$response->vars->table->hasRecipient=false;
			$response->vars->table->hasVote=false;
			$response->vars->table->hasNote=false;
			$response->vars->table->hasLastmodified=false;
			$response->vars->table->hasCreated=false;
			$hasIdg=false;
			$hasIdd=false;
			$hasLevel=false;
			$response->vars->table->hasHierarchy=false;
			$hasLat=false;
			$hasLng=false;
			$response->vars->table->isGeolocalized=false;
			// Looping throught fields
			while ($row = $this->core->db->fetchArray())
				{
				// If field is readed for the first time
				if((!isset($entry))||$entry->name!=$row['columnName'])
					{
					$entry=new stdClass();
					$entry->name=$row['columnName'];
					$entry->required=($row['isNull']=='NO'?true:false);
					if($row['defaultValue']!='CURRENT_TIMESTAMP')
						{
						$entry->defaultValue=$row['defaultValue'];
						}
					if($entry->name=='label'||$row['comment']=='label')
						$response->vars->table->labelFields->append($entry->name);
					$entry->unique=false;
					if($entry->name=='name'||$row['columnKey']=='UNI')
						{
						$entry->unique=true;
						if($entry->name=='name'||!$response->vars->table->nameField)
							$response->vars->table->nameField=$entry->name;
						}
					else if($row['columnKey']=='PRI')
						{
						$entry->unique=true;
						}
					// Parsing field types and converting to internal types
					if(strpos($row['colType'], 'enum')===0)
						{
						$entry->options = new MergeArrayObject();
						foreach(explode(',', preg_replace('/([^a-zA-Z0-9,\/]+)/', '',
							str_replace('enum', '', $row['colType']))) as $value)
							{
							$opt=new stdClass();
							$opt->value = $value;
							$entry->options->append($opt);
							}
						$entry->input = 'select';
						$entry->type = 'text';
						$entry->filter = 'iparameter';
						$entry->multiple = false;
						unset($value,$opt);
						}
					else if(strpos($row['colType'], 'set')===0)
						{
						$entry->options = new MergeArrayObject();
						foreach(explode(',', preg_replace('/([^a-zA-Z0-9,\/]+)/', '',
							str_replace('set', '', $row['colType']))) as $value)
							{
							$opt=new stdClass();
							$opt->value = $value;
							$entry->options->append($opt);
							}
						$entry->input = 'select';
						$entry->type = 'text';
						$entry->filter = 'iparameter';
						$entry->multiple = true;
						unset($value,$opt);
						}
					else if(strpos($row['colType'], 'decimal')===0)
						{
						$entry->input = 'input';
						$entry->type = 'number';
						$entry->filter = 'float';
						$temp=explode(',', preg_replace('/([^0-9,])/', '', $row['colType']));
						$entry->max='';
						for($i=$temp[0]; $i>0; $i--)
							{ $entry->max.='9'; }
						$entry->max.='.';
						for($i=$temp[1]; $i>0; $i--)
							{ $entry->max.='9'; }
						if(strpos($row['colType'],'unsigned')!==false)
							{ $entry->min=0; }
						else
							{ $entry->min='-'.$entry->max; }
						$entry->decimals = (isset($temp[1])?$temp[1]:0);
						unset($temp);
						}
					else if(strpos($row['colType'], 'float')===0
						||strpos($row['colType'], 'double')===0)
						{
						$entry->input = 'input';
						$entry->type = 'number';
						$entry->filter = 'float';
						if(strpos($row['colType'], 'float')===0)
							$entry->precision=4;
						else if(strpos($row['colType'], 'double')===0)
							$entry->precision=8;
						if(strpos($row['colType'],'unsigned')!==false)
							{
							$entry->min=0;
							}
						if(strpos($entry->name,'lat')===0||$row['comment']=='lat')
							{
							$hasLat=true;
							}
						else if(strpos($entry->name,'lng')===0||$row['comment']=='lng')
							{
							$hasLng=true;
							}
						}
					else if(strpos($row['colType'], 'tinyint')===0
						||strpos($row['colType'], 'smallint')===0
						||strpos($row['colType'], 'mediumint')===0
						||strpos($row['colType'], 'int')===0
						||strpos($row['colType'], 'bigint')===0
						||strpos($row['colType'], 'year')===0)
						{
						$entry->input = 'input';
						$entry->type = 'number';
						$entry->filter = 'int';
						if(strpos($row['colType'], 'tinyint')===0)
							{
							if(strpos($row['colType'],'unsigned')!==false)
								{
								$entry->min=0;
								$entry->max=255;
								}
							else
								{
								$entry->min=-128;
								$entry->max=127;
								}
							}
						else if(strpos($row['colType'], 'tinyint')===0)
							{
							if(strpos($row['colType'],'unsigned')!==false)
								{
								$entry->min=0;
								$entry->max=255;
								}
							else
								{
								$entry->min=-128;
								$entry->max=127;
								}
							}
						else if(strpos($row['colType'], 'smallint')===0)
							{
							if(strpos($row['colType'],'unsigned')!==false)
								{
								$entry->min=0;
								$entry->max=65535;
								}
							else
								{
								$entry->min=-32768;
								$entry->max=32767;
								}
							}
						else if(strpos($row['colType'], 'mediumint')===0)
							{
							if(strpos($row['colType'],'unsigned')!==false)
								{
								$entry->min=0;
								$entry->max=16777215;
								}
							else
								{
								$entry->min=-8388608;
								$entry->max=8388607;
								}
							}
						else if(strpos($row['colType'], 'int')===0)
							{
							if(strpos($row['colType'],'unsigned')!==false)
								{
								$entry->min=0;
								$entry->max=4294967295;
								}
							else
								{
								$entry->min=-2147483648;
								$entry->max=2147483647;
								}
							}
						else if(strpos($row['colType'], 'bigint')===0)
							{
							if(strpos($row['colType'],'unsigned')!==false)
								{
								$entry->min=0;
								$entry->max=18446744073709551615;
								}
							else
								{
								$entry->min=-9223372036854775808;
								$entry->max=9223372036854775807;
								}
							}
						}
					else if(strpos($row['colType'], 'date')===0
						||strpos($row['colType'], 'time')===0)
						{
						$entry->input='input';
						if($row['colType']=='datetime'||$row['colType']=='timestamp')
							{
							$entry->filter=$entry->type='datetime';
							$entry->min=($entry->required?'1000-01-01 00:00:00':'');
							$entry->max='9999-12-31 23:59:59';
							} // YYYY-MM-DD HH:MM:SS
						else if($row['colType']=='date')
							{
							if($row['comment']=='day')
								{
								$entry->min='1900-01-01'; $entry->max='1900-12-31';
								$entry->type='date'; $entry->filter='day';
								}
							else
								{
								$entry->filter=$entry->type='date';
								$entry->min=($entry->required?'1000-01-01':'');
								$entry->max='9999-12-31';
								} // YYYY-MM-DD
							}
						else if($row['colType']=='time')
							{
							$entry->min=($entry->required?'-838:59:59':'');
							$entry->max='838:59:59';
							$entry->filter=$entry->type='time';
							} // HH:MM:SS
						}
					else if(strpos($row['colType'], 'char')===0
						||strpos($row['colType'], 'varchar')===0)
						{
						$entry->input='input';
						$entry->type='text';
						$entry->filter='cdata';
						$entry->max=preg_replace('/([^0-9,])/', '', $row['colType']);
						if($entry->name=='password'||$row['comment']=='iparameter')
							{
							$entry->filter = 'iparameter';
							$entry->pattern = '[a-zA-Z0-9_]+';
							}
						else if($entry->name=='name'||$entry->name=='lang'
							||$row['comment']=='parameter'||$row['comment']=='name')
							{
							$entry->filter = 'parameter';
							$entry->pattern = '[a-z0-9_]+';
							}
						else if(strpos($entry->name,'mail')===0
							||strpos($entry->name,'email')===0||$row['comment']=='mail')
							{ $entry->type='email'; $entry->filter = 'mail'; }
						else if(strpos($entry->name,'url')===0||$row['comment']=='httpuri')
							$entry->filter = 'httpuri';
						else if(strpos($entry->name,'uri')===0||$row['comment']=='uri')
							$entry->filter = 'uri';
						else if(strpos($entry->name,'phone')===0
							||strpos($entry->name,'fax')===0||strpos($entry->name,'gsm')===0
							||$row['comment']=='phone')
							{
							$entry->type = 'tel';
							$entry->filter = 'phone';
							$entry->pattern = '\+[0-9]{2,3}\.[0-9]+';
							}
						}
					else if($row['colType']=='tinyblob'||$row['colType']=='tinytext'
						||$row['colType']=='blob'||$row['colType']=='text'
						||$row['colType']=='mediumblob'||$row['colType']=='mediumtext'
						||$row['colType']=='longblob'||$row['colType']=='longtext')
						{
						$entry->input = 'textarea';
						$entry->type = 'text';
						$entry->filter = 'cdata';
						if($row['comment'])
							{
							$entry->language = $row['comment'];
							if($row['comment']=='xhtml')
								$entry->filter = 'pcdata';
							else if($row['comment']=='xhtmlnb')
								$entry->filter = 'nbpcdata';
							else if($row['comment']=='xbbcode')
								$entry->filter = 'bbpcdata';
							else if($row['comment']=='xbbcodenb')
								$entry->filter = 'bbnbpcdata';
							}
						if($row['colType']=='tinyblob'||$row['colType']=='tinytext')
							{ $entry->max=255; }
						else if($row['colType']=='blob'||$row['colType']=='text')
							{ $entry->max=65535; }
						else if($row['colType']=='mediumblob'||$row['colType']=='mediumtext')
							{ $entry->max=16777215; }
						else if($row['colType']=='longblob'||$row['colType']=='longtext')
							{ $entry->max=4294967295; }
						}
					// Adding flags
					if($row['columnName']=='reads')
						{ $response->vars->table->hasCounter=true; }
					else if($row['columnName']=='owner')
						{ $response->vars->table->hasOwner=true; }
					else if($row['columnName']=='lang')
						{ $response->vars->table->isLocalized=true; }
					else if($row['columnName']=='status')
						{ $response->vars->table->hasStatus=true; }
					else if($row['columnName']=='recipient')
						{ $response->vars->table->hasRecipient=true; }
					else if($row['columnName']=='votes')
						{ $response->vars->table->hasVote=true; }
					else if($row['columnName']=='notes')
						{ $response->vars->table->hasNote=true; }
					else if($row['columnName']=='lastmodified')
						{ $response->vars->table->hasLastmodified=true; }
					else if($row['columnName']=='created')
						{ $response->vars->table->hasCreated=true; }
					else if($row['columnName']=='idl')
						{ $response->vars->table->hasIdl=true; }
					else if($row['columnName']=='idr')
						{ $response->vars->table->hasIdr=true; }
					else if($row['columnName']=='level')
						{ $response->vars->table->hasLevel=true; }
					else if($row['columnName']=='lat')
						{ $response->vars->table->hasLat=true; }
					else if($row['columnName']=='lng')
						{ $response->vars->table->hasLng=true; }
					// Adding field to field list
					$response->vars->table->fields->append($entry);
					}
				// Setting links (0-1)
				if($row['linkedTable']&&!isset($entry->linkTo))
					{
					$entry->input = 'select';
					$entry->linkTo=new stdClass();
					if(false===in_array($entry,
						(array) $response->vars->table->constraintFields,true))
						{
						$response->vars->table->constraintFields->append($entry);
						}
					$entry->linkTo->table=$row['linkedTable'];
					$entry->linkTo->field=$row['linkedColumn'];
					$entry->linkTo->onDelete=strtolower($row['linkedUpdRule']);
					$entry->linkTo->onUpdate=strtolower($row['linkedDelRule']);
					$entry->linkTo->name=xcUtils::camelCase($row['columnName'],'link',
						$row['linkedTable'],$row['linkedColumn']);
					}
				// Setting references (0-n)
				if($row['referredTable'])
					{
					$in=false;
					if(!isset($entry->references))
						{
						$entry->references = new MergeArrayObject();
						if(false===in_array($entry,
							(array) $response->vars->table->constraintFields,true))
							{
							$response->vars->table->constraintFields->append($entry);
							}
						}
					else
						{
						foreach($entry->references as $curReference)
							{
							if($curReference->table==$row['referredTable']
								&&$curReference->field==$row['referredColumn'])
								{
								$in=true;
								}
							}
						}
					if(!$in)
						{
						$curReference=new stdClass();
						$curReference->table=$row['referredTable'];
						$curReference->field=$row['referredColumn'];
						$curReference->onUpdate=strtolower($row['referredUpdRule']);
						$curReference->onDelete=strtolower($row['referredDelRule']);
						$curReference->name=xcUtils::camelCase($row['columnName'],'refs',
							$row['referredTable'],$row['referredColumn']);
						$entry->references->append($curReference);
						}
					}
				// Setting joins (0-n)
				if($row['joinedTable'])
					{
					$in=false;
					if(!isset($entry->joins))
						{
						$entry->joins = new MergeArrayObject();
						if(false===in_array($entry,
							(array) $response->vars->table->constraintFields,true))
							{
							$response->vars->table->constraintFields->append($entry);
							}
						}
					else
						{
						foreach($entry->joins as $curJoin)
							{
							if($curJoin->bridge==$row['joinedTable']
								&&$curJoin->field=='id')
								{
								$in=true;
								}
							}
						}
					if(!$in)
						{
						$curJoin=new stdClass();
						$curJoin->bridge=$row['joinedTable'];
						$curJoin->table=(($tables=explode('_',$row['joinedTable']))
							&&$tables[0]!=$this->request->table? $tables[0] : $tables[1] );
						$curJoin->field='id';
						$curJoin->onUpdate=strtolower($row['joinedUpdRule']);
						$curJoin->onDelete=strtolower($row['joinedDelRule']);
						$curJoin->name=xcUtils::camelCase($row['columnName'],'joins',
							$curJoin->table,$curJoin->field);
						$entry->joins->append($curJoin);
						}
					}
				// Why not unions ?
				}
			if(!$response->vars->table->nameField)
				{
				$response->vars->table->nameField='id';
				}
			if(!$response->vars->table->labelFields->count())
				{
				$response->vars->table->labelFields->append('id');
				}
			if($hasIdg&&$hasIdd&&$hasLevel)
				{
				$response->vars->table->hasHierarchy=true;
				}
			if($hasLat&&$hasLng)
				{
				$response->vars->table->isGeolocalized=true;
				}
			}

		return $response;
		}
	function post()
		{
		// Retrieving table schema
		// Note: it doesn't use $this->get() to rely on cached table schema
		$schema=RestDbHelper::getTableSchema($this->request->database,
			$this->request->table);
		// Checking fields while building insert query
		$sqlRequest='';
		$sqlRequest2='';
		foreach($schema->table->fields as $field)
			{
			if($field->name!='id')
				{
				$sqlRequest.=($sqlRequest?',':'').'`'.$field->name.'`';
				if(isset($field->multiple)&&$field->multiple)
					{
					$sqlRequest2.=($sqlRequest2?',':'').'"';
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
						xcUtilsInput::filterValue($this->request->content->entry->{$field->name}
							,$field->type,$field->filter):'');
					if(!($value||$value===0||$value===floatval(0)||$value==='0'))
						$value='NULL';
					if(isset($field->required)&&$field->required&&$value==='NULL')
						{
						if($field->type=='date')
							$value=date('Y-m-d');
						else if($field->type=='timestamp'||$field->type=='datetime')
							$value=date('Y-m-d H:i:s');
						else
							throw new RestException(RestCodes::HTTP_400,
								'Malformed or ungiven required field ('.$this->request->table
								.':'.$field->name.':'.$value.':'
								.$this->request->content->entry->{$field->name}.')');
						}
					if($field->name!='password')
						{
						$sqlRequest2.=($sqlRequest2?',':'')
							.($value!=='NULL'?'"'.$value.'"':'NULL');
						}
					else if(isset($this->request->content->entry->login))
						{
						$sqlRequest2.=($sqlRequest2?',':'').'"'
							.md5($this->request->content->entry->login
							. ':' . $this->core->server->realm . ':' . $value).'"';
						}
					else
						{
						$sqlRequest2.=($sqlRequest2?',':'').'SHA1("'.$value.'")';
						}
					}
				}
			}
		$sqlRequest='INSERT INTO `'.$this->request->table . '` (' . $sqlRequest .
			') VALUES ('.$sqlRequest2.')';
		$this->core->db->selectDb($this->request->database);
		$this->core->db->query($sqlRequest);
		$this->request->entry = $this->core->db->insertId();
		// Attempting to insert joined entries
		foreach($schema->table->constraintFields as $field)
			{
			if(isset($field->joins,$this->request->content->entry->{$field->name.'Joins'}))
				{
				foreach($field->joins as $join)
					{
					if(isset($this->request->content->entry->{$join->name}))
						{
						foreach($this->request->content->entry->{$join->name} as $entry)
							{
							if(isset($entry->value)&&(xcUtilsInput::filterValue(
									$entry->value,$field->type,$field->filter)
								||xcUtilsInput::filterValue($entry->value,
									$field->type,$field->filter)===0))
								{
								$this->core->db->query('INSERT INTO '.$join->joinTable
									.' ('.$join->table.'_'.$join->field.','.$this->request->table
									.'_'.$field->name.') VALUES ('
									.xcUtilsInput::filterValue($entry->value,
										$field->type,$field->filter)
									.','.$this->request->entry.')');
								}
							}
						}
					}
				}
			}
		// Reading the new entry
		$res=new RestResource(new RestRequest(RestMethods::GET,
			'/db/'.$this->request->database.'/'.$this->request->table
			.($this->request->entry?'/'.$this->request->entry:'')
			.'.'.$this->request->fileExt.'?field=*'));
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
			if(isset($field->linkTo,$field->linkTo->table))
				{
				$uncache.='|/db/'.$this->request->database.'/'.$field->linkTo->table.'/';
				}
			}
		$response->setHeader('X-Rest-Uncache',$uncache);
		// Providing the new entry location
		$response->setHeader('Location',RestServer::Instance()->server->location
			.'db'.($this->request->database?'/'.$this->request->database:'')
			.($this->request->table?'/'.$this->request->table:'')
			.($this->request->entry?'/'.$this->request->entry:'')
			.($this->request->fileExt?'.'.$this->request->fileExt:''));
		return $response;
		}
	function put()
		{
		try
			{
			$this->core->db->selectDb($this->request->database);
			}
		catch(Exception $e)
			{
			throw new RestException(RestCodes::HTTP_410,
				'The given database does\'nt exist ('.$this->request->database.')');
			}
		$this->core->db->query('FLUSH TABLES');
		$this->core->db->query('CREATE TABLE IF NOT EXISTS `' . $this->request->table
			.'` (`id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,'
			.'`label` varchar(50) NOT NULL,'
			.' PRIMARY KEY (`id`)'
			.') ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1');
		$this->core->db->query('FLUSH TABLES');
		$response=$this->get();
		if($response->code!=RestCodes::HTTP_200)
			throw new RestException(RestCodes::HTTP_500,
				'The table could\'nt be created ('.$this->request->table.')');
		$response->code=RestCodes::HTTP_201;
		$response->setHeader('X-Rest-Uncache','/db/'.$this->request->database);
		return $response;
		}
	function delete()
		{
		try
			{
			$this->core->db->selectDb($this->request->database);
			}
		catch(Exception $e)
			{
			throw new RestException(RestCodes::HTTP_410,
				'The given database does\'nt exist ('.$this->request->database.')');
			}
		$this->core->db->query('DROP TABLE IF EXISTS ' . $this->request->table);
		$this->core->db->query('FLUSH TABLES');
		return new RestVarsResponse(RestCodes::HTTP_410,
			array('Content-Type'=>xcUtils::getMimeFromExt($this->request->fileExt),
				'X-Rest-Uncache'=>'/db/'.$this->request->database));
		}
	}
