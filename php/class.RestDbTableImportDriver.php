<?php
class RestDbTableImportDriver extends RestDriver
{
  static $drvInf;
  public static function getDrvInf($methods=0)
  {
    $drvInf=new stdClass();
    $drvInf->name='Db: Entries Import Driver';
    $drvInf->description='Import entries from a csv file.';
    $drvInf->usage='/db/database/table/import.txt';
    $drvInf->methods=new stdClass();
    $drvInf->methods->options=new stdClass();
    $drvInf->methods->options->outputMimes='text/plain';
    $drvInf->methods->head=$drvInf->methods->get=new stdClass();
    $drvInf->methods->get->outputMimes='text/plain';
    $drvInf->methods->post=new stdClass();
    $drvInf->methods->post->outputMimes='text/plain';
    $drvInf->methods->post->queryParams=new MergeArrayObject();
    $drvInf->methods->post->queryParams[0]=new stdClass();
    $drvInf->methods->post->queryParams[0]->name='file';
    $drvInf->methods->post->queryParams[0]->filter='uri';
    $drvInf->methods->post->queryParams[0]->value='';
    $drvInf->methods->post->queryParams[1]=new stdClass();
    $drvInf->methods->post->queryParams[1]->name='start';
    $drvInf->methods->post->queryParams[1]->type='number';
    $drvInf->methods->post->queryParams[1]->filter='int';
    $drvInf->methods->post->queryParams[1]->value=
      $drvInf->methods->post->queryParams[1]->min=0;
    $drvInf->methods->post->queryParams[2]=new stdClass();
    $drvInf->methods->post->queryParams[2]->name='limit';
    $drvInf->methods->post->queryParams[2]->type='number';
    $drvInf->methods->post->queryParams[2]->filter='int';
    $drvInf->methods->post->queryParams[2]->value=
      $drvInf->methods->post->queryParams[2]->min=0;
    $drvInf->methods->post->queryParams[3]=new stdClass();
    $drvInf->methods->post->queryParams[3]->name='simulation';
    $drvInf->methods->post->queryParams[3]->values=new MergeArrayObject();
    $drvInf->methods->post->queryParams[3]->values[0]=
      $drvInf->methods->post->queryParams[3]->value='no';
    $drvInf->methods->post->queryParams[3]->values[1]='yes';

    return $drvInf;
  }
  public function __construct(RestRequest $request)
  {
    parent::__construct($request);
    // Retrieving main table schema
    $res=new RestResource(new RestRequest(RestMethods::GET,
                                          '/db/'.$this->request->database.'/'.$this->request->table.'.dat'));
    $res=$res->getResponse();
    if ($res->code!=RestCodes::HTTP_200) {
      return $res;
    }
    $this->_schema=$res->getContents();
  }
  public function head()
  {
    return new RestResponse(
             RestCodes::HTTP_200,
             array('Content-Type'=>'text/plain')
           );
  }
  public function get()
  {
    $response=$this->head();
    $response->content='Ready for importation';

    return $response;
  }
  public function post()
  {
    $response=$this->head();
    $response->content='';
    $content='';
    if ($this->queryParams->file) {
      $file=new RestResource(new RestRequest(RestMethods::GET,$this->queryParams->file));
      $res=$res->getResponse();
      if ($res->code!=RestCodes::HTTP_200) {
        return $res;
      } else {
        $content=$res->getContents();
      }
    } else {
      $content=$this->request->content;
    }
    $links=array();
    $lines=preg_split('/\r?\n/',$content);
    $csvfields=explode(';',$lines[0]);
    $k=sizeof($csvfields);
    $sqlRequest1='';
    for ($j=0; $j<$k; $j++) {
      $exist=false;
      foreach ($this->_schema->table->fields as $field) {
        if ($field->name==$csvfields[$j]) {
          $exist=true;
          $links[$csvfields[$j]]['type']=$field->type;
          $links[$csvfields[$j]]['filter']=$field->filter;
          $sqlRequest1.=($sqlRequest1?', ':'').'`'.$csvfields[$j].'`';
        } else
          if(isset($field->linkedTable)&&(strpos($csvfields[$j],$field->name.'_')===0
                                          ||strpos($csvfields[$j],'joined_'.$field->linkedTable)===0)) {
            if (!isset($ {$field->linkedTable.'Res'})) {
              $ {$field->linkedTable.'Res'}=new RestResource(new RestRequest(
                    RestMethods::GET,'/db/'.$this->request->database.'/'.$field->linkedTable.'.dat'));
              $ {$field->linkedTable.'Res'}=$ {$field->linkedTable.'Res'}->getResponse();
              if($ {$field->linkedTable.'Res'}->code!=RestCodes::HTTP_200)

                return $ {$field->linkedTable.'Res'};
            }
            foreach ($ {$field->linkedTable.'Res'}->getContents()->table->fields as $linkedField) {
              if ($csvfields[$j]==$field->name.'_'.$linkedField->name) {
                $exist=true;
                $links[$csvfields[$j]]=array();
                $links[$csvfields[$j]]['table']=$field->linkedTable;
                $links[$csvfields[$j]]['field']=$linkedField->name;
                $links[$csvfields[$j]]['type']=$linkedField->type;
                $links[$csvfields[$j]]['filter']=$linkedField->filter;
                if (isset($field->joinTable)) {
                  $links[$csvfields[$j]]['jointable']=$field->joinTable;
                } else {
                  $sqlRequest1.=($sqlRequest1?', ':'').'`'.$field->name.'`';
                }
              }
            }
          }
      }
      if(!$exist)
        throw new RestException(RestCodes::HTTP_400,
                                'The given field does\'nt exist ('.$csvfields[$j].')');
    }
    for ($i=1; $i<sizeof($lines); $i++) {
      if($i>$this->queryParams->start&&($this->queryParams->limit==0
                                        ||$i<=($this->queryParams->start+$this->queryParams->limit))) {
        $sqlRequest2='';
        $fieldvals=explode(';',$lines[$i]);
        //preg_match_all('/("(.*)"|[^;]+)(;|$)/',$lines[$i],$fieldvals);
        //$fieldvals=$fieldvals[1];
        $l=sizeof($fieldvals);
        if($l!=$k)
          throw new RestException(RestCodes::HTTP_400,
                                  'Does not match the declared field count ('.$l.'/'.$k.') at line '.$i.'.');
        try {
          for ($j=0; $j<$k; $j++) {
            if ($fieldvals[$j]&&$fieldvals[$j][0]=='"'&&$fieldvals[$j][sizeof($fieldvals[$j])-1]=='"') {
              $fieldvals[$j]=substr($fieldvals[$j],1,sizeof($fieldvals[$j])-2);
            }
            $fieldvals[$j]=xcUtilsInput::filterValue(
                             $fieldvals[$j],$links[$csvfields[$j]]['type'],$links[$csvfields[$j]]['filter']);
            if (!isset($links[$csvfields[$j]]['table'])) {
              $sqlRequest2.=($sqlRequest2?', ':'').'"'.$fieldvals[$j].'"';
            } else {
              $this->core->db->query('SELECT id FROM '.$links[$csvfields[$j]]['table']
                                     .' WHERE '.$links[$csvfields[$j]]['field'].'="'.$fieldvals[$j].'"');
              if(!$this->core->db->numRows())
                throw new RestException(RestCodes::HTTP_400,
                                        'Couldn\'t find the linked row for the field value given ("'.$fieldvals[$j].'")'
                                        .' for the linked field ('.$csvfields[$j].') at line '.$i.'.');
              if (!isset($links[$csvfields[$j]]['jointable'])) {
                $sqlRequest2.=($sqlRequest2?', ':'').'"'.$this->core->db->result('id').'"';
              } else {
                $links[$csvfields[$j]]['joinid']=$this->core->db->result('id');
              }
            }
          }
          $sqlRequest='INSERT INTO '.$this->request->table.' ('.$sqlRequest1
                      .') VALUES ('.$sqlRequest2.');';
          $response->content.=$sqlRequest."\n";
          if ($this->queryParams->simulation==0) {
            $this->core->db->query($sqlRequest);
            $id=$this->core->db->insertId($sqlRequest);
          } else {
            $id='[last_id]';
          }
          for ($j=0; $j<$k; $j++) {
            if (isset($links[$csvfields[$j]]['jointable'])) {
              $sqlRequest='INSERT INTO '.$links[$csvfields[$j]]['jointable']
                .' ('.$links[$csvfields[$j]]['table'].'_id,'.$this->request->table.'_id)'
                .' VALUES ('.$links[$csvfields[$j]]['joinid'].','.$id.')';
              $response->content.=$sqlRequest."\n";
              if ($this->queryParams->simulation==0) {
                $this->core->db->query($sqlRequest);
              }
            }
          }
        } catch (Exception $e) {
          throw new RestException(RestCodes::HTTP_500,
            'Got a SQL error.','Line '.$i.': '.$e->__toString());
        }
      }
    }
    $response->setHeader('X-Rest-Uncache',
      '/db/'.$this->request->database.'/'.$this->request->table.'/');

    return $response;
  }
}
