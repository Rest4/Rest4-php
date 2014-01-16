<?php
class RestFsStreamResponse extends RestStreamedResponse
{
  private $_filePathes;
  private $_handle;
  private $_i=-1;
  public function __construct($code=RestCodes::HTTP_200, $headers=array(),
    $filePathes,$downloadFilename='') {
    if ($downloadFilename) {
      $headers['Content-Disposition']='attachment;'
        . ' filename="'.$downloadFilename.'"';
      $headers['X-Rest-Cache']='None';
    }
    parent::__construct($code, $headers);
    if (!is_array($filePathes)) {
      throw new RestException(HTTP_500,
        'Internal Server Error',
        'Filepathes must be an array of pathes.');
    }
    $this->_filePathes=$filePathes;
  }
  public function pump()
  {
    // Opening the next file if none open
    if ((!$this->_handle)&&isset($this->_filePathes[++$this->_i])) {
      $this->_handle=fopen($this->_filePathes[$this->_i], 'r');
    }
    // Stop if no more files
    if (!$this->_handle) {
      return '';
    }
    // If no buffer, retrieving file content line by line
    if ($this->bufferSize===0) {
      $chunk = fgets($this->_handle, 4096);
    }
    // else reading file content to fit the buffer size
    else
      if (!feof($this->_handle)) {
        $chunk = fread($this->_handle, $this->bufferSize);
      } else {
        $chunk=false;
      }
    // Returning the buffer content
    if ($chunk !== false) {
      return $chunk;
    }
    // Managing end of file
    @fclose($this->_handle);
    $this->_handle=null;
    // Trying to open another file
    if (isset($this->_filePathes[$this->_i+1])) {
      return "\n";  // Add newline, do not work for binary files
    }
    // /!\ if removed, filesize on RestMpfsFileDriver take this char in count
    return '';
  }
}

