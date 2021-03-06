<?php
class RestAuthSessionDriver extends RestVarsDriver
{
  static $drvInf;
  public static function getDrvInf($methods=0)
  {
    $drvInf=parent::getDrvInf(RestMethods::GET|RestMethods::POST
      |RestMethods::DELETE);
    $drvInf->name='Auth: Session Auth Driver';
    $drvInf->description='Authentifies visitors/users sessions and show their'
      .' rights.';
    $drvInf->usage='/auth/session'.$drvInf->usage
                   .'?method=(request_method)&cookie=(cookies)';
    $drvInf->methods->get->queryParams=new MergeArrayObject();
    $drvInf->methods->get->queryParams[0]=new stdClass();
    $drvInf->methods->get->queryParams[0]->name='method';
    $drvInf->methods->get->queryParams[0]->filter='iparameter';
    $drvInf->methods->get->queryParams[0]->required=true;
    $drvInf->methods->get->queryParams[1]=new stdClass();
    $drvInf->methods->get->queryParams[1]->name='source';
    $drvInf->methods->get->queryParams[1]->filter='iparameter';
    $drvInf->methods->get->queryParams[1]->required=true;
    $drvInf->methods->get->queryParams[2]=new stdClass();
    $drvInf->methods->get->queryParams[2]->name='cookie';
    $drvInf->methods->get->queryParams[2]->filter='cdata';
    $drvInf->methods->get->queryParams[2]->value='';
    $drvInf->methods->post->queryParams=new MergeArrayObject();
    $drvInf->methods->post->queryParams[0]=new stdClass();
    $drvInf->methods->post->queryParams[0]->name='source';
    $drvInf->methods->post->queryParams[0]->filter='iparameter';
    $drvInf->methods->post->queryParams[0]->required=true;

    return $drvInf;
  }
  public function get()
  {
    if('db'!==$this->queryParams->source) {
      throw new RestException(RestCodes::HTTP_400,
        'Unsupported auth source "'.$this->queryParams->source.'".');
    }
    $this->core->db->selectDb($this->core->database->database);
    // Setting defaults
    $vars=new stdClass();
    $vars->id=0;
    $vars->group=0;
    $vars->organization=0;
    $vars->rights=new MergeArrayObject();
    $vars->login='';
    $vars->sessid='';
    // Getting session id
    if($this->queryParams->cookie
      && strpos($this->queryParams->cookie,'sessid=')!==false) {
      $vars->sessid=substr($this->queryParams->cookie,
        strpos($this->queryParams->cookie,'sessid=')+7);
      if(strpos($vars->sessid,';')!==false) {
        $vars->sessid=substr($vars->sessid,0,strpos($vars->sessid,';'));
      }
      $vars->sessid=xcUtilsInput::filterValue($vars->sessid,'text','iparameter');
    }
    if($vars->sessid) {
      // Checking validity
      $this->core->db->query(
        'SELECT users.id AS userId, users.login AS userLogin,'
        .'  users.group AS userGroup, users.organization AS userOrganization,'
        .'  visitors.id AS visitorId'
        .' FROM visitors'
        .' LEFT JOIN users ON users.id=visitors.user'
        .' WHERE visitors.sessid="'.$vars->sessid.'"'
        .' AND lastrequest>DATE_SUB(NOW(), INTERVAL 1 DAY)');
      if($this->core->db->numRows()) {
        if($this->core->db->result('userId')) {
          $vars->id=$this->core->db->result('userId');
          $vars->login=$this->core->db->result('userLogin');
          $vars->group=$this->core->db->result('userGroup');
          $vars->organization=$this->core->db->result('userOrganization');
        }
        $vars->visitor=$this->core->db->result('visitorId');
        // Updating the session validity
        $this->core->db->query('UPDATE visitors SET lastrequest=NOW()'
          .' WHERE sessid="'.$vars->sessid.'"');
      } else {
        $vars->sessid = '';
      }
    }
    // Creating a new session id
    if('' === $vars->sessid) {
      $vars->sessid = $this->createSessid();
      $this->core->db->query('INSERT INTO visitors (lastrequest, sessid, ip)'
        .'VALUES (NOW(),"'.$vars->sessid.'",'.$this->getIp().')');
      $vars->visitor=$this->core->db->insertId();
    }
    // Getting default anonymous and connected user rights
    $this->core->db->query('SELECT DISTINCT rights.path'
      .($this->queryParams->method?'':', rights.enablings')
      .' FROM rights'
      .' LEFT JOIN groups_rights ON groups_rights.rights_id=rights.id'
      .' LEFT JOIN groups ON groups.id=groups_rights.groups_id'
      .' WHERE (groups.id=0'.($vars->id?' OR groups.id=1':'').')'
      .($this->queryParams->method?' AND rights.enablings&'
        .RestMethods::getMethodFromString($this->queryParams->method):''));
    if($this->core->db->numRows()) {
      while ($row = $this->core->db->fetchArray()) {
        $right=new stdClass();
        $right->path=str_replace('{user.login}',$vars->login,
          str_replace('{user.group}',$vars->group,
            str_replace('{user.organization}',
              $vars->organization,$row['path'])));
        if(!$this->queryParams->method) {
          $right->methods=$row['enablings'];
        }
        $vars->rights->append($right);
      }
    }
    $this->core->db->query('SELECT DISTINCT rights.path'
      .($this->queryParams->method?'':', rights.enablings')
      .' FROM rights'
      .' LEFT JOIN groups_rights ON groups_rights.rights_id=rights.id'
      .' LEFT JOIN groups ON groups.id=groups_rights.groups_id'
      .' LEFT JOIN groups_users ON groups_users.groups_id=groups.id'
      .' LEFT JOIN rights_users ON rights_users.rights_id=rights.id'
      .' LEFT JOIN users ON (users.id=groups_users.users_id'
      .' OR users.id=rights_users.users_id OR users.group=groups.id)'
      .' WHERE users.id='.$vars->id.($this->queryParams->method?
        ' AND rights.enablings&'
        .RestMethods::getMethodFromString($this->queryParams->method):''));
    if($this->core->db->numRows()) {
      while ($row = $this->core->db->fetchArray()) {
        $right=new stdClass();
        $right->path=str_replace('{user.login}',$vars->login,
          str_replace('{user.group}',$vars->group,
            str_replace('{user.organization}',
              $vars->organization,$row['path'])));
        if(!$this->queryParams->method) {
          $right->methods=$row['enablings'];
        }
        $vars->rights->append($right);
      }
    }

    return new RestVarsResponse(RestCodes::HTTP_200,
      array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt),
            'X-Rest-Uncacheback' =>'/users'),
      $vars);
  }
  public function post()
  {
    if('db'!==$this->queryParams->source) {
      throw new RestException(RestCodes::HTTP_400,'Unsupported auth source "'
        .$this->queryParams->source.'".');
    }
    if(isset($this->request->content->login,
             $this->request->content->password)) {
      // Checking credentials
      $this->core->db->selectDb($this->core->database->database);
      $this->core->db->query(
        'SELECT id, login FROM users WHERE active="1" AND login="'
        .xcUtilsInput::filterValue($this->request->content->login)
        .'" AND (password="'.sha1($this->request->content->password)
        .'" OR password="'.md5($this->request->content->login.':'
          . $this->core->auth->realm . ':' . $this->request->content->password)
        .'")');
      if($this->core->db->numRows()) {
        $vars=new stdClass();
        $vars->message='Successfully logged in.';
        $vars->sessid=$this->core->user->sessid
          ? $this->core->user->sessid
          : self::createSessid();
        $vars->id=$this->core->db->result('users.id');
        $vars->login=$this->core->db->result('users.login');
        $vars->ip=$this->getIp();
        $this->core->db->query('SELECT id FROM visitors'
          .' WHERE sessid="'.$vars->sessid.'"');
        if($this->core->db->numRows()) {
          $vars->visitor=$this->core->db->result('id');
          $this->core->db->query('UPDATE visitors SET user='.$vars->id.','
            .' lastrequest=NOW() WHERE sessid="'.$vars->sessid.'"');
        } else {
          $this->core->db->query(
            'INSERT INTO visitors (user, sessid, ip, lastrequest)'
            .' VALUES ('.$vars->id.',"'.$vars->sessid.'",'.$vars->ip.', NOW())');
          $vars->visitor=$this->core->db->insertId();
        }

        return new RestVarsResponse(RestCodes::HTTP_200,
          array(
            'Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt),
            'Set-Cookie' => 'sessid='.$vars->sessid.'; Path=/;'
          ),
          $vars);
      } else {
        $vars=new stdClass();
        $vars->message='Bad credentials.';

        return new RestVarsResponse(RestCodes::HTTP_400,
          array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)),
          $vars);
      }
    } else {
      $vars=new stdClass();
      $vars->message='Must authenticate to access this ressource.';

      return new RestVarsResponse(RestCodes::HTTP_401,
        array('WWW-Authenticate'=>'Cookie realm="'.$this->core->auth->realm.'"',
          'Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)),
        $vars);
    }
  }
  public function delete()
  {
    if(isset($this->request->content, $this->request->content->sessid)) {
      $sessid=xcUtilsInput::filterValue($this->request->content->sessid,
        'text','iparameter');
      if($sessid) {
        $this->core->db->query(
          'DELETE FROM visitors WHERE visitors.sessid="'.$sessid.'"');
      }
    }

    return new RestVarsResponse(RestCodes::HTTP_410,
      array('Content-Type' => xcUtils::getMimeFromExt($this->request->fileExt)),
      new stdClass());
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
  public static function getIp()
  {
    return isset($_SERVER['REMOTE_ADDR'])&&ip2long($_SERVER['REMOTE_ADDR'])
      ? ip2long($_SERVER['REMOTE_ADDR'])
      : ip2long('127.0.0.1');
  }
}

