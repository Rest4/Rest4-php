<?php
class image
{
  public $img;
  public $w;
  public $h;
  public function image($directory,$type)
  {
    $this->img=false;
    $this->w=false;
    $this->h=false;
    switch (strtolower($type)) {
    case 'jpg':
    case 'jpeg':
      if (!$this->img=@imagecreatefromjpeg($directory)) {
        return false;
      }
      break;
    case 'png':
      if (!$this->img=@imagecreatefrompng($directory)) {
        return false;
      }
      break;
    case 'gif':
      if (!$this->img=@imagecreatefromgif($directory)) {
        return false;
      }
      break;
    }
    if ($this->img) {
      return true;
    } else {
      return false;
    }
  }
  public function getWidth()
  {
    if ($this->w||$this->w = @imagesx($this->img)) {
      return $this->w;
    } else {
      return false;
    }
  }
  public function getHeight()
  {
    if ($this->h||$this->h = @imagesy($this->img)) {
      return $this->h;
    } else {
      return false;
    }
  }
  public function createThumb($directory,$type=false,$width=200,$height=150,$pack=true,$quality=65)
  {
    if (!$type) {
      $type=str_replace('^(.*)\.(a-z0-9)$', '', $directory);
    }
    if (strtolower($type)=='jpeg') {
      $type='jpg';
    }
    $this->getWidth();
    $this->getHeight();
    if ($this->w>$width||$this->h>$height) {
      if (($this->w/$this->h)>=$width/$height) {
        $w=$width;
        $h=$this->h/$this->w*$width;
        $x=0;
        $y=($height-$h)/2;
      } else
        if (($this->w/$this->h)<$width/$height) {
          $w=$this->w/$this->h*$height;
          $h=$height;
          $x=($width-$w)/2;
          $y=0;
        }
    } else {
      $w=&$this->w;
      $h=&$this->h;
      $x=($width-$w)/2;
      $y=($height-$h)/2;
    }
    if((!$thumb=@ImageCreateTrueColor(($pack?$w:$width), ($pack?$h:$height)))
        ||($type!='jpg'&&!imagealphablending($thumb, false))
        ||($type!='jpg'&&!imagesavealpha($thumb, true))
        ||($type=='jpg'&&!@imagefill($thumb,0,0,@imagecolorallocate($thumb, 255, 255, 255)))
        ||(!@imagecopyresampled($thumb,$this->img,($pack?0:$x),($pack?0:$y),0,0,$w,$h,$this->w,$this->h))) {
      return false;
    } else {
      switch (strtolower($type)) {
      case 'jpg':
        if (!@imagejpeg($thumb, $directory, $quality)) {
          return false;
        }
        break;
      case 'png':
        if (!@imagepng($thumb, $directory, ceil($quality/100*9))) {
          return false;
        }
        break;
      case 'gif':
        if (!@imagegif($thumb, $directory)) {
          return false;
        }
        break;
      }

      return true;
    }
  }
}

