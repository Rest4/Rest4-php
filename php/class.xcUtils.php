<?php
class xcUtils
	{
	
	public static function deconcatenate($mConfig, $cConfig=false)
		{
		if($cConfig) { $mConfig .= $cConfig . '-' . $mConfig; }
		$tConfig = array();
		$tTab=explode('-', $mConfig);
		foreach($tTab as $t)
			{
			preg_match('/^([a-z0-9_]+)=([a-z0-9_:\.\/]*)$/i',$t,$tT);
			$tConfig[$tT[1]]=(isset($tT[2])?$tT[2]:'');
			}
		return $tConfig;
		}
	public static function explodeCaps($string)
		{
		$strLen=strlen($string);
		$words=array();
		$curWord='';
		for($i=0; $i<$strLen; $i++)
			{
			if($string[$i]===strtolower($string[$i]))
				{ $curWord.=$string[$i]; }
			else
				{
				array_push($words, $curWord);
				$curWord=strtolower($string[$i]);
				}
			}
		array_push($words, $curWord);
		return $words;
		}

	public static function classExists($class)
		{
		if(strpos($class,'xcm')===0)
			{
			$dir=xcUtils::explodeCaps($class);
			return xcUtils::fileExists('module/'.$dir[1].'/php/class.' . $class . '.php');
			}
		else
			return xcUtils::fileExists('php/class.' . $class . '.php');
		}

	public static function fileExists($filename)
		{
		foreach(explode(PATH_SEPARATOR, ini_get('include_path')) as $path)
			{
			$path=str_replace('\\', '/', $path) . '/';
			if(file_exists($path . $filename)) { return $path . $filename; }
			}
		return false;
		}

	public static function getMimeFromExt($ext)
		{
		$core=RestServer::Instance();
		if(!isset($core->mimes))
			return 'text/varstream';
		for($i=$core->mimes->count()-1; $i>=0; $i--)
			{
			if($core->mimes[$i]->ext==$ext)
				{
				return $core->mimes[$i]->mime;
				}
			}
		return '';
		}

	public static function getExtFromMime($mime)
		{
		$core=RestServer::Instance();
		if(!isset($core->mimes))
			return 'dat';
		for($i=$core->mimes->count()-1; $i>=0; $i--)
			{
			if($core->mimes[$i]->mime==$mime)
				{
				return $core->mimes[$i]->ext;
				}
			}
		return '';
		}

	public static function getMimeFromFilename($filename)
		{
		return xcUtils::getMimeFromExt(xcUtils::getExtFromFilename($filename));
		}

	public static function getExtFromFilename($filename)
		{
		$ext='';
		for($i=strlen($filename)-1; $i>=0; $i--)
			{
			if($filename[$i]!='.')
				$ext=$filename[$i].$ext;
			else
				break;
			}
		return $ext;
		}

	public static function getNameFromFilename($filename)
		{
		$name='';
		if(strpos($filename,'.')!==false)
			{
			$name=substr($filename,0,strrpos($filename,'.'));
			}
		return $name;
		}

	public static function getFolderFromPath($path)
		{
		$folder='';
		for($i=strlen($path)-1; $i>=0; $i--)
			{
			if($path[$i]=='/')
				{
				$folder=substr($path,0,$i+1);
				break;
				}
			}
		return $folder;
		}

	public static function getFilenameFromPath($path)
		{
		return preg_replace('/^(?:.*)\/([^\/]+)$/','$1',$path);
		}
	}
?>
