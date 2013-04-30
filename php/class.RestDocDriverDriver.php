<?php
class RestDocDriverDriver extends RestSiteDriver
	{
	static $drvInf;
	static function getDrvInf($methods=0)
		{
		$drvInf=new stdClass();
		$drvInf->name='Doc: Driver Driver';
		$drvInf->description='Show details of the selected controller.';
		$drvInf->usage='/doc/{user.i18n}/controller/(name).{document.type}';
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
		$mainModule=new stdClass();
		$mainModule->template=$this->loadTemplate('/sites/doc/driver/'
			.$this->core->document->type.'/index.tpl','mainModules.0',true);
		$this->loadLocale('/sites/'.$this->request->uriNodes[0]
			.($this->request->uriNodes[0]!='doc'?',doc':'').',default/driver/lang/$.lang', 'mainModules.0', true);
		$theClass='Rest'.$this->request->uriNodes[3].'Driver';
		if($drvInf=$theClass::getDrvInf())
			{
			$mainModule->syntax=$drvInf;
			}
		$source=$this->loadResource('/mpfs/php/class.Rest'.$this->request->uriNodes[3].'Driver.php',true);
		$mainModule->source=xcUtilsInput::filterAsCdata($source->content);
		$this->core->mainModules->append($mainModule);
		$this->core->layoutType='large';
		return $this->finish();
		}
	}
