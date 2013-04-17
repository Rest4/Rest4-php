<?php
class RestFsDriver extends RestDriver
	{
	function __construct(RestRequest $request)
		{
		parent::__construct($request);
		}
	function createParentFolders()
		{
		$parentFolder='';
		for($i=1;$i<$this->request->uriNodes->count()-1;$i++)
			{
			$parentFolder.='/'.$this->request->uriNodes[$i];
			clearstatcache(false,'.'.$parentFolder);
			if(!file_exists('.'.$parentFolder))
				{
				$res=new RestResource(new RestRequest(RestMethods::PUT,
					'/fs'.$parentFolder.'/?force=yes'));
				$res=$res->getResponse();
				if($res->code!=RestCodes::HTTP_201)
					{
					throw new RestException(RestCodes::HTTP_500,
						'Unable to create (/fs'.$parentFolder.'/)');
					}
				}
			}
		}
	}