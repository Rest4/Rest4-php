<?php
class RestSitePrivateProfileDriver extends RestSitePrivateDriver
{
  static $drvInf;
  public static function getDrvInf($methods=0)
  {
    $drvInf=new stdClass();
    $drvInf->name='Profile: Personnal datas page Driver';
    $drvInf->description='Allow users to modify their datas.';
    $drvInf->usage='/site/{user.i18n}/personnal.{document.type}';
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
    $this->loadSiteLocale($this->request->uriNodes[4],'index',
      $locale = new stdClass(), true, true);
    $this->loadSiteDatas('/'.$this->request->uriNodes[4].'/data/form.dat',
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
    // Saving changes
    $this->core->db->query(
      'UPDATE users SET '
      .'  firstname="'.xcUtilsInput::filterValue($this->request->content->firstname,'text','cdata').'",'
      .'  lastname="'.xcUtilsInput::filterValue($this->request->content->lastname,'text','cdata').'",'
      .'  email="'.xcUtilsInput::filterValue($this->request->content->email,'email','mail').'"'
      .'  WHERE users.login="'.xcUtilsInput::filterValue($this->request->uriNodes[3], 'text', 'parameter').'"'
    );
    $this->core->db->query(
      'UPDATE contacts SET '
      .'  value="'.xcUtilsInput::filterValue($this->request->content->phone,'tel','phone').'"'
      .'  WHERE id=('
      .'    SELECT contacts_id'
      .'    FROM contacts_users'
      .'      JOIN users ON users.id=contacts_users.users_id'
      .'    WHERE users.login="'.xcUtilsInput::filterValue($this->request->uriNodes[3], 'text', 'parameter').'"'
      .'      AND contacts.type=1'
      .'    ORDER BY contacts.id LIMIT 1'
      .'  )'
    );
    $this->core->db->query(
      'UPDATE places SET '
      .'  address="'.xcUtilsInput::filterValue($this->request->content->address,'text','cdata').'",'
      .'  address2="'.xcUtilsInput::filterValue($this->request->content->address2,'text','cdata').'",'
      .'  postalCode="'.xcUtilsInput::filterValue($this->request->content->postalCode,'text','cdata').'",'
      .'  city="'.xcUtilsInput::filterValue($this->request->content->city,'text','cdata').'"'
      .'  WHERE id=('
      .'    SELECT places_id'
      .'    FROM places_users'
      .'      JOIN users ON users.id=places_users.users_id'
      .'    WHERE users.login="'.xcUtilsInput::filterValue($this->request->uriNodes[3], 'text', 'parameter').'"'
      .'    ORDER BY places.id LIMIT 1'
      .'  )'
    );
    $this->notice($locale->notice_saved);
    $this->_form();
    return $this->finish();
  }
  protected function _form() {
    $mainModule=new stdClass();
    $mainModule->class='text';
    $this->core->mainModules->append($mainModule);
    // Main tpl
    $mainModule->template=$this->loadSiteTemplate(
      '/'.$this->request->uriNodes[4].'/'.$this->core->document->type.'/index.tpl',
      'mainModules.0',true);
    $this->loadSiteLocale($this->request->uriNodes[4],'','mainModules.0',
      false, true);
    $this->loadSiteLocale($this->request->uriNodes[4],'index','mainModules.0',
      true, true);
    // Form
    $this->core->db->query(
       'SELECT users.*, places.*, contacts.value as phone'
      .'  FROM users'
      .'    JOIN places_users ON places_users.users_id = users.id'
      .'    JOIN places ON places.id = places_users.places_id'
      .'    JOIN contacts_users ON contacts_users.users_id = users.id'
      .'    JOIN contacts ON contacts.id = contacts_users.contacts_id'
      .'      AND contacts.type=1'
      .'  WHERE users.login="'.xcUtilsInput::filterValue($this->request->uriNodes[3], 'text', 'parameter').'"'
      .'  ORDER BY places.id ASC, contacts.id ASC'
      .'  LIMIT 1'
    );
    if($this->core->db->numRows()!=1) {
      $this->error($locale->error_norow,
        'The user seems to have no place/phone number associated');
      return;
    }
    $row = $this->core->db->fetchArray();
    $mainModule->form=$this->loadSiteTemplate(
      '/system/'.$this->core->document->type.'/form.tpl',
      'mainModules.0',true);
    $this->loadSiteDatas('/'.$this->request->uriNodes[4].'/data/form.dat',
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
    if($row) {
      foreach($row as $property => $value) {
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

