<?php
class RestAuthTokenDriver extends RestVarsDriver
{
  static $drvInf;
  public static function getDrvInf($methods=0)
  {
    $drvInf=parent::getDrvInf(RestMethods::GET|RestMethods::POST
      |RestMethods::DELETE);
    $drvInf->name='Session: Session Driver';
    $drvInf->description='Bring/check sessions.';
    $drvInf->usage='/session'.$drvInf->usage.'?sessid=([a-z0-9])'
      .'&redirect=url';
    $drvInf->methods=new stdClass();
    $drvInf->methods->options=new stdClass();
    $drvInf->methods->options->outputMimes='text/varstream';
    $drvInf->methods->head =
      $drvInf->methods->get =
        $drvInf->methods->post = new stdClass();
    $drvInf->methods->get->outputMimes='text/plain';
    $drvInf->methods->get->queryParams=new MergeArrayObject();
    $drvInf->methods->get->queryParams[0]=new stdClass();
    $drvInf->methods->get->queryParams[0]->name='sessid';
    $drvInf->methods->get->queryParams[0]->filter='iparameter';
    $drvInf->methods->get->queryParams[0]->required=true;
    $drvInf->methods->get->queryParams[1]=new stdClass();
    $drvInf->methods->get->queryParams[1]->name='success';
    $drvInf->methods->get->queryParams[1]->filter='weburi';
    $drvInf->methods->get->queryParams[1]->value='';
    $drvInf->methods->get->queryParams[2]=new stdClass();
    $drvInf->methods->get->queryParams[2]->name='fail';
    $drvInf->methods->get->queryParams[2]->filter='weburi';
    $drvInf->methods->get->queryParams[2]->value='';

    return $drvInf;
  }
  public function get()
  {
    $this->core->db->selectDb($this->core->database->database);

    $this->core->db->query(
      'SELECT id'
      .' FROM visitors'
      .' WHERE visitors.sessid="'.$this->queryParams->sessid.'"'
      .' AND lastrequest>DATE_SUB(NOW(), INTERVAL 1 DAY)');

    if(!$this->core->db->numRows()) {

      return new RestResponse(RestCodes::HTTP_301,
        array(
          'Content-Type' => 'text/plain',
          'Location' => $this->queryParams->success
        ),
      'Invalid or outdated session, redirecting...'
      );
    }

    return new RestResponse(RestCodes::HTTP_301,
      array(
        'Content-Type' => 'text/plain',
        'Location' => $this->queryParams->success,
        'Set-Cookie' => 'sessid=' . $this->queryParams->sessid . '; Path=/;'
      ),
      'Session found, redirecting...'
    );
  }
  public function post()
  {
    if('db'!==$this->queryParams->source) {
      throw new RestException(RestCodes::HTTP_400,'Unsupported auth source "'
        .$this->queryParams->source.'".');
    }
    if(isset($this->request->content->username,
             $this->request->content->password)) {
      // Checking credentials
      $this->core->db->selectDb($this->core->database->database);
      $this->core->db->query('SELECT id, login FROM users WHERE login="'
        .xcUtilsInput::filterValue($this->request->content->username)
        .'" AND (password="'.sha1($this->request->content->password)
        .'" OR password="'.md5($this->request->content->username.':'
          . $this->core->auth->realm . ':' . $this->request->content->password)
        .'")');
      if($this->core->db->numRows()) {
        $vars=new stdClass();
        $vars->message='Successfully logged in.';
        $vars->sessid=self::createSessid();
        $vars->id=$this->core->db->result('users.id');
        $vars->login=$this->core->db->result('users.login');
        if(isset($_SERVER['REMOTE_ADDR'])&&ip2long($_SERVER['REMOTE_ADDR'])) {
          $vars->ip=ip2long($_SERVER['REMOTE_ADDR']);
        } else {
          $vars->ip=ip2long('127.0.0.1');
        }
        $this->core->db->query(
          'INSERT INTO visitors (user, sessid, ip, lastrequest)'
          .' VALUES ('.$vars->id.',"'.$vars->sessid.'",'.$vars->ip.', NOW())');

        return new RestVarsResponse(RestCodes::HTTP_200,
          array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt),
                'Set-Cookie' => 'sessid='.$vars->sessid.'; Path=/;'),
          $vars);
      } else {
        $vars=new stdClass();
        $vars->message='Bad credentials.';

        return new RestVarsResponse(RestCodes::HTTP_400,
          array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)),
          $vars);
      }
  }
  public function delete()
  {
    if(isset($this->request->content, $this->request->content->sessid)) {
      $sessid=xcUtilsInput::filterValue($this->request->content->sessid,
        'text','iparameter');
      $this->core->db->query(
        'DELETE FROM visitors'
        .' WHERE visitors.sessid="'.$this->queryParams->sessid.'"');
    }

    return new RestVarsResponse(RestCodes::HTTP_410,
      array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)),
      new stdClass());
  }
  public function patch()
  {
    // Updating the session validity
    $this->core->db->query(
      'UPDATE visitors SET lastrequest=DATE_ADD(NOW(), INTERVAL 1 DAY)'
      .' WHERE sessid="'.$this->queryParams->sessid.'"'
    );

    return new RestVarsResponse(RestCodes::HTTP_200,
      array('Content-Type' => 'text/plain'),
      'Session renewed.');
  }
  public static function createSessid()
  {
    $length = 40;
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    srand(time());
    $sessid='';
    for ($i=0; $i<$length; $i++) {
      $sessid.=substr($chars,(rand()%(strlen($chars))),1);
    }

    return $sessid;
  }
}
