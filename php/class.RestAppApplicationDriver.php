<?php
class RestAppApplicationDriver extends RestAppDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new stdClass();
		$drvInf->name='App: Index Driver';
		$drvInf->description='Generate the web application manifest.';
		$drvInf->usage='/app/{document.i18n}/index.{document.type}';
		$drvInf->methods=new stdClass();
		$drvInf->methods->options=new stdClass();
		$drvInf->methods->options->outputMimes='text/varstream';
		$drvInf->methods->head=$drvInf->methods->get=new stdClass();
		$drvInf->methods->get->outputMimes='text/cache-manifest';
		return $drvInf;
		}
	function get()
		{
		$this->prepare();
		// Loading lang files
		$this->loadDatas('/mpfsi/public/lang/'.$this->core->document->i18nFallback
			.'.dat?mode=light',$this->core->languageFiles=new stdClass(),true);
		// Loading widgets scripts
		$this->loadDatas('/mpfsi/public/javascript/profiles.dat?mode=light',
			$this->core->profilesScripts=new stdClass(),true);
		// Loading profile scripts
		$this->loadDatas('/mpfsi/public/javascript/widgets.dat?mode=light',
			$this->core->widgetsScripts=new stdClass(),true);
		return $this->finish();
		}
	}
RestAppApplicationDriver::$drvInf=RestAppApplicationDriver::getDrvInf();