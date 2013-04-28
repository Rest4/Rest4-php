<?php
class RestDbBaseDriver extends RestVarsDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=parent::getDrvInf(RestMethods::GET|RestMethods::PUT|RestMethods::DELETE);
		$drvInf->name='Db: Database Driver';
		$drvInf->description='Manage a database and list all it\'s tables.';
		$drvInf->usage='/db/database'.$drvInf->usage.'?mode=(count|full)';
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='mode';
		$drvInf->methods->get->queryParams[0]->values=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]->values[0]=
			$drvInf->methods->get->queryParams[0]->value='normal';
		$drvInf->methods->get->queryParams[0]->values[1]='count';
		$drvInf->methods->get->queryParams[0]->values[2]='full';
		return $drvInf;
		}
	function head()
		{
		try
			{
			$this->core->db->query('SHOW TABLE STATUS FROM ' . $this->request->database);
			}
		catch(Exception $e)
			{
			throw new RestException(RestCodes::HTTP_410,
				'The given database does\'nt exist.',$e->__toString());
			}

		return new RestVarsResponse(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	function get()
		{
		$response=$this->head();
		if($this->queryParams->mode=='count')
			$response->vars->count=$this->core->db->numRows();
		else
			{
			$response->vars->tables=new MergeArrayObject(array(),
				MergeArrayObject::ARRAY_MERGE_RESET|MergeArrayObject::ARRAY_MERGE_POP);
			while ($row = $this->core->db->fetchArray())
				{
				$entry=new stdClass();
				$entry->rows=$row['Rows'];
				$entry->name=$row['Name'];
				$entry->engine=$row['Engine'];
				if($this->queryParams->mode=='full')
					{
					$entry->version=$row['Version'];
					$entry->rowFormat=$row['Row_format'];
					$entry->averageRowsLength=$row['Avg_row_length'];
					$entry->dataLength=$row['Data_length'];
					$entry->maxDataLength=$row['Max_data_length'];
					$entry->indexLength=$row['Index_length'];
					$entry->dataFree=$row['Data_free'];
					$entry->autoIncrement=$row['Auto_increment'];
					$entry->create=$row['Create_time'];
					$entry->update=$row['Update_time'];
					$entry->check=$row['Check_time'];
					$entry->collation=$row['Collation'];
					$entry->checksum=$row['Checksum'];
					$entry->options=$row['Create_options'];
					}
				$entry->comment=$row['Comment'];
				$response->vars->tables->append($entry);
				}
			}
		return $response;
		}
	function put()
		{
		try
			{
			$this->core->db->query('CREATE DATABASE IF NOT EXISTS '
				.$this->request->database.' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');
			$this->core->db->query('FLUSH TABLES');
			}
		catch(Exception $e)
			{
			throw new RestException(RestCodes::HTTP_500,
				'Got an error while creating the database.',$e->__toString());
			}
		return new RestVarsResponse(RestCodes::HTTP_201,
			array('X-Rest-Uncache'=>'/db',
				'Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	function delete()
		{
		try
			{
			$this->core->db->query('DROP DATABASE IF EXISTS ' . $this->request->database);
			$this->core->db->query('FLUSH TABLES');
			}
		catch(Exception $e)
			{
			throw new RestException(RestCodes::HTTP_500,
				'The given database could\'nt be delete.',$e->__toString());
			}
		return new RestVarsResponse(RestCodes::HTTP_410,
			array('X-Rest-Uncache'=>'/db',
				'Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	}
