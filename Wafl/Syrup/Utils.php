<?php
namespace Wafl\Syrup;

class Utils {	

	/**
	 * Convenience (helper) method for creating an instance of the parser and executing it.
	 * 
	 * @param string $inputString
	 * @param string $encoding
	 * 
	 * @return \Wafl\Syrup\ParseResult
	 */
	public static function ParseString($inputString, $encoding = null) {
		$parser = new Parser($inputString, $encoding);
		$parseResult = $parser->Parse();
		return $parseResult;
	}

	/**
	 * Convenience (helper) method for creating an instance of the parser and executing it
	 * 
	 * @since 0.1.633
	 * @param string $filename
	 * @param string $encoding
	 * @return \Wafl\Syrup\ParseResult
	 */
	public static function ParseFile($filename, $encoding = null) {
		if (!file_exists($filename))
		{
			throw new \Exception("File $filename does not exist");
		}
		return self::ParseString(file_get_contents($filename),$encoding);
	}
	
	public static function ParseFileAsArray($filename)
	{
		$result = self::ParseFile($filename);
		return $result->Get_ResultArray();
	}
}
?>