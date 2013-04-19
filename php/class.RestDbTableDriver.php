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
		$drvInf->usage='/db/database/table(.ext)?';
		return $drvInf;
		}
	function head()
		{
		try
			{
			$this->core->db->selectDb($this->request->database);
			}
		catch(Exception $e)
			{
			throw new RestException(RestCodes::HTTP_410,'The given database does\'nt exist ('.$this->request->database.')');
			}
		try
			{
			$this->core->db->query('SHOW FULL COLUMNS FROM ' . $this->request->table);
			}
		catch(Exception $e)
			{
			throw new RestException(RestCodes::HTTP_410,'The given table does\'nt exist ('.$this->request->database.'.'.$this->request->table.')');
			}
		if(!$this->core->db->numRows())
			throw new RestException(RestCodes::HTTP_410,'The given table has no fields ('.$this->request->database.'.'.$this->request->table.')');
		return new RestResponseVars(RestCodes::HTTP_200,
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
			$response->vars->table->joinFields=new MergeArrayObject(array(),
				MergeArrayObject::ARRAY_MERGE_RESET|MergeArrayObject::ARRAY_MERGE_POP);
			$response->vars->table->labelFields=new MergeArrayObject(array(),
				MergeArrayObject::ARRAY_MERGE_RESET|MergeArrayObject::ARRAY_MERGE_POP);
			$response->vars->table->linkedTables=new MergeArrayObject(array(),
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
			$response->vars->table->fields=new MergeArrayObject(array(),
				MergeArrayObject::ARRAY_MERGE_RESET|MergeArrayObject::ARRAY_MERGE_POP);
			// Adding fields
			while ($row = $this->core->db->fetchArray())
				{
				$entry=new stdClass();
				$entry->name=$row['Field'];
				$entry->required=($row['Null']=='NO'?true:false);
				$entry->defaultValue=$row['Default'];
				if($entry->name=='label'||$row['Comment']=='label')
					$response->vars->table->labelFields->append($entry->name);
					$entry->unique=false;
				if($entry->name=='name'||$row['Key']=='UNI')
					{
					$entry->unique=true;
					if($entry->name=='name'||!$response->vars->table->nameField)
						$response->vars->table->nameField=$row['Field'];
					}
				else if($row['Key']=='PRI')
					{
					$entry->unique=true;
					}
				// Parsing field types and converting to internal types
				if(strpos($row['Type'], 'enum')===0)
					{
					$entry->options = new MergeArrayObject();
					foreach(explode(',', preg_replace('/([^a-zA-Z0-9,\/]+)/', '', str_replace('enum', '', $row['Type']))) as $value)
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
				else if(strpos($row['Type'], 'set')===0)
					{
					$entry->options = new MergeArrayObject();
					foreach(explode(',', preg_replace('/([^a-zA-Z0-9,\/]+)/', '', str_replace('set', '', $row['Type']))) as $value)
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
				else if(strpos($row['Type'], 'decimal')===0)
					{
					$entry->input = 'input';
					$entry->type = 'number';
					$entry->filter = 'float';
					$temp=explode(',', preg_replace('/([^0-9,])/', '', $row['Type']));
					$entry->max='';
					for($i=$temp[0]; $i>0; $i--)
						{ $entry->max.='9'; }
					$entry->max.='.';
					for($i=$temp[1]; $i>0; $i--)
						{ $entry->max.='9'; }
					if(strpos($row['Type'],'unsigned')!==false)
						{ $entry->min=0; }
					else
						{ $entry->min='-'.$entry->max; }
					$entry->decimals = (isset($temp[1])?$temp[1]:0);
					unset($temp);
					}
				else if(strpos($row['Type'], 'float')===0||strpos($row['Type'], 'double')===0)
					{
					$entry->input = 'input';
					$entry->type = 'number';
					$entry->filter = 'float';
					if(strpos($row['Type'], 'float')===0)
						$entry->precision=4;
					else if(strpos($row['Type'], 'double')===0)
						$entry->precision=8;
					if(strpos($row['Type'],'unsigned')!==false)
						{
						$entry->min=0;
						}
					if(strpos($entry->name,'lat')===0||$row['Comment']=='lat')
						{
						$hasLat=true;
						}
					else if(strpos($entry->name,'lng')===0||$row['Comment']=='lng')
						{
						$hasLng=true;
						}
					}
				else if(strpos($row['Type'], 'tinyint')===0||strpos($row['Type'], 'smallint')===0||strpos($row['Type'], 'mediumint')===0||strpos($row['Type'], 'int')===0||strpos($row['Type'], 'bigint')===0
					||strpos($row['Type'], 'year')===0)
					{
					$entry->input = 'input';
					$entry->type = 'number';
					$entry->filter = 'int';
					if(strpos($row['Type'], 'tinyint')===0)
						{
						if(strpos($row['Type'],'unsigned')!==false)
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
					else if(strpos($row['Type'], 'tinyint')===0)
						{
						if(strpos($row['Type'],'unsigned')!==false)
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
					else if(strpos($row['Type'], 'smallint')===0)
						{
						if(strpos($row['Type'],'unsigned')!==false)
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
					else if(strpos($row['Type'], 'mediumint')===0)
						{
						if(strpos($row['Type'],'unsigned')!==false)
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
					else if(strpos($row['Type'], 'int')===0)
						{
						if(strpos($row['Type'],'unsigned')!==false)
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
					else if(strpos($row['Type'], 'bigint')===0)
						{
						if(strpos($row['Type'],'unsigned')!==false)
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
				else if(strpos($row['Type'], 'date')===0||strpos($row['Type'], 'time')===0)
					{
					$entry->input='input';
					if($row['Type']=='datetime'||$row['Type']=='timestamp')
						{
						if($entry->defaultValue=='CURRENT_TIMESTAMP')
							$entry->defaultValue='';
						$entry->filter=$entry->type='datetime'; $entry->min=($entry->required?'1000-01-01 00:00:00':''); $entry->max='9999-12-31 23:59:59';
						} // YYYY-MM-DD HH:MM:SS
					else if($row['Type']=='date')
						{
						if($row['Comment']=='day')
							{ $entry->min='1900-01-01'; $entry->max='1900-12-31';  $entry->type='date'; $entry->filter='day'; }
						else
							{ $entry->filter=$entry->type='date'; $entry->min=($entry->required?'1000-01-01':''); $entry->max='9999-12-31'; } // YYYY-MM-DD
						}
					else if($row['Type']=='time') { $entry->min=($entry->required?'-838:59:59':''); $entry->max='838:59:59'; $entry->filter=$entry->type='time'; } // HH:MM:SS
					}
				else if(strpos($row['Type'], 'char')===0||strpos($row['Type'], 'varchar')===0)
					{
					$entry->input='input';
					$entry->type='text';
					$entry->filter='cdata';
					$entry->max=preg_replace('/([^0-9,])/', '', $row['Type']);
					if($entry->name=='password'||$row['Comment']=='iparameter')
						{
						$entry->filter = 'iparameter';
						$entry->pattern = '[a-zA-Z0-9_]+';
						}
					else if($entry->name=='name'||$entry->name=='lang'||$row['Comment']=='parameter'||$row['Comment']=='name')
						{
						$entry->filter = 'parameter';
						$entry->pattern = '[a-z0-9_]+';
						}
					else if(strpos($entry->name,'mail')===0||strpos($entry->name,'email')===0||$row['Comment']=='mail')
						{ $entry->type='email'; $entry->filter = 'mail'; }
					else if(strpos($entry->name,'url')===0||$row['Comment']=='httpuri')
						$entry->filter = 'httpuri';
					else if(strpos($entry->name,'uri')===0||$row['Comment']=='uri')
						$entry->filter = 'uri';
					else if(strpos($entry->name,'phone')===0||strpos($entry->name,'fax')===0||strpos($entry->name,'gsm')===0||$row['Comment']=='phone')
						{
						$entry->type = 'tel';
						$entry->filter = 'phone';
						$entry->pattern = '\+[0-9]{2,3}\.[0-9]+';
						}
					}
				else if($row['Type']=='tinyblob'||$row['Type']=='tinytext'||$row['Type']=='blob'
					||$row['Type']=='text'||$row['Type']=='mediumblob'||$row['Type']=='mediumtext'
					||$row['Type']=='longblob'||$row['Type']=='longtext')
					{
					$entry->input = 'textarea';
					$entry->type = 'text';
					$entry->filter = 'cdata';
					if($row['Comment'])
						{
						$entry->language = $row['Comment'];
						if($row['Comment']=='xhtml')
							$entry->filter = 'pcdata';
						else if($row['Comment']=='xhtmlnb')
							$entry->filter = 'nbpcdata';
						else if($row['Comment']=='xbbcode')
							$entry->filter = 'bbpcdata';
						else if($row['Comment']=='xbbcodenb')
							$entry->filter = 'bbnbpcdata';
						}
					if($row['Type']=='tinyblob'||$row['Type']=='tinytext') { $entry->max=255; }
					else if($row['Type']=='blob'||$row['Type']=='text') { $entry->max=65535; }
					else if($row['Type']=='mediumblob'||$row['Type']=='mediumtext') { $entry->max=16777215; }
					else if($row['Type']=='longblob'||$row['Type']=='longtext') { $entry->max=4294967295; }
					}
				// Looking for linked tables
				foreach(explode('|',$row['Comment']) as $comment)
					{
					if(strpos($comment, 'link:')===0)// link:categories.id
						{
						$entry->input = 'select';
						$entry->type = 'number';
						$entry->filter = 'int';
						$vars=explode('.',str_replace('link:', '', $comment));
						$entry->linkedTable = $vars[0];
						$entry->linkedField = (isset($vars[1])?$vars[1]:'id');
						if(!$response->vars->table->linkedTables->has($vars[0]))
							$response->vars->table->linkedTables->append($vars[0]);
						}
					else if(strpos($comment, 'ref:')!==false)// ref:table.field&table.field
						{
						$joins=explode('&',str_replace('ref:', '', $comment));
						$nj=1;
						foreach($joins as $join)
							{
							$vars=explode('.',$join);
							$entry2 = new stdClass();
							$entry2->name = 'refered_'.$vars[0];
							$entry2->input = 'select';
							$entry2->type = 'number';
							$entry2->filter = 'int';
							$entry2->linkedTable = $vars[0];
							$entry2->linkedField = (isset($vars[1])?$vars[1]:'id');
							$entry2->referedField = $entry->name;
							$entry2->multiple = true;
							$response->vars->table->joinFields->append($entry2);
							if(!$response->vars->table->linkedTables->has($vars[0]))
								$response->vars->table->linkedTables->append($vars[0]);
							$nj++;
							}
						}
					else if(strpos($comment, 'join:')===0)// join:table.field&table.field&table.field
						{
						$joins=explode('&',str_replace('join:', '', $comment));
						$nj=1;
						foreach($joins as $join)
							{
							$vars=explode('.',$join);
							$entry2 = new stdClass();
							$entry2->name = 'joined_'.$vars[0];
							$entry2->input = 'select';
							$entry2->type = 'number';
							$entry2->filter = 'int';
							$entry2->linkedTable = $vars[0];
							$entry2->linkedField = (isset($vars[1])?$vars[1]:'id');
							$entry2->joinedField = $entry->name;
							if($this->request->table>$vars[0])
								$entry2->joinTable = $vars[0].'_'.$this->request->table;
							else
								$entry2->joinTable = $this->request->table.'_'.$vars[0];
							$entry2->multiple = true;
							$response->vars->table->joinFields->append($entry2);
							if(!$response->vars->table->linkedTables->has($vars[0]))
								$response->vars->table->linkedTables->append($vars[0]);
							$nj++;
							}
						}
					if($row['Comment']=='name')
						$response->vars->table->nameField=$entry->name;
					}
				if($row['Field']=='reads') { $response->vars->table->hasCounter=true; }
				else if($row['Field']=='owner') { $response->vars->table->hasOwner=true; }
				else if($row['Field']=='lang') { $response->vars->table->isLocalized=true; }
				else if($row['Field']=='status') { $response->vars->table->hasStatus=true; }
				else if($row['Field']=='recipient') { $response->vars->table->hasRecipient=true; }
				else if($row['Field']=='votes') { $response->vars->table->hasVote=true; }
				else if($row['Field']=='notes') { $response->vars->table->hasNote=true; }
				else if($row['Field']=='lastmodified') { $response->vars->table->hasLastmodified=true; }
				else if($row['Field']=='created') { $response->vars->table->hasCreated=true; }
				else if($row['Field']=='idl') { $response->vars->table->hasIdl=true; }
				else if($row['Field']=='idr') { $response->vars->table->hasIdr=true; }
				else if($row['Field']=='level') { $response->vars->table->hasLevel=true; }
				else if($row['Field']=='lat') { $response->vars->table->hasLat=true; }
				else if($row['Field']=='lng') { $response->vars->table->hasLng=true; }
				if(!$response->vars->table->nameField)
					$response->vars->table->nameField='id';
				//$entry->oType=$row['Type'];
				//$entry->collation=$row['Collation'];
				//$entry->key=$row['Key'];
				//$entry->extra=$row['Extra'];
				//$entry->privileges=$row['Privileges'];
				//$entry->comment=$row['Comment'];
				$response->vars->table->fields->append($entry);
				}
			foreach($response->vars->table->joinFields as $field)
				$response->vars->table->fields->append($field);
				
			if($hasIdg&&$hasIdd&&$hasLevel)
				$response->vars->table->hasHierarchy=true;
			if($hasLat&&$hasLng)
				$response->vars->table->isGeolocalized=true;
			}

		return $response;
		}
	function post()
		{
		// why i didn't use $this->get()
		$res=new RestResource(new RestRequest(RestMethods::GET,
			'/db/'.$this->request->database.'/'.$this->request->table.'.dat'));
		$res=$res->getResponse();
		if($res->code!=RestCodes::HTTP_200)
			return $res;
		$tableFields=$res->getContents()->table->fields;
		$sqlRequest='';
		$sqlRequest2='';
		foreach($tableFields as $field)
			{
			if(strpos($field->name,'joined_')!==0
				&&strpos($field->name,'refered_')!==0&&$field->name!='id')
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
						$sqlRequest2.=($sqlRequest2?',':'').($value!=='NULL'?'"'.$value.'"':'NULL');
					else if(isset($this->request->content->entry->login))
						$sqlRequest2.=($sqlRequest2?',':'').'"'.md5($this->request->content->entry->login
							. ':' . $this->core->server->realm . ':' . $value).'"';
					else
						$sqlRequest2.=($sqlRequest2?',':'').'SHA1("'.$value.'")';
					}
				}
			}
		$sqlRequest='INSERT INTO `'.$this->request->table . '` (' . $sqlRequest . ') VALUES ('.$sqlRequest2.')';
		$this->core->db->query($sqlRequest);
		$this->request->entry = $this->core->db->insertId();
		foreach($tableFields as $field)
			{
			if(strpos($field->name,'joined_')===0
				&&isset($this->request->content->entry->{$field->name}))
				{
				foreach($this->request->content->entry->{$field->name} as $entry)
					{
					if(isset($entry->value)&&(xcUtilsInput::filterValue($entry->value,$field->type,$field->filter)
						||xcUtilsInput::filterValue($entry->value,$field->type,$field->filter)===0))
						{
						$this->core->db->query('INSERT INTO '.$field->joinTable
							.' ('.$field->linkedTable.'_id,'.$this->request->table.'_id)'
							.' VALUES ('.xcUtilsInput::filterValue($entry->value,$field->type,$field->filter)
								.','.$this->request->entry.')');
						}
					}
				}
			}
		$res=new RestResource(new RestRequest(RestMethods::GET,
			'/db/'.$this->request->database.'/'.$this->request->table
			.($this->request->entry?'/'.$this->request->entry:'').'.dat'));
		$response=$res->getResponse();
		$response->code=RestCodes::HTTP_201;
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
		return new RestResponse(RestCodes::HTTP_410,
			array('Content-Type'=>'text/varstream','X-Rest-Uncache'=>'/db/'.$this->request->database));
		}
	}
RestDbTableDriver::$drvInf=RestDbTableDriver::getDrvInf();