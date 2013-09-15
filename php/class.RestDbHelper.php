<?php
class RestDbHelper
	{
	public static $schemas;
	// Helper to retrieve the table schemas
	public static function getTableSchema($database,$table)
		{
		if(!isset(self::$schemas))
			self::$schemas=new stdClass();
		if(!isset(self::$schemas->{$database.'_'.$table}))
			{
			$res=new RestResource(new RestRequest(RestMethods::GET,
				'/db/'.$database.'/'.$table.'.dat'));
			$res=$res->getResponse();
			if($res->code!=RestCodes::HTTP_200)
				{
				throw new RestException(RestCodes::HTTP_500,
					'Could not retrieve the table schema ("'.$database.'.'.$table.'").');
				}
			self::$schemas->{$database.'_'.$table}=$res->vars;
			}
		return self::$schemas->{$database.'_'.$table};
		}
	}
