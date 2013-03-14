<?php
class RestAppApplicationDriver extends RestAppDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new xcDataObject();
		$drvInf->name='App: Index Driver';
		$drvInf->description='Generate the web application manifest.';
		$drvInf->usage='/app/{document.i18n}/index.{document.type}';
		$drvInf->methods=new xcDataObject();
		$drvInf->methods->options=new xcDataObject();
		$drvInf->methods->options->outputMimes='application/internal';
		$drvInf->methods->head=$drvInf->methods->get=new xcDataObject();
		$drvInf->methods->get->outputMimes='text/cache-manifest';
		return $drvInf;
		}
	function get()
		{
		$this->prepare();
		// Loading lang files
		$this->loadDatas('/mmpfsi/public/lang/'.$this->core->document->i18nFallback.'.dat?mode=light',$this->core->languageFiles=new xcDataObject(),true);
		// Loading widgets scripts
		$this->loadDatas('/mpfsi/public/javascript/profiles.dat?mode=light',$this->core->profilesScripts=new xcDataObject(),true);
		// Loading profile scripts
		$this->loadDatas('/mpfsi/public/javascript/widgets.dat?mode=light',$this->core->widgetsScripts=new xcDataObject(),true);
		return $this->finish();
		}
	}
RestAppApplicationDriver::$drvInf=RestAppApplicationDriver::getDrvInf();