<?php
class RestSiteAuthDriver extends RestSiteDriver
{
  static $drvInf;
  public static function getDrvInf($methods=0)
  {
    $drvInf=new stdClass();
    $drvInf->name='Auth: Authentication page Driver';
    $drvInf->description='Allow users to connect to a website using sessions.';
    $drvInf->usage='/site/{user.i18n}/auth.{document.type}';
    $drvInf->methods=new stdClass();
    $drvInf->methods->options=new stdClass();
    $drvInf->methods->options->outputMimes='text/varstream';
    $drvInf->methods->head=$drvInf->methods->get=$drvInf->methods->post=new stdClass();
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
      $this->prepare();
      $this->loadSiteLocale($this->request->uriNodes[2],'index',
        $locale = new stdClass(), true, true);
      return $this->fail($locale->error_bad_credentials);
    }
  }
  protected function _form()
  {
    $mainModule=new stdClass();
    $mainModule->class='text';
    $this->core->mainModules->append($mainModule);
    // Main tpl
    $mainModule->template=$this->loadSiteTemplate(
      '/'.$this->request->uriNodes[2].'/'.$this->core->document->type.'/index.tpl',
      'mainModules.0',true);
    $this->loadSiteLocale($this->request->uriNodes[2],'','mainModules.0');
    $this->loadSiteLocale($this->request->uriNodes[2],'index','mainModules.0',true);
    // Form
    $mainModule->form=$this->loadSiteTemplate(
      '/system/'.$this->core->document->type.'/form.tpl',
      'mainModules.0',true);
    $this->loadSiteDatas('/'.$this->request->uriNodes[2].'/data/form.dat',
      $mainModule, true, true);
  }
}

