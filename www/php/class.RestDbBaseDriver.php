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
		$drvInf->methods->options->outputMimes='application/internal';
		$drvInf->methods->head=new stdClass();
		$drvInf->methods->head->outputMimes='application/internal';
		$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='application/internal';
		$drvInf->methods->put=new stdClass();
		$drvInf->methods->put->outputMimes='application/internal';
		$drvInf->methods->delete=new stdClass();
		$drvInf->methods->delete->outputMimes='application/internal';
		return $drvInf;
		}
	function head()
		{
		try
			{
			$this->core->db->query('SHOW TABLE STATUS FROM ' . $this->request->database);
			}
		catch(xcException $e)
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
			$response->content->tables=new xcObjectCollection();
			while ($row = $this->core->db->fetchArray())
				{
				$entry=new stdClass();
				//$entry->name= $row['Tables_in_'.$this->request->database];
				$entry->name= $row['Name'];
				$entry->count= $row['Rows'];
				$response->content->tables->append($entry);
				}
			$response->setHeader('Content-Type','application/internal');
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
		catch(xcException $e)
			{
			throw new RestException(RestCodes::HTTP_500,'Got an error while creating the database.');
			}
		return new RestResponse(RestCodes::HTTP_201,
			array('Content-Type'=>'application/internal','X-Rest-Uncache'=>'/db'));
		}
	function delete()
		{
		try
			{
			$this->core->db->query('DROP DATABASE IF EXISTS ' . $this->request->database);
			$this->core->db->query('FLUSH TABLES');
			}
		catch(xcException $e)
			{
			throw new RestException(RestCodes::HTTP_500,'The given database could\'nt be delete ('.$e->__toString().')');
			}
		return new RestResponse(RestCodes::HTTP_200,
			array('Content-Type'=>'application/internal','X-Rest-Uncache'=>'/db'));
		}
	}
RestDbBaseDriver::$drvInf=RestDbBaseDriver::getDrvInf();