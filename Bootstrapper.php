<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
ini_set("html_errors", 1);

spl_autoload_register(function($classname){
	$filePath = __DIR__.DIRECTORY_SEPARATOR.str_replace("\\", DIRECTORY_SEPARATOR, $classname).".php";
	if (file_exists($filePath))
	{
		require_once ($filePath);
	}
});?>