<?php
class RestSitePrivateDriver extends RestSiteDriver
{
  public function prepare()
  {
    // Getting user informations (should be based on the login in the URI node)
    if($this->core->user->id&&$this->core->auth->source=='db'
        &&isset($this->core->database,$this->core->database->database)) {
      Varstream::loadObject($this->core->user,$this->loadResource(
        '/db/'.$this->core->database->database.'/users/'.$this->core->user->id
        .'.dat?field=*&field=organizationLinkOrganizationsId.*','',true)->vars->entry);
    }
    // Preparing site structure
    parent::prepare();
  }
}

