<?php
require 'php/class.xcUtils.php';
try
	{
	// Auto loads classes files
	function __autoload($class)
		{
		if(!strpos($class,'_'))
			{
			if(xcUtils::fileExists('php/class.' . $class . '.php'))
				{
				require_once 'php/class.' . $class . '.php';
				}
			else
				trigger_error('Class not found : php/class.' . $class . '.php');
			}
		}
	// Instantiate the Server
	$server=RestServer::Instance();
	$server->run();
	unset($server);
	}
catch (Exception $e)
	{
	$content='Alert, fatal error on ' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']
		.'File: ' . $e->getFile() . "\n"
		.'Line: ' . $e->getLine() . "\n"
		.'Message: '.$e->getMessage();
	$stack=$e->getTrace();
	foreach($stack as $key=>$level)
		{
		$content.="\n".'Stack'.$key.' - File : '.$level['file'].' Line : '.$level['line'].' Function :'.$level['function'];
		}
	if(defined('DEBUG_MAIL')&&DEBUG_MAIL)
		@mail(DEBUG_MAIL,'Alert, fatal error on ' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'],$content);
	header('Date: ' . gmdate('D, d M Y H:i:s') . ' GMT', true, 500);
	echo 'Internal Servor Error, you just discovered a new bug.';
	if(defined('DEBUG_PRINT')&&DEBUG_PRINT)
		echo $content;
	}
