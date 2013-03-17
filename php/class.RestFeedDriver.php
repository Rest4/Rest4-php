<?php
class RestFeedDriver extends RestDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='Feed: Driver';
		$drvInf->description='Retrieve the given feeds entries from the network.';
		$drvInf->usage='/feed.ext(?uri=httpuri)';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='text/varstream';
		$drvInf->methods->head=$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='text/varstream';
		$drvInf->methods->get->queryParams=new MergeArrayObject();
		$drvInf->methods->get->queryParams[0]=new stdClass();
		$drvInf->methods->get->queryParams[0]->name='uri';
		$drvInf->methods->get->queryParams[0]->filter='httpuri';
		$drvInf->methods->get->queryParams[0]->multiple=true;
		$drvInf->methods->get->queryParams[0]->required=true;
		return $drvInf;
		}
	function head()
		{
		$response=$this->get();
		$response->content='';
		return $response;
		}
	function get()
		{
		$response=new RestResponse();
		$response->content=new stdClass();
		$response->content->values=new MergeArrayObject();
		if(!xcUtils::classExists('simplePie'))
			throw new RestException(RestCodes::HTTP_400,'The simplePie library is not installed.');
		$feed = new simplePie(); // require simplePie lib
		$feed->set_feed_url($this->queryParams->uri[0]); // Not multiple yet
		$feed->init();
		if($feed->error)
				{
				throw new RestException(RestCodes::HTTP_500,'SimplePie got an error: '.$feed->error.' ('.$this->queryParams->uri[0].')');
				}
		foreach ($feed->get_items() as $item)
			{
			$feed=$item->get_feed();
			$entry=new stdClass();
			$entry->title=xcUtilsInput::filterAsPcdata($item->get_title());
			$entry->link=xcUtilsInput::filterAsCdata($item->get_permalink());
			$entry->description=xcUtilsInput::filterAsPcdata($item->get_description(),250).'...';
			$entry->date=$item->get_date();
			$entry->feed=xcUtilsInput::filterAsCdata($feed->get_permalink());
			$entry->source=xcUtilsInput::filterAsPcdata($feed->get_title());
			$entry->favicon=xcUtilsInput::filterAsCdata($feed->get_favicon());
			$response->content->values->append($entry);
			}
		$response->setHeader('Content-Type','text/varstream');
		return $response;
		}
	}
RestFeedDriver::$drvInf=RestFeedDriver::getDrvInf();