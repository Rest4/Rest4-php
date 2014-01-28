<?php
class RestCaptchaVisualDriver extends RestDriver
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
    $drvInf->usage='/captcha/visual.png?code=([a-z0-9]+)';
    $drvInf->methods=new stdClass();
    $drvInf->methods->options=new stdClass();
    $drvInf->methods->options->outputMimes='*';
    $drvInf->methods->head=$drvInf->methods->get=new stdClass();
    $drvInf->methods->get->queryParams=new MergeArrayObject();
    $drvInf->methods->get->queryParams[0]=new stdClass();
    $drvInf->methods->get->queryParams[0]->name='code';
    $drvInf->methods->get->queryParams[0]->required=true;
    $drvInf->methods->get->queryParams[0]->filter='parameter';
    $drvInf->methods->get->outputMimes='image/png';

    return $drvInf;
  }
  public function head()
  {
    return new RestResponse(
       RestCodes::HTTP_200,
       array('Content-Type'=>'image/png')
    );
  }
  public function get()
  {
    $response = new RestResponse(
       RestCodes::HTTP_200,
       array('Content-Type'=>'image/png')
    );
    $code=$this->queryParams->code;
		$code_length=strlen($code);
		// Text
		$text_size = 30;
		$text_font = xcUtils::fileExists('font/arial.ttf');
		// Image
		$img_width = $code_length * $text_size;
		$img_heigth = $text_size * 2 ;
		$img = imagecreatetruecolor($img_width, $img_heigth);
		$img_text_color = ImageColorAllocate($img, 128, 128, 128);
		$img_line_color = ImageColorAllocate($img, 128, 128, 128);
		$img_bg_color = ImageColorAllocate($img, 255, 255, 255);
		@imagefill($img, 0, 0, $img_bg_color);
		for($i=0; $i<$code_length; $i++) {
			imagettftext($img, $text_size, 0, $text_size * $i,
			  ($img_heigth - $text_size) / (8/rand(1,4)) + $text_size,
			  $img_text_color, $text_font, substr($code, $i, 1));
		}
		for($i=0; $i<rand(1,3); $i++) {
			@imageline( $img, rand(0,$img_width/4),
			  rand($img_heigth/4,($img_heigth*3)/4),
			  rand(($img_width*3)/4,$img_width*3),
			  rand($img_heigth/4,($img_heigth*3)/4),
			  $img_line_color);
		}
		$response->setHeader('Content-type', 'image/png');
		ob_start();
		imagepng($img);
    $response->content = ob_get_contents();
    ob_end_clean();
		imagedestroy($img);
		return $response;
	}
}

