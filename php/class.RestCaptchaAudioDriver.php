<?php
class RestCaptchaAudioDriver extends RestDriver
{
  static $drvInf;
  public function __construct(RestRequest $request)
  {
    parent::__construct($request);
  }
  public static function getDrvInf($methods=0)
  {
    $drvInf=new stdClass();
    $drvInf->name='Captcha: Sound Driver';
    $drvInf->description='Provide an audio captcha.';
    $drvInf->usage='/captcha/audio.wav?code=([a-z0-9]+)';
    $drvInf->methods=new stdClass();
    $drvInf->methods->options=new stdClass();
    $drvInf->methods->options->outputMimes='*';
    $drvInf->methods->head=$drvInf->methods->get=new stdClass();
    $drvInf->methods->get->queryParams=new MergeArrayObject();
    $drvInf->methods->get->queryParams[0]=new stdClass();
    $drvInf->methods->get->queryParams[0]->name='code';
    $drvInf->methods->get->queryParams[0]->required=true;
    $drvInf->methods->get->queryParams[0]->filter='parameter';
    $drvInf->methods->get->outputMimes='audio/x-wav';

    return $drvInf;
  }
  public function head()
  {
    return new RestResponse(
       RestCodes::HTTP_200,
       array('Content-Type'=>'audio/x-wav')
    );
  }
  public function get()
  {
    $response = new RestResponse(
       RestCodes::HTTP_200,
       array('Content-Type'=>'audio/x-wav')
    );
    $code=$this->queryParams->code;
		$code_length=strlen($code);
		$sound_data='';
		$blank=$this->getSoundData('sound/blank.dat');
		for($i=0; $i<$code_length; $i++) {
			$sound_data.= $blank . $this->getSoundData(
			  'sound/fr-FR/' . substr($code, $i, 1) . '.dat') . $blank;
		}
		$sound_data.=$blank. $blank. $blank. $blank. $blank;
		$sound_data_size = strlen($sound_data);
		$response->setHeader('Content-type', 'audio/x-wav');
		$response->setHeader('Accept-Ranges', 'bytes');
		$response->setHeader('Content-disposition','inline; filename=audio.wav');
		$response->setHeader('Expires','0');
		$response->setHeader('Pragma','no-cache');
		$response->content = 'RIFF'
		  . $this->size2asc($sound_data_size+40)
		  . $this->getSoundData('sound/header.dat',0,32)
		  . $this->size2asc($sound_data_size) . $sound_data;
		return $response;
  }
  private function size2asc($size)
	{
		$bin=decbin($size);
		while(!is_int(strlen($bin)/8)) { $bin='0'.$bin; }
		$bin2='';
		for($i=0; $i<strlen($bin)/8; $i++) {
			$bin2.=substr($bin,strlen($bin)-8-($i*8),strlen($bin)-($i*8));
		}
		while(strlen($bin2)<32) {
		  $bin2.='0';
		}
		$asc = '';
		for ($i = 0, $len = strlen($bin2); $i < $len; $i += 8) {
			$asc .= chr(bindec(substr($bin2,$i,8)));
		}
		return $asc;
	}

	private function getSoundData($directory, $start=0, $end=0)
	{
		$fulldir=xcUtils::fileExists($directory);
		$handle=@fopen($fulldir,'rb');
		@fseek ($handle, $start);
		if(!$end) { $end=@filesize ($fulldir); }	
		$contents = @fread ($handle, $end);
		@fclose ($handle);
		return $contents;
	}
}

