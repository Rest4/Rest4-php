<?php
class RestAppIndexDriver extends RestAppDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=new stdClass();
		$drvInf->name='App: Index Driver';
		$drvInf->description='Prints the web application interface.';
		$drvInf->usage='/app/{document.i18n}/index.{document.type}';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='text/varstream';
		$drvInf->methods->head=$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='text/html';
		return $drvInf;
		}
	function get()
		{
		$this->prepare();
		if(isset($this->core->server->debug)&&$this->core->server->debug)
			{
			// Loading profile scripts
			$this->loadDatas('/mpfsi/public/javascript/widgets.dat?mode=light',
				$this->core->widgetsScripts=new stdClass(),true);
			}
		return $this->finish();
		}
	}
