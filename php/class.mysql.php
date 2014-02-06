<?php
class mysql
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

  public function __construct($config,$core)
  {
    $this->config=$config;
    $this->core=$core;
  }

  public function __destruct()
  {
    foreach ($this->links as $link) {
      $this->close($link);
    }
  }

  // Connections
  public function connect($host, $user, $password, $database = false)
  {
    if (!($this->links[sizeof($this->links)] = @mysql_connect($host, $user, $password))) {
      throw new Exception('Mysql_connect -> Host: ' . $host . ' User: ' . $user
                          . ' Error: ' . mysql_errno() . ' - ' . mysql_error());
    } else {
      $this->cLink=&$this->links[sizeof($this->links)-1];
      $this->query('SET NAMES \'utf8\';');
    }
  }

  public function openDefaultLink()
  {
    $this->connect($this->config->host, $this->config->user, $this->config->password);
    $this->selectDb($this->config->database);
  }

  public function close($link=false)
  {
    if ($link&&!in_array($link, $this->links)) {
      throw new Exception('Mysql_close -> Message: Given link does not exists Error.');
    }
    if (!@mysql_close(($link?$link:$this->cLink))) {
      throw new Exception('Mysql_close -> Database: ' . $this->config->database
                          . ' Error: ' . mysql_errno() . ' - ' . xcUtilsInput::filterAsCdata(mysql_error()));
    } else {
      if ($link&&$link!=$this->cLink) {
        unset($this->links[array_search($link,$this->links)]);
      } else {
        array_pop($this->links);
        if (sizeof($this->links)>0) {
          $this->cLink=$this->links[sizeof($this->links)-1];
        } else {
          $this->cLink=false;
        }
      }
    }
  }

  // Databases
  public function selectDb($database, $link=false)
  {
    if (!($link||$this->cLink)) {
      $this->openDefaultLink();
    }
    if (!@mysql_select_db($database,($link?$link:$this->cLink))) {
      throw new Exception('Mysql_selectDb -> Database: ' . $database
                          . ' Error: ' . mysql_errno() . ' - ' . xcUtilsInput::filterAsCdata(mysql_error()));
    } else {
      $this->databases[sizeof($this->databases)]=$database;
      $this->cDatabase=&$this->databases[sizeof($this->databases)-1];
    }
  }

  public function closeDb($database,$link=false)
  {
    array_pop($this->databases);
    $this->cDatabase&=$this->databases[sizeof($this->databases)-1];
  }

  // Functions
  public function query($request, $link=false)
  {
    if (!($link||$this->cLink)) {
      $this->openDefaultLink();
    }
    $this->requests[sizeof($this->requests)]=$request;
    $this->cRequest = $request;
    if(!($this->queries[sizeof($this->queries)] =
           @mysql_query($request,($link?$link:$this->cLink)))) {
      throw new Exception('Mysql_query -> Error :' . mysql_errno()
                          . ' - ' . xcUtilsInput::filterAsCdata(mysql_error())
                          . ' Request: ' . xcUtilsInput::filterAsCdata($this->cRequest));
    } else {
      $this->cQuery=&$this->queries[sizeof($this->queries)-1];

      return $this->cQuery;
    }
  }
  public function fetchArray($query = false)
  {
    if ($query) {
      $this->cQuery = $query;
    }

    return mysql_fetch_array($this->cQuery, MYSQL_ASSOC);
  }
  public function freeResult($query = false)
  {
    if ($query) {
      $this->cQuery = $query;
    }
    if ($this->cResult = @mysql_free_result($this->cQuery)) {
      return $this->cResult;
    } else
      throw new Exception('Mysql_freeResult -> Error :' . mysql_errno()
                          . ' - ' . xcUtilsInput::filterAsCdata(mysql_error())
                          . ' Request: ' . xcUtilsInput::filterAsCdata($this->cRequest));
  }
  public function numRows($query = false)
  {
    if ($query) {
      $this->cQuery = $query;
    }
    if (($this->cResult = @mysql_num_rows($this->cQuery))>=0) {
      return $this->cResult;
    } else
      throw new Exception('Mysql_numRows -> Error :' . mysql_errno()
                          . ' - ' . xcUtilsInput::filterAsCdata(mysql_error())
                          . ' Request: ' . xcUtilsInput::filterAsCdata($this->cRequest));
  }
  public function result($field, $row =  0, $query=false)
  {
    if ($query) {
      $this->cQuery = $query;
    }
    if(($this->cResult =
          @mysql_result($this->cQuery, $row, $field))===false&&mysql_error()) {
      throw new Exception('Mysql_result -> Row:' . $row . ' Field: ' . $field
                          . ' Error: ' . mysql_errno() . ' - ' . xcUtilsInput::filterAsCdata(mysql_error())
                          . ' Request: ' . xcUtilsInput::filterAsCdata($this->cRequest));
    } else {
      return $this->cResult;
    }
  }
  public function insertId()
  {
    return mysql_insert_id();
  }
  public function affectedRows()
  {
    return mysql_affected_rows();
  }
}

