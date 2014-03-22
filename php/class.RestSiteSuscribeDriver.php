<?php
class RestSiteSuscribeDriver extends RestSiteDriver
{
  static $drvInf;
  public static function getDrvInf($methods=0)
  {
    $drvInf=new stdClass();
    $drvInf->name='Suscribe: Subscription page Driver';
    $drvInf->description='Allow users to register to a website.';
    $drvInf->usage='/site/{user.i18n}/suscribe.{document.type}';
    $drvInf->methods=new stdClass();
    $drvInf->methods->options=new stdClass();
    $drvInf->methods->options->outputMimes='text/varstream';
    $drvInf->methods->head=$drvInf->methods->get=
      $drvInf->methods->post=new stdClass();
    $drvInf->methods->get->outputMimes='text/html';

    return $drvInf;
  }
  public function get()
  {
    $this->prepare();
    $this->_form();
    return $this->finish();
  }
  public function post()
  {
    $this->prepare();
    $this->loadSiteLocale($this->request->uriNodes[2],'index',
      $locale = new stdClass(), true, true);
    if((!isset($this->request->content->login))
      || !$this->request->content->login) {
      return $this->fail($locale->error_login);
    }
    $this->loadSiteDatas('/'.$this->request->uriNodes[2].'/data/form.dat',
      $form = new stdClass(), true, true);
    if($field = xcUtilsInput::checkRequiredContent($this->request->content, $form)) {
      return $this->fail(sprintf($this->core->i18n->form_required,
        $locale->{'field_'.$field->name}
      ));
    }
    if($field = xcUtilsInput::checkContent($this->request->content, $form)) {
      
      return $this->fail(sprintf($this->core->i18n->form_format,
        $locale->{'field_'.$field->name},
        $this->core->i18n->{'form_type_'.$field->type},
        isset($field->filter) && isset($this->core->i18n->{'form_filter_'.$field->filter})
          ? $this->core->i18n->{'form_filter_'.$field->filter} : ''
      ));
    }
    // Checking availability
    $res=new RestResource(new RestRequest(
      RestMethods::HEAD,
      '/users/'.$this->request->content->login.'.dat'
    ));
    $res=$res->getResponse();
    if($res->code==RestCodes::HTTP_200) {
      return $this->fail($locale->error_login);
    }
    // Checking phone number
    $this->core->db->query(
      'SELECT * FROM contacts'
      .' WHERE type=1 AND value="'.xcUtilsInput::filterValue($this->request->content->phone,'text','cdata').'"'
    );
    if($this->core->db->numRows()) {
      return $this->fail($locale->error_phone);
    }
    // Saving user
    $this->core->db->query(
      'INSERT INTO users (login, password, firstname, lastname, email, `group`, active)'
      .' VALUES ('
      .'  "'.xcUtilsInput::filterValue($this->request->content->login,'text','parameter').'",'
      .'  "'.md5(
          $this->request->content->login
          . ':' . $this->core->auth->realm
          . ':' . $this->request->content->password
        ).'",'
      .'  "'.xcUtilsInput::filterValue($this->request->content->firstname,'text','cdata').'",'
      .'  "'.xcUtilsInput::filterValue($this->request->content->lastname,'text','cdata').'",'
      .'  "'.xcUtilsInput::filterValue($this->request->content->email,'email','mail').'",'
      .'  1, "1"'
      .')'
    );
    $userId = $this->core->db->insertId();
    $this->core->db->query(
      'INSERT INTO places (address, address2, postalCode, city)'
      .' VALUES ('
      .'  "'.xcUtilsInput::filterValue($this->request->content->address,'text','cdata').'",'
      .'  "'.xcUtilsInput::filterValue($this->request->content->address2,'text','cdata').'",'
      .'  "'.xcUtilsInput::filterValue($this->request->content->postalCode,'text','cdata').'",'
      .'  "'.xcUtilsInput::filterValue($this->request->content->city,'text','cdata').'"'
      .')'
    );
    $placeId = $this->core->db->insertId();
    $this->core->db->query(
      'INSERT INTO places_users (users_id, places_id)'
      .' VALUES ('.$userId.','.$placeId.')'
    );
    $this->core->db->query(
      'INSERT INTO contacts (type, value)'
      .' VALUES ('
      .'  1,'
      .'  "'.xcUtilsInput::filterValue($this->request->content->phone,'tel','phone').'"'
      .')'
    );
    $contactId = $this->core->db->insertId();
    $this->core->db->query(
      'INSERT INTO contacts_users (users_id, contacts_id)'
      .' VALUES ('.$userId.','.$contactId.')'
    );
    // Then connecting
    $res=new RestResource(new RestRequest(
      RestMethods::POST,
      '/auth/session.dat?source=db',
      array('Content-Type' => 'application/x-www-form-urlencoded'),
      $this->request->content
    ));
    $res=$res->getResponse();
    // If connected, redirect to the wanted url
    if ($res->code==RestCodes::HTTP_200) {
      throw new RestException(RestCodes::HTTP_301,
        'Redirecting to your private page.','',
        array('Location' => '/'.$this->request->uriNodes[0].'/'
          .$this->request->uriNodes[1].'/private/'.$res->vars->login
          .'/board.'.$this->request->fileExt,
          'Set-Cookie' => $res->getHeader('Set-Cookie')));
    // else print the error
    } else {
      return $this->fail($locale->error_connect, $res->vars->message);
    }
  }
  protected function _form() {
    $mainModule=new stdClass();
    $mainModule->class='text';
    $this->core->mainModules->append($mainModule);
    // Main tpl
    $mainModule->template=$this->loadSiteTemplate(
      '/'.$this->request->uriNodes[2].'/'.$this->core->document->type.'/index.tpl',
      'mainModules.0',true);
    $this->loadSiteLocale($this->request->uriNodes[2],'','mainModules.0',
      false, true);
    $this->loadSiteLocale($this->request->uriNodes[2],'index','mainModules.0',
      true, true);
    // Form
    $mainModule->form=$this->loadSiteTemplate(
      '/system/'.$this->core->document->type.'/form.tpl',
      'mainModules.0',true);
    $this->loadSiteDatas('/'.$this->request->uriNodes[2].'/data/form.dat',
      $mainModule, true, true);
    if($this->request->content) {
      foreach($this->request->content as $property => $value) {
        foreach($mainModule->fieldsets as $fieldset) {
          foreach($fieldset->fields as $field) {
            if($field->name == $property) {
              $field->value = $value;
              break 2;
            }
          }
        }
      }
    }
  }
}

