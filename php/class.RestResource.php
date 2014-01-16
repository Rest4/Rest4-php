<?php
class RestResource
{
  private static $_loadedResources=array();
  private $request;
  private $response;
  public function __construct(RestRequest $request)
  {
    $this->request=$request;
    $this->core=RestServer::Instance();
  }
  public function getResponse()
  {
    try {
      if (!$this->request) {
        throw new RestException(RestCodes::HTTP_500,'No Request object given');
      }
      $this->request->parseUri();
      if (!$this->request->controller) {
        throw new RestException(RestCodes::HTTP_301,
                                'Redirecting to the home ressource ('.$this->core->server->home.')',
                                '',
                                array('Location'=>$this->core->server->location
                                      .$this->core->server->home));
      }
      /* Reset local resource cache if needed */
      if ($this->request->getHeader('X-Rest-Local-Cache')=='disabled') {
        self::$_loadedResources=array();
      }
      /* Try to find the resource in the previously loaded resources */
      if($this->request->method==RestMethods::GET&&isset(
            self::$_loadedResources[$this->request->controller
                                    .($this->request->filePath?$this->request->filePath:'')
                                    .$this->request->fileName.($this->request->queryString?
                                        '-'.md5($this->request->queryString):'')
                                    .($this->request->fileExt?'.'.$this->request->fileExt:'')])) {
        $this->response=self::$_loadedResources[$this->request->controller
                                                .($this->request->filePath?$this->request->filePath:'')
                                                .$this->request->fileName.($this->request->queryString?
                                                    '-'.md5($this->request->queryString):'')
                                                .($this->request->fileExt?'.'.$this->request->fileExt:'')];
      }
      /* Try to find resource in the cache */
      if($this->core->cache->type&&'none'!=$this->core->cache->type
          &&$this->request->getHeader('X-Rest-Local-Cache')!='disabled'
          &&$this->request->method==RestMethods::GET
          &&$this->request->controller!='cache'
          &&$this->request->controller!='fs') {
        $res=new RestResource(new RestRequest(RestMethods::GET,'/cache/'
                                              .$this->core->cache->type.'/'.$this->request->controller
                                              .($this->request->filePath?$this->request->filePath:'')
                                              .$this->request->fileName.($this->request->queryString?
                                                  '-'.md5($this->request->queryString):'')
                                              .($this->request->fileExt?'.'.$this->request->fileExt:'')));
        $res=$res->getResponse();
        if ($res->code==RestCodes::HTTP_200) {
          $iMS=$this->request->getHeader('If-Modified-Since','text','cdata');
          $resTime=strtotime(substr(
                               $res->getHeader('Last-Modified','text','cdata'),0,29));
          if ($iMS&&strtotime(substr($iMS,0,29))>=$resTime) {
            $this->response=new RestResponse();
            $this->response->code=RestCodes::HTTP_304;
          } else
            if((!$this->core->http->maxage)
                ||time()-$this->core->http->maxage<$resTime) {
              if ($this->core->server->debug) {
                $res->setHeader('X-Rest-Cache','Cache');
              }
              $this->response=$res;
            }
        }
      }
      /* Run controller if cache is empty */
      if (!$this->response) {
        // Defaults to the original request object
        $request=$this->request;
        // Trying to find a route
        if($this->request->controller!='http'&&isset($this->core->routes)
            &&$this->core->routes->count()) {
          foreach ($this->core->routes as $route) {
            if(isset($route->paths)&&$route->paths->count())
              foreach ($route->paths as $path) {
              if(strpos($this->request->controller
                        .($this->request->filePath?$this->request->filePath:'')
                        .$this->request->fileName
                        .($this->request->fileExt?'.'.$this->request->fileExt:''),
                        $path->path)===0) {
                // Routing to a distant resource
                if(isset($route->domain)
                    &&$route->domain!=$this->core->server->domain) {
                  $request=new RestRequest($this->request->method,
                                           '/http?uri='.urlencode((isset($route->protocol)?
                                               $route->protocol:'http')
                                               .'://'.$route->domain.$this->request->uri)
                                           .(isset($route->auth,$route->user,$route->password)?
                                             '&auth='.$route->auth.'&user='.$route->user
                                             .'&password='.$route->password:''),
                                           $this->request->headers,$this->request->content);
                  $request->parseUri(); // ParseUri should be called automatically when uri is changed !
                  break;
                }
                // Mapping to another local resource
                else
                  if (isset($path->replace)) {
                    $request->uri=str_replace($path->path,
                                              $path->replace,$this->request->uri);
                    $request->parseUri(); // ParseUri should be called automatically when uri is changed !
                  }
              }
            }
          }
        }
        // Running the controller and than the driver
        $controllerClass='Rest'.ucfirst($request->controller).'Controller';
        if (!xcUtils::classExists($controllerClass)) {
          throw new RestException(RestCodes::HTTP_400,
                                  'The given controller is not present here'
                                  .' ('.$request->controller.')');
        }
        $controller=new $controllerClass($request);
        $this->response=$controller->getResponse();
        /* Adding GET requests results to the cache */
        if($this->core->cache->type&&'none'!=$this->core->cache->type
            &&$this->request->method==RestMethods::GET
            &&$this->request->controller!='cache'
            &&$this->request->controller!='fs'
            &&$this->response->code==RestCodes::HTTP_200
            &&$this->response->getHeader('X-Rest-Cache')!='None'
            &&$content=$this->response->getContents()) {
          self::$_loadedResources[$this->request->controller
                                  .($this->request->filePath?$this->request->filePath:'')
                                  .$this->request->fileName.($this->request->queryString?
                                      '-'.md5($this->request->queryString):'')
                                  .($this->request->fileExt?
                                    '.'.$this->request->fileExt:'')]=$this->response;
          $res=new RestResource(new RestRequest(RestMethods::PUT,
                                                '/cache/'.$this->core->cache->type.'/'.$this->request->controller
                                                .($this->request->filePath?$this->request->filePath:'')
                                                .$this->request->fileName
                                                .($this->request->queryString?
                                                  '-'.md5($this->request->queryString):'')
                                                .($this->request->fileExt?'.'.$this->request->fileExt:'')
                                                ,array('Content-Type' => 'text/plain'),$content));
          $res=$res->getResponse();
          if ($res->code!=RestCodes::HTTP_201) {
            trigger_error('Cannot write response to the cache (code: '
                          .$res->code.', uri: '.$this->core->server->location.'cache/'
                          .$this->core->cache->type.'/'.$this->request->controller
                          .($this->request->filePath?$this->request->filePath:'')
                          .$this->request->fileName
                          .($this->request->queryString?
                            '-'.md5($this->request->queryString):'')
                          .($this->request->fileExt?'.'.$this->request->fileExt:'').')');
          }
          if($this->response->getHeader('X-Rest-Uncacheback')&&$uncache=explode(
                '|',$this->response->getHeader('X-Rest-Uncacheback'))) {
            foreach ($uncache as $unc) {
              if ($unc) {
                $res=new RestResource(new RestRequest(RestMethods::POST,
                                                      '/cache/'.$this->core->cache->type
                                                      .$unc.'callback.txt',array(),'/'.$this->request->controller
                                                      .($this->request->filePath?$this->request->filePath:'')
                                                      .$this->request->fileName.($this->request->queryString?
                                                          '-'.md5($this->request->queryString):'')
                                                      .($this->request->fileExt?'.'.$this->request->fileExt:'')
                                                      ."\n"));
                $res->getResponse();
              }
            }
          }
        }
        // Removing cache contents when modifying ressources :
        // It could be annoying when modifying a lot of resources in a single server hit
        // Maybe do it after sending the response (into the RestServer)
        // Should create a rest driver to post and repeats uncaches on each servers of a rest grappe
        if($this->core->cache->type&&'none'!=$this->core->cache->type
            &&($this->request->method==RestMethods::PUT
               ||$this->request->method==RestMethods::POST
               ||$this->request->method==RestMethods::PATCH
               ||$this->request->method==RestMethods::DELETE)
            &&$this->request->controller!='cache') {
          if ($this->response->getHeader('X-Rest-Uncache')) {
            $uncache=explode('|',$this->response->getHeader('X-Rest-Uncache'));
          } else {
            $uncache=array();
          }
          array_push($uncache,'/'.$this->request->controller
                     .($this->request->filePath?
                       substr($this->request->filePath,0,strlen($this->request->filePath)-1):''));
          foreach ($uncache as $unc) {
            $res=new RestResource(new RestRequest(RestMethods::DELETE,'/cache/'
              .$this->core->cache->type.$unc.'.dat?mode=multiple',array()));
            $res->getResponse();
          }
        }
        if (isset($this->core->server->debug)&&$this->core->server->debug) {
          $this->response->setHeader('X-Rest-Cache','Live');
        } else {
          $this->response->unsetHeader('X-Rest-Local-Cache');
          $this->response->unsetHeader('X-Rest-Cache');
          $this->response->unsetHeader('X-Rest-Uncacheback');
          $this->response->unsetHeader('X-Rest-Uncache');
        }
      }
    } catch (RestException $e) {
      $this->response=new RestResponse();
      $this->response->code=$e->getCode();
      $this->response->setHeader('Content-Type','text/plain');
      $this->response->content=$e->getMessage();
      // Building debug string
      $debug="\n".$e->getDebug()."\n\n# Stack:";
      foreach ($e->getTrace() as $key=>$level) {
        $debug.="\n".xcUtilsInput::filterAsCData($key.' - File: '
                .$level['file'].' Line: '.$level['line']
                .' Function: '.$level['function']);
      }
      // printing debug informations
      if (defined('DEBUG_PRINT')&&DEBUG_PRINT) {
        $this->response->content.=$debug;
      }
      $this->response->setHeader('Content-Length',
                                 strlen($this->response->content));
      foreach ($e->headers as $name => $value) {
        $this->response->setHeader($name,$value);
      }
      // Mailing debug informations
      if($this->response->code==RestCodes::HTTP_500
          &&defined('DEBUG_RESOURCE')&&DEBUG_RESOURCE) {
        $debug.="\n\n# Core vars:\n".Varstream::export($this->core);
        if (defined('DEBUG_MAIL')&&DEBUG_MAIL) {
          mail(DEBUG_MAIL, 'Debug: '. $_SERVER['REQUEST_METHOD']
               .'-'.$_SERVER['SERVER_NAME']. $_SERVER['REQUEST_URI'],
               $this->response->content.$debug);
        }
      }
    }

    return $this->response;
  }
}
