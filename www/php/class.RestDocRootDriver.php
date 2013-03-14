<?php
class RestDocRootDriver extends RestSiteDriver
	{
	static $drvInf;
	static function getDrvInf()
		{
		$drvInf=new xcDataObject();
		$drvInf->name='Doc: Root Driver';
		$drvInf->description='Show each controllers available..';
		$drvInf->usage='/doc/{user.i18n}/root.{document.type}';
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
		$mainModule->template=$this->loadTemplate('/sites/doc/root/'.$this->core->document->type.'/index.tpl','mainModules.0',true);
		$this->loadLocale('/sites/'.$this->request->uriNodes[0].($this->request->uriNodes[0]!='doc'?',doc':'').',default/root/lang/$.lang', 'mainModules.0', true);
		$mainModule->values=new xcObjectCollection();
		$this->loadDatas('/mmpfsi/www,xcms/php.dat',$files=new xcDataObject(),true);
		if(isset($files->files)&&$files->files->count())
			{
			foreach($files->files as $file)
				{
				$theClass=''; $name='';
				if(strpos($file->name,'class.Rest')===0&&strpos($file->name,'Controller.php')===strlen($file->name)-14
					&&$name=substr($file->name,10,strlen($file->name)-24))
					{
					$entry=new xcDataObject();
					$entry->name=$name;
					$theClass='Rest'.$name.'Controller';
					if(isset($theClass::$ctrInf,$theClass::$ctrInf->description))
						{
						$entry->description=$theClass::$ctrInf->description;
						}
					$mainModule->values->append($entry);
					}
				}
			}
		$this->core->mainModules->append($mainModule);
		return $this->finish();
		}
	}
RestDocRootDriver::$drvInf=RestDocRootDriver::getDrvInf();