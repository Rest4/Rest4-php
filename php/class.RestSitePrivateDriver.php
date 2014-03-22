<?php
class RestSitePrivateDriver extends RestSiteDriver
{
  public function prepare()
  {
    // Getting user informations
    if($this->request->uriNodes[3]&&!xcUtilsInput::isInt($this->request->uriNodes[3])
        &&$this->core->auth->source=='db'
        &&isset($this->core->database, $this->core->database->database)) {
      $this->user = $this->loadResource(
        '/db/'.$this->core->database->database.'/users/'
        .xcUtilsInput::filterValue($this->request->uriNodes[3], 'text', 'parameter')
        .'.dat?field=*&field=organizationLinkOrganizationsId.*','',true
      )->vars->entry;
    }
    // Preparing site structure
    parent::prepare();
  }
}

