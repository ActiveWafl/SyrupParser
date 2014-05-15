<?php
spl_autoload_register(function($classname){
	if (!class_exists($classname,false))
	{
		if (substr($classname,0,10)=="Wafl\\Syrup" || substr($classname,0,11)=="\\Wafl\\Syrup")
		{
			$filePath = __DIR__.DIRECTORY_SEPARATOR.str_replace("\\", DIRECTORY_SEPARATOR, $classname).".php";
			if (file_exists($filePath))
			{
				require_once ($filePath);
				return true;
			}
		}
		return false;		
	}
});?>