<?php
class RestCompositeDriver extends RestDriver
{
  public function __construct(RestRequest $request)
  {
    parent::__construct($request);
    $this->core->datasLoaded=new ArrayObject();
  }
  public function prepare()
  {
    // Getting the document language and locale
    if (!isset($this->core->document)) {
      $this->core->document=new stdClass();
    }
    $this->core->document->lang=$this->request->lang;
    $this->core->document->locale=$this->request->locale;
    $this->core->document->i18n=$this->request->i18n;
    $this->core->document->i18nFallback=($this->request->locale
       &&$this->request->i18n!=$this->core->server->defaultLang
        .'-'.$this->core->server->defaultLocale?
       $this->request->i18n.',':'')
      .($this->request->lang!=$this->core->server->defaultLang?$this->request->lang.',':'')
      .($this->core->server->defaultLocale?$this->core->server->defaultLang
        .'-'.$this->core->server->defaultLocale.',':'')
      .$this->core->server->defaultLang;
    if (!isset($this->core->i18n)) {
      $this->core->i18n=new stdClass();
    }
    // Creating reference to uriNodes :
    $this->core->uriNodes=$this->request->uriNodes;
    // Creating reference to queryParams :
    $this->core->queryParams=$this->queryParams;
    // Getting the document type
    if (!$this->request->fileExt) {
      throw new RestException(RestCodes::HTTP_301,
        'No file type given, redirecting to default file type.', '',
        array('Location'=>$this->core->server->location.$this->request->controller
          .$this->request->filePath.$this->request->fileName.'.'
          .$this->core->site->defaultType
          .($this->request->queryString?'?'.$this->request->queryString:'')));
    }
    $this->core->document->type=$this->request->fileExt;
    if(!Varstream::get($this->core,'types.'.$this->core->document->type)) {
      throw new RestException(RestCodes::HTTP_400,
        'Can\'t play with the given type yet: '.$this->core->document->type.'.');
    }
  }
  // Resources load
  public function loadLocale($path,$context='',$required=false,$fallbackPatch='',$merge=false)
  // Add a way to not search in the default locale.
  {
    $fallback=$this->core->document->i18nFallback;
    if($fallbackPatch===true||$fallbackPatch===false) {
      throw new RestException(RestCodes::HTTP_500,
        'Multiple argument is deprecated ('.$path.').');
    } else {
      if ($fallbackPatch) {
        $fallbacks=explode(',',$this->core->document->i18nFallback);
        for ($i=sizeof($fallbacks)-1; $i>=0; $i--) {
          $fallbacks[$i].=$fallbackPatch;
        }
        $fallback=implode(',',$fallbacks);
      }
    }

    if (!$context) {
      $context=$this->core->i18n;
    } else if(!($context instanceof stdClass)) {
      if (!Varstream::get($this->core,'i18n.'.$context)) {
        $context=Varstream::set($this->core,'i18n.'.$context, new stdClass());
      } else {
        $context=Varstream::get($this->core,'i18n.'.$context);
      }
    }
    $path='/mpfs'.$path.($merge?'?mode=merge':'');
    if((!$found=$this->loadDatas(
      str_replace('$',$fallback,$path), $context, false)) && $required) {
      throw new RestException(RestCodes::HTTP_500,
        'No language file available ('.$path.').');
    }

    return $found;
  }
  public function loadDatas($uri,$context=null,$required=false)
  {
    if ($res=$this->loadResource($uri,$required)) {
      if (!$context) { // dangerous ?
        $context=$this->core;
      }
      if(!$context instanceof stdClass) {
        throw new RestException(RestCodes::HTTP_500,
          'Context object is not an instance of stdClass.');
      }
      // Try to access to internal vars
      if($res instanceof RestVarsResponse
          &&($res->vars instanceof ArrayObject||$res->vars instanceof stdClass)) {
        Varstream::loadObject($context,$res->vars);
      // Load content from text content
      } else {
        if($res->getHeader('Content-Type')=='text/varstream'
            ||$res->getHeader('Content-Type')=='text/lang') {
          Varstream::import($context,$res->getContents());
        } else {
          if ($res->getHeader('Content-Type')=='text/json') {
            $context=Json::decode($res->getContents());
          } else {
            throw new RestException(RestCodes::HTTP_500,
              'Cannot load unsupported datas.');
          }
        }
      }

      return true;
    }

    return false;
  }
  public function loadTemplate($uri,$context='',$required=false)
  {
    if ($res=$this->loadResource('/mpfs'.$uri,$required)) {
      return str_replace('§',$context,$res->getContents());
    }

    return false;
  }
  public function loadResource($uri,$required=false)
  {
    $res=new RestResource(new RestRequest(RestMethods::GET,$uri));
    $res=$res->getResponse();
    if ($res->code==RestCodes::HTTP_200) {
      $this->core->datasLoaded->append($uri);

      return $res;
    } elseif ($required) {
      throw new RestException(RestCodes::HTTP_500,'Can\'t read ressource content.',$uri);
    } else {
      return false;
    }

    return true;
  }
}

