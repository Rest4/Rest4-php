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
		$this->cache=new stdClass();
		$this->cache->type='none';

		/* Config : Loading conf.dat files */
		$res=new RestResource(new RestRequest(RestMethods::GET,'/mpfs/conf/conf.dat?mode=append'));
		$response=$res->getResponse();
		if($response->code!=RestCodes::HTTP_200)
			throw new RestException(RestCodes::HTTP_500,'Unable to load the server configuration.');
		Varstream::import($this,$response->getContents());

		/* Config : Initializing global vars */
		// Development purpose (test server custom tilde)
		if(isset($this->server->srvtld))
			{
			$this->server->domain=$this->server->domain.'.'.$this->server->srvtld;
			if(isset($this->server->cdn))
				$this->server->cdn=$this->server->cdn.'.'.$this->server->srvtld;
			}
		// Setting server location
		$this->server->location=$this->server->protocol.'://'.$this->server->domain.'/';
		// Setting the currently used protocol (http or https)
		if(isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on')
			{
			$this->server->protocol='https';
			}
		// Force https if protocol is set to https in the config file
		else if(isset($this->server->protocol)&&$this->server->protocol=='https')
			{
			$response=new RestResponse(RestCodes::HTTP_301,
				array('Content-Type'=>'text/plain','Location'=>'https'.'://'.$this->server->domain.$_SERVER['REQUEST_URI']),
				'Not allowed to access this ressource with HTTP use HTTPS instead.');
			$this->outputResponse($response);
			return;
			}
		// Defaults to http
		else
			$this->server->protocol='http';

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
			$request->setHeader('Authorization','Basic '.base64_encode((isset($_SERVER['PHP_AUTH_USER'])?
				$_SERVER['PHP_AUTH_USER']:'-').':'.(isset($_SERVER['PHP_AUTH_PW'])?$_SERVER['PHP_AUTH_PW']:'-')));
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
			if($request->getHeader('Content-Type')=='text/varstream'||strpos($request->content,'#text/varstream')===0
				||strpos($request->content,'#application/internal')===0) // Backward compatibility issue, remove after 2013-05-15
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

		/* Authentication : Verifying rights if a controller is set */
		if(!isset($this->auth,$this->auth->type))
			{
			$response=new RestResponse(RestCodes::HTTP_500,
				array('Content-Type'=>'text/plain'),
				'No authentication system defined (auth.type configuration var).');
			$this->outputResponse($response);
			return 1;
			}
		else if($this->auth->type=='none'||($request->uri=='/'))
			{
			$enabled=true;
			$this->user=new stdClass();
			$this->user->id=0;
			$this->user->login='webmaster';
			$this->user->group='webmasters';
			}
		else
			{
			$authorization=$request->getHeader('Authorization','text','cdata');
			$res=new RestResource(new RestRequest(RestMethods::GET,
				'/auth/'.$this->auth->type.'.dat?method='
				.RestMethods::getStringFromMethod($request->method)
				.'&source='.$this->auth->source
				.($authorization?
					'&authorization='.urlencode($authorization)
					:($this->auth->type=='session'?'&cookie='
						.urlencode($request->getHeader('Cookie')):''))
				));
			$response=$res->getResponse();
			$enabled=false;
			if($response->code==RestCodes::HTTP_200)
				{
				$this->user=$response->vars;
				foreach($this->user->rights as $right)
					{
					if(preg_match('#^'.$right->path.'$#',$request->uri))
							{
							$enabled=true;
							}
					}
				}
			}

		/* Processing : Selecting the response to send */
		if($enabled)
			{
			// can cancel idempotent requests
			if($request->method==RestMethods::GET||$request->method==RestMethods::HEAD
				||$request->method==RestMethods::OPTIONS)
				{
				ignore_user_abort(0);
				}
			else
				{
				ignore_user_abort(1);
				}
			$ressource=new RestResource($request);
			$response=$ressource->getResponse();
			}
		// authentified, but not authorized
		else if(isset($this->user,$this->user->id)&&$this->user->id)
			{
			$response=new RestResponse(RestCodes::HTTP_403,
				array('Content-Type'=>'text/plain'),	'Not allowed to access this ressource.');
			}
		// not authentified, send HTTP authentication request
		else if($this->server->protocol=='https')
			{
			$res=new RestResource(new RestRequest(RestMethods::POST,'/auth/'.$this->auth->type.'.dat'));
			$response=$res->getResponse();
			}
		// not authentified
		else
			{
			$response=new RestResponse(RestCodes::HTTP_403,
				array('Content-Type'=>'text/plain'),	'Not allowed to access this ressource.');
			}

		/* Database : Closing links left opened */
		if(sizeof($this->db->links))
			$this->db->close();
		$this->outputResponse($response);

		// Exiting
		return ($response->code>=400?1:0);
		}
function outputResponse($response)
		{
		/* Cache : Setting client cache directives */
		if(!$response->headerIsset('Cache-Control'))
			$response->setHeader('Cache-Control',(isset($this->http,$this->http->cache)?
				(isset($this->user,$this->user->id)&&$this->user->id?'private':'public') .', max-age='
				.$this->http->maxage .(isset($this->http->revalidate)?', must-revalidate':''):'no-cache'));

		/* Cache : Add a last modified header if resource is live */
		if($response->getHeader('X-Rest-Cache')=='Live')
				$response->setHeader('Last-Modified',gmdate('D, d M Y H:i:s', time()) . ' GMT');

		/* Response : Adding extra headers */
		if($response->content) // Need to use getContents
			$response->setHeader('Content-Length',strlen($response->content));
		$response->setHeader('X-Powered-By','Restfor');
		//$response->setHeader('X-Nb-Reqs',sizeof($this->db->requests)); // Database feedback

		/* Response : Outputting response content */
		// Output headers
		header('HTTP/1.1 '.$response->code.' '.constant('RestCodes::HTTP_'.$response->code.'_MSG'));
		foreach($response->headers as $name => $value)
			{
			if($name=='Content-Type')
				header($name.': '.$value.'; charset="UTF-8"');
			else
				header($name.': '.$value);
			}

		// Disable default compression (is it a good idea ?)
		// Why not embrace config instead of try to change her ?
		ini_set('zlib.output_compression', 'Off');

		// Saving response length
		$resplen=$response->getHeader('Content-Length');
		// Enable gzip according to the config
		 if($this->http->gzip!=0&&$this->http->gzip<$resplen)
			ob_start("ob_gzhandler");

		// Outputting content
		if($response instanceof RestStreamedResponse&&!$response->content)
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
