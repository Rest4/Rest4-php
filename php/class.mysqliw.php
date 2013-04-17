<?php
class mysqliw
	{
	private $config;
	private $core;

	public $links = array();
	public $connected = false;
	public $cLink = false;

	public $databases = array();
	public $cDatabase = false;

	public $requests = array();
	public $cRequest = false;

	public $queries = array();
	public $cQuery = false;

	public $cResult = false;

	function __construct($config,$core)
		{
		$this->config=$config;
		$this->core=$core;
		}

	function __destruct()
		{
		foreach($this->links as $link)
			{
			$this->close($link);
			}
		}


	// Connections
	function connect($host, $user, $password, $database = false)
		{
		$this->cLink=$this->links[sizeof($this->links)]=@mysqli_init();
		if(!($this->cLink&&@mysqli_real_connect($this->cLink,$host, $user, $password)))
			{
			array_pop($this->links);
			throw new Exception('Mysql_connect -> Host: ' . $host
				. ' User: ' . $user . ' Error: ' . mysqli_errno($this->cLink)
				. ' - ' . mysqli_error($this->cLink));
			}
		else
			{
			$this->query('SET NAMES \'utf8\';');
			}
		}

	function openDefaultLink()
		{
		$this->connect($this->config->host, $this->config->user, $this->config->password);
		$this->selectDb($this->config->database);
		}

	function close($link=false)
		{
		if($link&&!in_array($link, $this->links))
			{
			throw new Exception('Mysql_close -> Message: Given link does not exists Error.');
			}
		if(!@mysqli_close(($link?$link:$this->cLink)))
			{
			throw new Exception('Mysql_close -> Database: '
				. $this->config->database . ' Error: ' . mysqli_errno($this->cLink)
				. ' - ' . xcUtilsInput::filterAsCdata(mysqli_error($this->cLink)));
			}
		else
			{
			if($link&&$link!=$this->cLink)
				{
					unset($this->links[array_search($link,$this->links)]);
				}
			else
				{
				array_pop($this->links);
				if(sizeof($this->links)>0)
					$this->cLink=$this->links[sizeof($this->links)-1];
				else
					$this->cLink=false;
				}
			}
		}

	// Databases
	function selectDb($database, $link=false)
		{
		if(!($link||$this->cLink))
			$this->openDefaultLink();
		if(!@mysqli_select_db(($link?$link:$this->cLink),$database))
			{
			throw new Exception('Mysql_selectDb -> Database: ' . $database
				. ' Error: ' . mysqli_errno($this->cLink) . ' - '
				. xcUtilsInput::filterAsCdata(mysqli_error($this->cLink)));
			}
		else
			{
			$this->databases[sizeof($this->databases)]=$database;
			$this->cDatabase=&$this->databases[sizeof($this->databases)-1];
			}
		}

	function closeDb($database,$link=false)
		{
		array_pop($this->databases);
		$this->cDatabase&=$this->databases[sizeof($this->databases)-1];
		}

	// Functions
	function query($request, $link=false)
		{
		if(!($link||$this->cLink))
			$this->openDefaultLink();
		$this->requests[sizeof($this->requests)]=$request;
		$this->cRequest = $request;
		if(!($this->queries[sizeof($this->queries)] =
			mysqli_query(($link?$link:$this->cLink),$request)))
			{
			throw new Exception('Mysql_query -> Error :' . mysqli_errno($this->cLink)
				. ' - ' . xcUtilsInput::filterAsCdata(mysqli_error($this->cLink))
				. ' Request: ' . xcUtilsInput::filterAsCdata($this->cRequest));
			}
		else
			{
			$this->cQuery=&$this->queries[sizeof($this->queries)-1];
			return $this->cQuery;
			}
		}
	function fetchArray($query = false)
		{
		if($query) { $this->cQuery = $query; }
		return mysqli_fetch_array($this->cQuery,MYSQLI_ASSOC);
		}
	function freeResult($query = false)
		{
		if($query) { $this->cQuery = $query; }
		@mysqli_free_result($this->cQuery);
		}
	function numRows($query = false)
		{
		if($query) { $this->cQuery = $query; }
		if(($this->cResult = @mysqli_num_rows($this->cQuery))>=0)
			{
			return $this->cResult;
			}
		else
			throw new Exception('Mysql_numRows -> Error :' . mysqli_errno($this->cLink)
				. ' - ' . xcUtilsInput::filterAsCdata(mysqli_error($this->cLink))
				. ' Request: ' . xcUtilsInput::filterAsCdata($this->cRequest));
		}
	function result($field, $row =  0, $query=false)
		{
		if($query) { $this->cQuery = $query; }
		mysqli_data_seek($this->cQuery,$row);
		$row=mysqli_fetch_array($this->cQuery,MYSQLI_ASSOC);
		if(mysqli_error($this->cLink))
			{
			throw new Exception('Mysql_result -> Row:' . $row . ' Field: ' . $field
				. ' Error: ' . mysqli_errno($this->cLink) . ' - '
				. xcUtilsInput::filterAsCdata(mysqli_error($this->cLink))
				. ' Request: ' . xcUtilsInput::filterAsCdata($this->cRequest));
			}
		else
			{
			return (isset($row[$field])?$row[$field]:$row[substr($field,strrpos($field,'.')+1)]);
			}
		}
	function insertId()
		{
		return mysqli_insert_id($this->cLink);
		}
	function affectedRows()
		{
		return mysqli_affected_rows($this->cLink);
		}
	function hasUpdatedContent()
		{
		$updated=false;
		for($i=sizeof($this->requests)-1; $i>=0; $i--)
			{
			if(strpos($this->requests[$i],'UPDATE')===0
				||strpos($this->requests[$i],'INSERT')===0
				||strpos($this->requests[$i],'DELETE')===0)
				{
				if(strpos($this->requests[$i],'document')>=0)
					{
					return 2;
					}
				else if(strpos($this->requests[$i],'visitor')===false)
				$updated=true;
				}
			}
		if($updated)
			return 1;
		return 0;
		}
	}