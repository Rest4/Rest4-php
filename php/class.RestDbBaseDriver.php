<?php
class RestDbBaseDriver extends RestVarsDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=parent::getDrvInf(RestMethods::GET|RestMethods::PUT|RestMethods::DELETE);
		$drvInf->name='Db: Database Driver';
		$drvInf->description='Manage a database and list all it\'s tables.';
		$drvInf->usage='/db/database'.$drvInf->usage;
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
				'The given database does\'nt exist ('.$e->__toString().')');
			}

		return new RestResponseVars(RestCodes::HTTP_200,
			array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	function get()
		{
		$response=$this->head();
		if($response->code==RestCodes::HTTP_200)
			{
			$response->vars->tables=new MergeArrayObject(array(),
				MergeArrayObject::ARRAY_MERGE_RESET|MergeArrayObject::ARRAY_MERGE_POP);
			while ($row = $this->core->db->fetchArray())
				{
				$entry=new stdClass();
				$entry->count= $row['Tables_in_'.$this->request->database];
				$entry->name= $row['Name'];
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
				'Got an error while creating the database ('.$e->__toString().').');
			}
		return new RestResponseVars(RestCodes::HTTP_201,
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
				'The given database could\'nt be delete ('.$e->__toString().')');
			}
		return new RestResponseVars(RestCodes::HTTP_200,
			array('X-Rest-Uncache'=>'/db',
				'Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
		}
	}
