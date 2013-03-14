<?php
class RestAppIndexDriver extends RestAppDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new xcDataObject();
		$drvInf->name='App: Index Driver';
		$drvInf->description='Prints the web application interface.';
		$drvInf->usage='/app/{document.i18n}/index.{document.type}';
		$drvInf->methods=new xcDataObject();
		$drvInf->methods->options=new xcDataObject();
		$drvInf->methods->options->outputMimes='application/internal';
		$drvInf->methods->head=$drvInf->methods->get=new xcDataObject();
		$drvInf->methods->get->outputMimes='text/html';
		return $drvInf;
		}
	function get()
		{
		$this->prepare();
		return $this->finish();
		}
	}
RestAppIndexDriver::$drvInf=RestAppIndexDriver::getDrvInf();