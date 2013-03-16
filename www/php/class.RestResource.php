<?php
class RestResource
	{
	private static $_loadedResources=array();
	private $request;
	private $response;
	function __construct(RestRequest $request)
		{
		$this->request=$request;
		$this->core=RestServer::Instance();
		}
	function getResponse()
		{
		try
			{
			if(!$this->request)
				throw new RestException(RestCodes::HTTP_400,'No Request object given');
			$this->request->parseUri();
			if(!$this->request->controller)
				{
				throw new RestException(RestCodes::HTTP_301,'Redirecting to the home ressource ('.$this->core->server->home.')', '', array('Location'=>$this->core->server->location.$this->core->server->home));
				}
			/* Reset local resource cache if needed */
			if($this->request->getHeader('X-Rest-Local-Cache')=='disabled')
				{ self::$_loadedResources=array(); }
			/* Try to find resource in the previously loaded resources */
			if($this->request->method==RestMethods::GET&&isset(self::$_loadedResources[$this->request->controller.($this->request->filePath?$this->request->filePath:'').$this->request->fileName.($this->request->queryString?'-'.md5($this->request->queryString):'').($this->request->fileExt?'.'.$this->request->fileExt:'')]))
				{
				$this->response=self::$_loadedResources[$this->request->controller.($this->request->filePath?$this->request->filePath:'').$this->request->fileName.($this->request->queryString?'-'.md5($this->request->queryString):'').($this->request->fileExt?'.'.$this->request->fileExt:'')];
				}
			/* Try to find resource in the cache */
			if($this->core->server->cache&&$this->request->getHeader('X-Rest-Local-Cache')!='disabled'&&$this->request->method==RestMethods::GET&&$this->request->controller!='cache'&&$this->request->controller!='fs')
				{
				$res=new RestResource(new RestRequest(RestMethods::GET,'/cache/'.$this->core->server->cache.'/'.$this->request->controller.($this->request->filePath?$this->request->filePath:'').$this->request->fileName.($this->request->queryString?'-'.md5($this->request->queryString):'').($this->request->fileExt?'.'.$this->request->fileExt:'')));
				$res=$res->getResponse();
				if($res->code==RestCodes::HTTP_200)
					{
					$iMS=$this->request->getHeader('If-Modified-Since','text','cdata');
					$resTime=strtotime(substr($res->getHeader('Last-Modified','text','cdata'),0,29));
					if($iMS&&strtotime(substr($iMS,0,29))>=$resTime)
						{
						$this->response=new RestResponse();
						$this->response->code=RestCodes::HTTP_304;
						}
					else if((!$this->core->http->maxage)||time()-$this->core->http->maxage<$resTime)
						{
						$res->setHeader('X-Rest-Cache','Cache');
						$this->response=$res;
						}
					}
				}
			/* Run controller if cache is empty */
			if(!$this->response)
				{
				// Finding the route
				$resRoute=null;
				if($this->request->controller!='http'&&isset($this->core->routes)&&$this->core->routes->count())
					{
					foreach($this->core->routes as $route)
						{
						if(isset($route->paths,$route->domain)&&$route->paths->count()&&$route->domain!=$this->core->server->domain)
						foreach($route->paths as $path)
							{
							if(strpos($this->request->controller.($this->request->filePath?$this->request->filePath:'').$this->request->fileName,$path)===0)
								{
								$resRoute=$route; break;
								}
							}
						}
					}
				$request=$this->request;
				if($resRoute) // distant resource
					{
					$request=new RestRequest($this->request->method,
						'/http?uri='.urlencode((isset($resRoute->protocol)?$resRoute->protocol:'http').'://'
							.$resRoute->domain.$this->request->uri)
							.(isset($resRoute->auth,$resRoute->user,$resRoute->password)?
								'&auth='.$resRoute->auth.'&user='.$resRoute->user.'&password='.$resRoute->password:''),
						$this->request->headers,$this->request->content);
					$request->parseUri(); // ParseUri should be called automatically when uri is changed !
					}
				$controllerClass='Rest'.ucfirst($request->controller).'Controller';
				if(!xcUtils::classExists($controllerClass))
					throw new RestException(RestCodes::HTTP_400,'The given controller is not present here ('.$request->controller.')');
				$controller=new $controllerClass($request);
				$this->response=$controller->getResponse();
				/* Adding GET requests results to the cache */
				if($this->core->server->cache&&$this->response->content&&$this->request->method==RestMethods::GET&&$this->request->controller!='cache'&&$this->request->controller!='fs'&&$this->response->code==RestCodes::HTTP_200&&$this->response->getHeader('X-Rest-Cache')!='None')
					{
					self::$_loadedResources[$this->request->controller.($this->request->filePath?$this->request->filePath:'').$this->request->fileName.($this->request->queryString?'-'.md5($this->request->queryString):'').($this->request->fileExt?'.'.$this->request->fileExt:'')]=$this->response;
					$res=new RestResource(new RestRequest(RestMethods::PUT,'/cache/'.$this->core->server->cache.'/'.$this->request->controller.($this->request->filePath?$this->request->filePath:'').$this->request->fileName.($this->request->queryString?'-'.md5($this->request->queryString):'').($this->request->fileExt?'.'.$this->request->fileExt:''),array(),$this->response->content));
					$res=$res->getResponse();
					if($res->code!=RestCodes::HTTP_201)
						{
						trigger_error('Cannot write response to the cache (code: '.$res->code.', uri: '.$this->core->server->location.'cache/'.$this->core->server->cache.'/'.$this->request->controller.($this->request->filePath?$this->request->filePath:'').$this->request->fileName.($this->request->queryString?'-'.md5($this->request->queryString):'').($this->request->fileExt?'.'.$this->request->fileExt:'').')');
						}
					if($this->response->getHeader('X-Rest-Uncacheback')&&$uncache=explode('|',$this->response->getHeader('X-Rest-Uncacheback')))
						{
						foreach($uncache as $unc)
							{
							if($unc)
								{
								$res=new RestResource(new RestRequest(RestMethods::POST,'/cache/'.$this->core->server->cache.$unc.'callback.txt',array(),'/'.$this->request->controller.($this->request->filePath?$this->request->filePath:'').$this->request->fileName.($this->request->queryString?'-'.md5($this->request->queryString):'').($this->request->fileExt?'.'.$this->request->fileExt:'')));
								$res->getResponse();
								}
							}
						}
					}
				$this->response->setHeader('X-Rest-Cache','Live');
				/* Removing cache contents when modifying ressources :: Hum, it could be annoying when modifying a lot of resource in a single server hit  */
				// Maybe do it after sending the response (into the RestServer)
				// Should create a rest driver to post and repeats uncaches on each servers of a rest grappe
				if($this->core->server->cache&&($this->request->method==RestMethods::PUT||$this->request->method==RestMethods::POST||$this->request->method==RestMethods::DELETE)&&$this->request->controller!='cache')
					{
					if($this->response->getHeader('X-Rest-Uncache')&&$uncache=explode('|',$this->response->getHeader('X-Rest-Uncache')))
						{
						foreach($uncache as $unc)
							{
							$res=new RestResource(new RestRequest(RestMethods::DELETE,'/cache/'.$this->core->server->cache.$unc,array()));
							$res->getResponse();
							}
						}
					else
						{
						$res=new RestResource(new RestRequest(RestMethods::DELETE,'/cache/'.$this->core->server->cache.'/'.$this->request->controller.($this->request->filePath?$this->request->filePath:''),array()));
						$res->getResponse();
						}
					}/**/
				}
			}
		catch(RestException $e)
			{
			$this->response=new RestResponse();
			$this->response->code=$e->getCode();
			$this->response->setHeader('Content-Type','text/plain');
			$this->response->content=$e->getMessage()."\n".$e->getDebug();
			$stack=$e->getTrace();
			foreach($stack as $key=>$level)
				{
				$this->response->content.="\n".xcUtilsInput::filterAsCData('Stack'.$key.' - File : '.$level['file'].' Line : '.$level['line'].' Function :'.$level['function']);
				}
			$this->response->setHeader('Content-Length',strlen($this->response->content));
			foreach($e->headers as $name => $value)
				$this->response->setHeader($name,$value);
			// Debug
			if($this->response->code==RestCodes::HTTP_500)
				{
				mail('webmaster@elitwork.com', 'Debug: '. $_SERVER['REQUEST_METHOD'] .'-'.$_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'], $this->response->content."\n".Varstream::export($this->core));
				//trigger_error('ERROR: '.$this->request->uri.': '.$this->response->content);
				}
			}
		return $this->response;
		}
	}