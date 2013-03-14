<?php
class RestDocDriverDriver extends RestSiteDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new xcDataObject();
		$drvInf->name='Doc: Driver Driver';
		$drvInf->description='Show details of the selected controller.';
		$drvInf->usage='/doc/{user.i18n}/controller/(name).{document.type}';
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
		$mainModule=new xcDataObject();
		$mainModule->template=$this->loadTemplate('/sites/doc/driver/'.$this->core->document->type.'/index.tpl','mainModules.0',true);
		$this->loadLocale('/sites/'.$this->request->uriNodes[0].($this->request->uriNodes[0]!='doc'?',doc':'').',default/driver/lang/$'.($name?'-'.$name:'').'.lang', 'mainModules.0', true);
		$theClass='Rest'.$this->request->uriNodes[3].'Driver';
		if(isset($theClass::$drvInf))
			{
			$mainModule->syntax=$theClass::$drvInf;
			}
		$source=$this->loadResource('/mmpfs/www,xcms/php/class.Rest'.$this->request->uriNodes[3].'Driver.php',true);
		$mainModule->source=xcUtilsInput::filterAsCdata($source->content);
		$this->core->mainModules->append($mainModule);
		$this->core->layoutType='large';
		return $this->finish();
		}
	}
RestDocDriverDriver::$drvInf=RestDocDriverDriver::getDrvInf();