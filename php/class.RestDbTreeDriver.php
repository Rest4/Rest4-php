<?php
class RestDbTreeDriver extends RestVarsDriver
{
  static $drvInf;
  public function __construct(RestRequest $request)
  {
    parent::__construct($request);
  }
  public static function getDrvInf($methods=0)
  {
    $drvInf=parent::getDrvInf(RestMethods::GET);
    $drvInf->name='Db: Entry Driver';
    $drvInf->description='Get a table content as a tree.';
    $drvInf->usage='/db/database/table/tree'.$drvInf->usage
                   .'?field=field1&field=fiedl2&files=(count|list|include)';
    $drvInf->methods->get->queryParams=new MergeArrayObject();
    $drvInf->methods->get->queryParams[0]=new stdClass();
    $drvInf->methods->get->queryParams[0]->name='field';
    $drvInf->methods->get->queryParams[0]->filter='cdata';
    $drvInf->methods->get->queryParams[0]->multiple=true;
    $drvInf->methods->get->queryParams[1]=new stdClass();
    $drvInf->methods->get->queryParams[1]->name='files';
    $drvInf->methods->get->queryParams[1]->values=new MergeArrayObject();
    $drvInf->methods->get->queryParams[1]->values[0]=
      $drvInf->methods->get->queryParams[1]->value='ignore';
    $drvInf->methods->get->queryParams[1]->values[1]='count';
    $drvInf->methods->get->queryParams[1]->values[2]='list';
    $drvInf->methods->get->queryParams[1]->values[3]='include';

    return $drvInf;
  }
  public function head()
  {
    return new RestVarsResponse(RestCodes::HTTP_200,
      array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)));
  }
  public function get()
  {
    $res=new RestResource(new RestRequest(RestMethods::GET,
      '/db/'.$this->request->database.'/'.$this->request->table
      .'/list.'.$this->request->fileExt.'?field=*&'
      .($this->request->queryString?$this->request->queryString.'&':'')
      .'limit=0&orderby=leftId&dir=asc'));
    $response=$res->getResponse();
    if ($response->code==RestCodes::HTTP_200) {
      if ($response->vars->entries->count()) {
        $entries=$response->vars->entries;
        $response->vars->entries=new MergeArrayObject();
        $currentTree = array();
        $baseLevel = 0;
        $currentLevel = 0;
        $currentTree[$currentLevel] = $response->vars->entries;
        foreach ($entries as $entry) {
          if ($entry->deep<$baseLevel) {
            continue;
          }
          while ($currentLevel>$entry->deep-$baseLevel) { // Down in the tree
            $currentLevel--;
          }
          while ($currentLevel<$entry->deep-$baseLevel) { // Up in the tree
            $currentTree[$currentLevel][
              $currentTree[$currentLevel]->count()-1
            ]->childs = new MergeArrayObject();
            $currentLevel++;
            $currentTree[$currentLevel] = $currentTree[$currentLevel-1][
                sizeof($currentTree[$currentLevel-1])-1
              ]->childs;
          }
          $currentTree[$currentLevel]->append($entry);
        }
      }
    }

    return $response;
  }
}

