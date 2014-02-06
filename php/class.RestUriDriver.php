<?php
class RestUriDriver extends RestVarsDriver
{
  static $drvInf;
  public static function getDrvInf($methods=0)
  {
    $drvInf=parent::getDrvInf(RestMethods::GET|RestMethods::PUT);
    $drvInf->name='Uri: Diag';
    $drvInf->description='Show how the uri is decomposed by the request object,'
      . ' helps for unit tests.';
    $drvInf->usage='/uri'.$drvInf->usage;
    $drvInf->methods->get->queryParams=new MergeArrayObject();
    $drvInf->methods->get->queryParams[0]=new stdClass();
    $drvInf->methods->get->queryParams[0]->name='param1';
    $drvInf->methods->get->queryParams[0]->value='value';
    $drvInf->methods->get->queryParams[1]=new stdClass();
    $drvInf->methods->get->queryParams[1]->name='param2';
    $drvInf->methods->get->queryParams[1]->type='number';
    $drvInf->methods->get->queryParams[1]->filter='int';
    $drvInf->methods->get->queryParams[1]->value=
      $drvInf->methods->get->queryParams[1]->min=0;
    $drvInf->methods->get->queryParams[1]->max=1000;
    $drvInf->methods->get->queryParams[2]=new stdClass();
    $drvInf->methods->get->queryParams[2]->name='param3';
    $drvInf->methods->get->queryParams[2]->type='number';
    $drvInf->methods->get->queryParams[2]->filter='int';
    $drvInf->methods->get->queryParams[2]->value=5;
    $drvInf->methods->get->queryParams[3]=new stdClass();
    $drvInf->methods->get->queryParams[3]->name='multiparam';
    $drvInf->methods->get->queryParams[3]->type='number';
    $drvInf->methods->get->queryParams[3]->filter='int';
    $drvInf->methods->get->queryParams[3]->multiple=true;
    $drvInf->methods->get->queryParams[4]=new stdClass();
    $drvInf->methods->get->queryParams[4]->name='unordmultiparam';
    $drvInf->methods->get->queryParams[4]->type='number';
    $drvInf->methods->get->queryParams[4]->filter='int';
    $drvInf->methods->get->queryParams[4]->multiple=true;
    $drvInf->methods->get->queryParams[4]->orderless=true;
    $drvInf->methods->get->queryParams[4]->value=new MergeArrayObject();
    $drvInf->methods->get->queryParams[5]=new stdClass();
    $drvInf->methods->get->queryParams[5]->name='param4';
    $drvInf->methods->get->queryParams[5]->type='number';
    $drvInf->methods->get->queryParams[5]->filter='int';
    $drvInf->methods->get->queryParams[5]->value=1;
    $drvInf->methods->get->queryParams[6]=new stdClass();
    $drvInf->methods->get->queryParams[6]->name='param5';
    $drvInf->methods->get->queryParams[6]->type='text';
    $drvInf->methods->get->queryParams[6]->filter='cdata';
    $drvInf->methods->get->queryParams[6]->value='';
    $drvInf->methods->get->queryParams[7]=new stdClass();
    $drvInf->methods->get->queryParams[7]->name='param6';
    $drvInf->methods->get->queryParams[7]->values=new MergeArrayObject();
    $drvInf->methods->get->queryParams[7]->values[0]=
      $drvInf->methods->get->queryParams[7]->value='value1';
    $drvInf->methods->get->queryParams[7]->values[1]='value2';

    return $drvInf;
  }
  public function get()
  {
    $vars=new stdClass();
    $vars->nodes=$this->request->uriNodes;
    $vars->controller=$this->request->controller;
    $vars->filePath=$this->request->filePath;
    $vars->fileName=$this->request->fileName;
    $vars->isFolder=$this->request->isFolder;
    $vars->fileExt=$this->request->fileExt;
    $vars->queryString=$this->request->queryString;
    $vars->queryParams=new MergeArrayObject();
    foreach ($this::$drvInf->methods->get->queryParams as $queryParam) {
      if (isset($this->queryParams-> {$queryParam->name})) {
        $qP=new stdClass();
        $qP->name=$queryParam->name;
        $qP->value=$this->queryParams-> {$queryParam->name};
        $vars->queryParams->append($qP);
      }
    }

    return new RestVarsResponse(RestCodes::HTTP_200,
      array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)),
      $vars);
  }
  public function put()
  {
    $response=$this->get();
    // Show received content as it is
    $response->vars->content=$this->request->content;

    return $response;
  }
}

