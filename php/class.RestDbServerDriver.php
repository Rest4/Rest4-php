<?php
class RestDbServerDriver extends RestVarsDriver
{
  static $drvInf;
  public static function getDrvInf($methods=0)
  {
    $drvInf=parent::getDrvInf(RestMethods::GET|RestMethods::POST|RestMethods::PATCH);
    $drvInf->name='Db: Server Driver';
    $drvInf->description='List each databases of the SQL server.';
    $drvInf->usage='/db'.$drvInf->usage.'?mode=(count|full)';
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
  public function head()
  {
    try {
      $this->core->db->query(
        'SELECT SCHEMATA.CATALOG_NAME, SCHEMATA.SCHEMA_NAME,'
        .' 	SCHEMATA.DEFAULT_CHARACTER_SET_NAME,'
        .' 	SCHEMATA.DEFAULT_COLLATION_NAME, SCHEMATA.SQL_PATH,'
        .' 	count(TABLES.TABLE_NAME) as TABLES'
        .' FROM information_schema.SCHEMATA'
        .'	LEFT JOIN information_schema.TABLES'
        .'		ON TABLES.TABLE_SCHEMA=SCHEMATA.SCHEMA_NAME'
        .' GROUP BY SCHEMATA.SCHEMA_NAME'
      );
    } catch (Exception $e) {
      throw new RestException(RestCodes::HTTP_500,
        'Unable to get the database list.',$e->__toString());
    }

    return new RestVarsResponse(RestCodes::HTTP_200,
      array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
  }
  public function get()
  {
    $response=$this->head();
    if ($this->queryParams->mode=='count') {
      $response->vars->count=$this->core->db->numRows();
    } else {
      $response->vars->databases=new MergeArrayObject(array(),
          MergeArrayObject::ARRAY_MERGE_RESET|MergeArrayObject::ARRAY_MERGE_POP);
      while ($row = $this->core->db->fetchArray()) {
        $entry=new stdClass();
        $entry->database=$row['SCHEMA_NAME'];
        $entry->characterSet=$row['DEFAULT_CHARACTER_SET_NAME'];
        $entry->collation=$row['DEFAULT_COLLATION_NAME'];
        $entry->tables=$row['TABLES'];
        if ($this->queryParams->mode=='full') {
          $entry->catalog=$row['CATALOG_NAME'];
        }
        $response->vars->databases->append($entry);
      }
    }

    return $response;
  }
  public function patch()
  {
    throw new RestException(RestCodes::HTTP_501,'Not done yet');
    // Could allow to change databases collation
  }
}
