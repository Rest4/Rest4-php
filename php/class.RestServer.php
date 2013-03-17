<?php
class RestServer extends stdClass
	{
	private static $instance;
	public static function Instance()
		{
		if(!isset(self::$instance))
			{
			self::$instance = new RestServer();
			}
		return self::$instance;
		}
	private function __construct()
		{
		}
	function run()
		{
		/* Pathes : Retrieving server pathes */
		$this->server=new stdClass();
		$this->server->paths=new MergeArrayObject();
		foreach(explode(PATH_SEPARATOR, ini_get('include_path')) as $path)
			{
			$this->server->paths->append(str_replace('\\', '/', $path) . ($path[strlen($path)-1]=='/'?'':'/'));
			}

		/* Cache : Set cache type here to also cache the configuration file */
		$this->server->cache='';

		/* Config : Loading conf.dat files */
		$res=new RestResource(new RestRequest(RestMethods::GET,'/mpfs/conf/conf.dat?mode=merge'));
		$response=$res->getResponse();
		if($response->code!=RestCodes::HTTP_200)
			throw new RestException(RestCodes::HTTP_500,'Unable to load the server configuration.');
		Varstream::loadObject($this,$response->content);

		/* Config : Initializing global vars */
		if((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on')||(isset($_SERVER['SERVER_PORT'])&&$_SERVER['SERVER_PORT']=='443'))
			$this->server->protocol='https';
		if(isset($this->server->srvtld))
			{
			$this->server->domain=$this->server->domain.'.'.$this->server->srvtld;
			if(isset($this->server->cdn))
				$this->server->cdn=$this->server->cdn.'.'.$this->server->srvtld;
			}
		$this->server->location=$this->server->protocol.'://'.$this->server->domain.'/';

		/* Database : Preparing database in case of use */
		$this->db = database::load($this->database,$this);

		/* Request : Creating request */
		$request=new RestRequest(
				RestMethods::getMethodFromString($_SERVER['REQUEST_METHOD']),
				str_replace('/restfor/www','',$_SERVER['REQUEST_URI'])
				);
		 foreach ($_SERVER as $name => $value)
	  // foreach (apache_request_headers() as $name => $value)
		   {
		   if (strpos($name, 'HTTP_')===0)
			   {
			   $request->setHeader(str_replace('_', '-',substr($name, 5)), $value);
			   }
		   }
		// 	PHP doesn't give Authorization header when using mod_php, must reconstituate
		if(isset($_SERVER['PHP_AUTH_USER'])&&$_SERVER['PHP_AUTH_USER'])
			$request->setHeader('Authorization','Basic '.base64_encode((isset($_SERVER['PHP_AUTH_USER'])?$_SERVER['PHP_AUTH_USER']:'-').':'.(isset($_SERVER['PHP_AUTH_PW'])?$_SERVER['PHP_AUTH_PW']:'-')));
		else if(isset($_SERVER['PHP_AUTH_DIGEST'])&&$_SERVER['PHP_AUTH_DIGEST'])
			$request->setHeader('Authorization','Digest '.$_SERVER['PHP_AUTH_DIGEST']);
		// Getting content of the request : http://php.net/manual/fr/features.file-upload.put-method.php
		// Humm, the content type can't be catched.. fuck !
		if(($request->getHeader('Content-Type')=='application/x-www-form-urlencoded'
			||$request->getHeader('Content-Type')=='multipart/form-data')&&$_POST)
			{
			$request->content=$_POST;
			$request->setHeader('Content-Type','application/array');
			}
		else
			{
			$request->content=file_get_contents('php://input');
			$request->setHeader('Content-Length',strlen($request->content));
			if($request->getHeader('Content-Type')=='text/varstream'||strpos($request->content,'#text/varstream')===0)
				{
				$request->parseVarsContent();
				$request->setHeader('Content-Type','text/varstream');
				}
			else if(strpos($request->content,'data:')===0)
				{
				$request->parseBase64Content();
				}
			else if(strpos($request->content,'{')===0)
				{
				$request->parseJsonContent();
				$request->setHeader('Content-Type','text/varstream');
				}
			else
				{
				$content=$request->content;
				try
					{
					$request->parseFormContent();
					$request->setHeader('Content-Type','text/varstream');
					}
				catch(RestException $e)
					{
					$request->content=$content;
					$request->setHeader('Content-Type','text/plain');
					}
				}
			}

		/* Authentification : Verifying rights if a controller is set */
		if($request->uri&&$request->uri!='/')
			{
			$authorization=$request->getHeader('Authorization','text','cdata');
			// Should include the above line in an "AuthSwitch" resource
			//$authType=xcUtilsInput::filterValue($authorization?strtolower(substr($authorization,0, strpos($authorization,' '))):$this->server->auth,'text','iparameter');
			$authType=$this->server->auth;
			$res=new RestResource(new RestRequest(RestMethods::GET,'/auth/'.$authType.'.dat?method='
				.RestMethods::getStringFromMethod($request->method)
				.($authorization?
					'&authorization='.urlencode($authorization)
					:($authType=='session'?'&cookie='.urlencode($request->getHeader('Cookie')):''))
				));
			$response=$res->getResponse();
			$enabled=false;
			if($response->code==RestCodes::HTTP_200)
				{
				$this->user=$response->content;
				foreach($this->user->rights as $right)
					{
					if(preg_match('#^'.$right->path.'$#',$request->uri))
							{
							$enabled=true;
							}
					}
				}
			}
		else
			{
			$enabled=true;
			}

		/* Processing : Selecting the response to send */
		if($enabled)
			{
			$ressource=new RestResource($request);
			$response=$ressource->getResponse();
			}
		// authentified, but not authorized
		else if($this->server->protocol!='https'||(isset($this->user,$this->user->id)&&$this->user->id))
			{
			$response=new RestResponse(RestCodes::HTTP_403,
			array('Content-Type'=>'text/plain'),
			'Not allowed to access this ressource.');
			}
		// not authentified, send authentification response
		else
			{
			$res=new RestResource(new RestRequest(RestMethods::POST,'/auth/'.$this->server->auth.'.dat'));
			$response=$res->getResponse();
			}

		/* Database : Closing links left opened */
		if(sizeof($this->db->links))
			$this->db->close();

		/* Trick : Keeping text/varstream internal (should review it with a real internal type) */
		if($response->getHeader('Content-Type')=='text/varstream'||$response->getHeader('Content-Type')=='text/lang')
			{
			$response->setHeader('Content-Type','text/plain');
			if($response->content instanceof MergeArrayObject||$response->content instanceof stdClass)
				{
				$response->content=Varstream::export($response->content);
				}
			else
				$response->content=xcUtilsInput::filterAsCdata(utf8_encode(print_r($response->content,true)));
			}

		/* Cache : Setting client cache directives */
		if(!$response->headerIsset('Cache-Control'))
			$response->setHeader('Cache-Control',(isset($this->http,$this->http->cache)?(isset($this->user,$this->user->id)&&$this->user->id?'private':'public') .', max-age=' . $this->http->maxage .(isset($this->http->revalidate)?', must-revalidate':''):'no-cache'));

		/* Cache : Add a last modified header if resource is live */
		if($response->getHeader('X-Rest-Cache')=='Live')
				$response->setHeader('Last-Modified',gmdate('D, d M Y H:i:s', time()) . ' GMT');

		/* Response : Adding extra headers */
		$response->setHeader('Content-Type',$response->getHeader('Content-Type').'; charset="UTF-8"');
		if($response->content)
			$response->setHeader('Content-Length',strlen($response->content));
		$response->setHeader('X-Powered-By','Restfor');
		//$response->setHeader('X-Nb-Reqs',sizeof($this->db->requests)); // Database feedback

		/* Response : Outputting response content */
		// Output headers
		header('HTTP/1.1 '.$response->code.' '.constant('RestCodes::HTTP_'.$response->code.'_MSG'));
		foreach($response->headers as $name => $value)
			header($name.': '.$value);

		// Disable default compression
		ini_set('zlib.output_compression', 'Off');

		// Saving response length
		$resplen=$response->getHeader('Content-Length');
		// Enable gzip according to the config
		if($this->http->gzip!=0&&$this->http->gzip<$resplen)
			ob_start("ob_gzhandler");

		// Outputting content
		if($response instanceof RestResponseStream)
			{
			while(($cnt=$response->pump())!=='')
				{
				echo $cnt;
				}
			}
		else
			echo $response->getContents();

		// Flush gzip according to the config
		if($this->http->gzip<$resplen)
			ob_end_flush();
		}
	}
