<?php
class database
	{
	private static $instance;
	public static function load($config,$core)
		{
		return self::$instance=new $config->type($config,$core);
		}
	public static function getInstance()
		{
		if(!isset(self::$instance))
			{
			throw new Exception('database::getInstance() -> No database loaded yet !');
			}
		return self::$instance;
		}
	}
?>