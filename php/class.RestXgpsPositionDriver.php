<?php
class RestXgpsPositionDriver extends RestDriver
{
  static $drvInf;
  public static function getDrvInf($methods=0)
  {
    $drvInf=new stdClass();
    $drvInf->name='Xgps: User Position Driver';
    $drvInf->description='Show last GPS entry for the given user.';
    $drvInf->usage='/xgps/(username)/position.txt?day=yyyy-mm-dd';
    $drvInf->methods=new stdClass();
    $drvInf->methods->options=new stdClass();
    $drvInf->methods->options->outputMimes='text/plain';
    $drvInf->methods->head=$drvInf->methods->get=new stdClass();
    $drvInf->methods->get->outputMimes='text/plain';
    $drvInf->methods->get->queryParams=new MergeArrayObject();
    $drvInf->methods->get->queryParams[0]=new stdClass();
    $drvInf->methods->get->queryParams[0]->name='day';
    $drvInf->methods->get->queryParams[0]->type='date';
    $drvInf->methods->get->queryParams[0]->filter='date';
    $drvInf->methods->get->queryParams[0]->required=true;
    $drvInf->methods->get->queryParams[0]->description='The day of the positions.';
    $drvInf->methods->get->queryParams[1]=new stdClass();
    $drvInf->methods->get->queryParams[1]->name='lastonly';
    $drvInf->methods->get->queryParams[1]->value='';
    $drvInf->methods->get->queryParams[1]->description='Send only the last position.';

    return $drvInf;
  }
  public function get()
  {
    $response=new RestResponse(
      RestCodes::HTTP_200,
      array('Content-Type'=>'text/plain')
    );
    $response->content='';
    $this->core->db->query('SELECT vehicles.device FROM users'
      .' LEFT JOIN vehicles ON vehicles.user=users.id'
      .' WHERE users.login="'.$this->request->user.'"');
    if(!$this->core->db->numRows())
      throw new RestException(RestCodes::HTTP_400,'User "'
                              .$this->request->user.'" does not exist.');
    if(!$device=$this->core->db->result('device'))
      throw new RestException(RestCodes::HTTP_400,'User "'
        .$this->request->user.'" have no device to ear.');
    $vals=explode('-',$this->queryParams->day);
    $filename='./log/x1-'.$device.'-'
      .date("Ymd", mktime(0, 0, 0, $vals[1] , $vals[2], $vals[0])).'.log';
    if (file_exists($filename)) {
      $content=@file_get_contents($filename);
      if ($this->queryParams->lastonly) {
        $lines=explode("\n",$content);
        $i=sizeof($lines)-1;
        while ($i>=0&&$lines[$i]=='') {
          $i--;
        }
        $response->content=$lines[$i];
      } else {
        $response->content=$content;
      }
    } else
      throw new RestException(RestCodes::HTTP_410,'User "'
        .$this->request->user.'" did not move that day.');

    return $response;
  }
}

