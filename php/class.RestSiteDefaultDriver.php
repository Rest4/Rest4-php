<?php
class RestSiteDefaultDriver extends RestSiteDriver
{
  static $drvInf;
  public static function getDrvInf($method=0)
  {
    $drvInf=new stdClass();
    $drvInf->name='Site: Default Driver';
    $drvInf->description='Try to run a default code if the specific driver doesn\'t exist.';
    $drvInf->usage='/home/{user.i18n}/index(/page).{document.type}';
    $drvInf->methods=new stdClass();
    $drvInf->methods->options=new stdClass();
    $drvInf->methods->options->outputMimes='text/varstream';
    $drvInf->methods->head=$drvInf->methods->get=new stdClass();
    $drvInf->methods->get->outputMimes='text/html';

    return $drvInf;
  }
  public function get()
  {
    $this->prepare();
    $mainModule=new stdClass();
    $mainModule->class='text';
    $node=strtolower($this->request->uriNodes[2]);
    // Redirect if the fourth uri node equals to index
    if (isset($this->request->uriNodes[3])&&'index'==$this->request->uriNodes[3]) {
      throw new RestException(RestCodes::HTTP_301,
        'Redirecting to index for this ressource.', '',
        array('Location'=>'/'.$this->request->uriNodes[0].'/'
          .$this->request->uriNodes[1].'/'.$this->request->uriNodes[2]
          .'.'.$request->fileExt));
    }
    $mainModule->template=$this->loadSiteTemplate(
      '/'.$node.'/'.$this->core->document->type.'/'
      .(isset($this->request->uriNodes[3])?$this->request->uriNodes[3]:'index')
      .'.tpl','mainModules.0');
    if (!$mainModule->template) {
      $mainModule->template=$this->loadSiteTemplate(
        '/system/'.$this->core->document->type.'/404.tpl','mainModules.0');
      $this->loadSiteLocale('system','404','mainModules.0',true);
      $this->core->mainModules->append($mainModule);

      return $this->finish(RestCodes::HTTP_404);
    } else {
      $this->loadSiteLocale($node,'','mainModules.0');
      $this->loadSiteLocale($node,
        (isset($this->request->uriNodes[3])?$this->request->uriNodes[3]:'index'),
        'mainModules.0',true);
      $this->core->mainModules->append($mainModule);

      return $this->finish();
    }
  }
}

