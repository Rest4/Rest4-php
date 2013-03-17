<?php
class RestDbBaseDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='Db: Database Driver';
		$drvInf->description='Manage a database and list all it\'s tables.';
		$drvInf->usage='/db/database(.ext)?';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='text/varstream';
		$drvInf->methods->head=new stdClass();
		$drvInf->methods->head->outputMimes='text/varstream';
		$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='text/varstream';
		$drvInf->methods->put=new stdClass();
		$drvInf->methods->put->outputMimes='text/varstream';
		$drvInf->methods->delete=new stdClass();
		$drvInf->methods->delete->outputMimes='text/varstream';
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
			throw new RestException(RestCodes::HTTP_410,'The given database does\'nt exist ('.$e->__toString().')');
			}

		return new RestResponse(
			RestCodes::HTTP_200,
			array('Content-Type'=>'text/plain')
			);
		}
	function get()
		{
		$response=$this->head();
		if($response->code==RestCodes::HTTP_200)
			{
			$response->content=new stdClass();
			$response->content->tables=new MergeArrayObject(array(),
				MergeArrayObject::ARRAY_MERGE_RESET|MergeArrayObject::ARRAY_MERGE_POP);
			while ($row = $this->core->db->fetchArray())
				{
				$entry=new stdClass();
				//$entry->name= $row['Tables_in_'.$this->request->database];
				$entry->name= $row['Name'];
				$entry->count= $row['Rows'];
				$response->content->tables->append($entry);
				}
			$response->setHeader('Content-Type','text/varstream');
			}

		return $response;
		}
	function put()
		{
		try
			{
			$this->core->db->query('CREATE DATABASE IF NOT EXISTS ' . $this->request->database . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');
			$this->core->db->query('FLUSH TABLES');
			}
		catch(Exception $e)
			{
			throw new RestException(RestCodes::HTTP_500,'Got an error while creating the database.');
			}
		return new RestResponse(RestCodes::HTTP_201,
			array('Content-Type'=>'text/varstream','X-Rest-Uncache'=>'/db'));
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
			throw new RestException(RestCodes::HTTP_500,'The given database could\'nt be delete ('.$e->__toString().')');
			}
		return new RestResponse(RestCodes::HTTP_200,
			array('Content-Type'=>'text/varstream','X-Rest-Uncache'=>'/db'));
		}
	}
RestDbBaseDriver::$drvInf=RestDbBaseDriver::getDrvInf();