<?php

namespace Wafl\Syrup;

/**
 * Syrup file parsing class
 */
class Parser {
	/**
	 * The string to be parsed
	 * @var string
	 */
	private $_inputString;

	/**
	 * The parser's current depth in the hierarchy
	 * @var integer
	 */
	private $_currentDepth = 0;

	/**
	 * The depth of the header that the parser is currently inside of (or 0 if at the top)
	 * @var integer
	 */
	private $_currentHeaderDepth = 0;
	private $_settingDepth		 = 0;
	private $_currentHeader		 = null;
	private $_lastLevelParents	 = array();
	private $_lineNumber		 = 1;
	private $_charPos;
	private $_currentRowCellCt	 = 0;
	private $_tabBuffer			 = array();

	/**
	 * a character buffer user for buffering consecutive value chars;
	 * @var string
	 */
	private $_charBuffer = "";

	/**
	 * Whether or not we are inside of a backslash
	 * @todo combine all these flags into one var or something
	 * @var boolean
	 */
	private $_inBackSlash	 = false;
	private $_inForwardSlash = false;
	private $_inComment		 = false;
	private $_inQuote		 = false;
	private $_inSpace		 = false;
	private $_inTab			 = false;
	private $_encoding;
	private $_currentElementString;

	/**
	 * The parsed Syrup array
	 * @var array 
	 */
	private $_resultBuffer = null;

	/**
	 * Create an instance of the Syrup Parser
	 * 
	 * @setup file_get_contents('../../../Resources/SyrpTest1Input.syrp'),null
	 * @param string $inputSyrp
	 * @param string $encoding
	 */
	public function __construct($inputSyrp, $encoding = null) {
		if (!$this->_encoding) {
			$this->_encoding = mb_detect_encoding($this->_inputString);
			if ($this->_encoding != "ASCII") {
				/*
				 * @todo finish multibyte support
				 */
				throw new \Exception("If you're parsing non-ascii files, you  must pass in the encoding argument");
			}
		}

		$this->_inputString	 = $inputSyrp;
		$this->_encoding	 = $encoding;
	}

	/**
	 * Parse the input string passed during construction.
	 * 
	 * @assert () == \DblEj\Tests\ExpectedResults\Syrp::GetExpectedTest1Output()
	 * 
	 * @return \Wafl\Syrup\ParseResult
	 */
	public function Parse() {

		if ($this->_resultBuffer === null) { //don't reparse if already parsed
			$this->_resultBuffer = array();

			//get all characters of the input string
			//@todo finish multibyte support
			$chars = preg_split('//u', $this->_inputString, -1, PREG_SPLIT_NO_EMPTY);

			//iterate over each character and process it
			foreach ($chars as $char) {
				$this->_processCharacter($char);
			}


			$charPos			 = $this->_charPos;
			$lineNumber			 = $this->_lineNumber;
			$currentDepth		 = $this->_currentDepth;
			$currentHeaderDepth	 = $this->_currentHeaderDepth;

			//handle any unprocessed data in the buffer, since the file will rarely end with a \n
			$this->_handleEol();

			$this->_resultBuffer = $this->_removeUnnecessaryNesting($this->_resultBuffer); //post-processing.  Convert empty Headers into Values,and also deal with key=val assignments
		}
		return new ParseResult($this->_resultBuffer, $charPos, $lineNumber, $currentDepth, $currentHeaderDepth,
						 $this->_currentElementString);
	}

	/**
	 * Removes nesting of Value Lists when it isn't needed.
	 * 
	 * 1) If a Heading holds only an empty list, then treat the Heading as a Value
	 * 2) If a Value List has only one Value, and that Value is also a Value List, then promote the lower Value List's values up one to replace to first Value List's values.  This way you dont have a situation like this Setting = array(array(one,two,three)).  Instead you get a cleaner Setting = array(one,two,three)
	 * 
	 * @param array $array
	 * @param boolean $recursive
	 * @return array
	 */
	private function _removeUnnecessaryNesting($array, $recursive = true, $useKey = null) {
//		if (count($array) == 1 && (is_array(reset($array))) && key($array) === 0) { //we test the key for zero to ensure this is a setting and not a headding.  But what if someone uses 0 as a heading?  Maybe we should reserve it
//			$array = $this->_removeUnnecessaryNesting(reset($array));
//		}

		$resultArray = array();
		foreach ($array as $elemKey => &$element) {
			$cleanKey = trim($useKey ? $useKey : preg_replace('/\{\$\{.*\}\$\}/', "", $elemKey));
			if (is_array($element)) {
				if (count($element) == 0) {
					if ($useKey) {
						$resultArray[$useKey] = $cleanKey;
					}
					else {
						$resultArray[] = $cleanKey;
					}
				}
				elseif (count($element) == 1) {
					$first		 = reset($element);
					$firstKey	 = key($element);
					$firstKey	 = preg_replace('/\{\$\{.*\}\$\}/', "", $firstKey);
					if (is_array($first) && count($first) == 0) {
						$resultArray[$cleanKey] = $firstKey;
					}
					elseif (is_array($first)) {
						$resultArray[$cleanKey] = $this->_removeUnnecessaryNesting($element, true, $firstKey);
					}
					else {
						$resultArray[$cleanKey] = $first;
					}
				}
				else {
					if (isset($element[1]) && ($element[1] == "=")) {
						$elementValue = null;
						if (count($element) > 2) {
							$elementValue = $element[2];
						}
						$resultArray[trim($element[0])] = $elementValue;
					}
					elseif ($recursive) {
						$resultArray[$cleanKey] = $this->_removeUnnecessaryNesting($element);
					}
					else {
						$resultArray[$cleanKey] = &$element;
					}
				}
			}
			else {
				$resultArray[$cleanKey] = $element;
			}
		}

		return $resultArray;
	}

	private function _processCharacter($char) {

		$this->_charPos++;

		if (!$this->_inComment || $char == "\n") { //ignore comments until eol
			if ($this->_inQuote) {
				if ($char == "\"") {
					$this->_inQuote = false;
				}
				else {
					$this->_charBuffer .= $char;
				}
			}
			else if ($this->_inBackSlash) {
				$this->_charBuffer .= $char;
				$this->_inBackSlash = false;
			}
			else {
				$evalChar = $char;
				if ($this->_inForwardSlash) {
					if ($evalChar != "/") {
						$this->_inForwardSlash = false;
						$this->_charBuffer.="/";
					}
				}

				if ($evalChar != " " && $this->_inSpace) { //if they ended up using the space without doubling it then add the literal space
					if (!$this->_inTab) //dont add a space if there were several in a row as they are all considered a tab after the first one (and the first one is nullified)
					{
						$this->_charBuffer .= " ";
					}
					$this->_inSpace = false;
				}
				if ($evalChar != " " && $evalChar != "\t" && $this->_inTab)
				{
					$this->_inTab = false;
				}
				switch ($evalChar) {
					case " ":
						if ($this->_inSpace) {
							//$this->_inSpace = false;
							$this->_processTab();
						}
						else {
							$this->_inSpace = true;
						}
						$this->_currentElementString = "";
						break;
					case "\t":
						$this->_processTab();
						$this->_currentElementString = "";
						break;
					case "\r": //ignore
						break;
					case "\n":
						$this->_handleEol();
						$this->_currentElementString = "";
						break;
					case "\"";
						$this->_inQuote				 = !$this->_inQuote;
						break;
					case "/":
						if ($this->_inForwardSlash) {
							$this->_inForwardSlash	 = false;
							$this->_inComment		 = true;
						}
						else {
							$this->_inForwardSlash = true;
						}
						break;
					case "\\":
						$this->_inBackSlash = true;
						break;
					//			case "=":
					//				break;
					case "[":  //ignore
					case "]":
						break;
					default:
						$this->_charBuffer .= $evalChar;
						$this->_currentElementString .= $evalChar;
						break;
				}
			}
		}
	}

	private function _processTab() {
		$this->_inTab = true;
		if (!$this->_charBuffer && ($this->_currentRowCellCt == 0)) {
			$this->_currentDepth++;
		}
		if ($this->_charBuffer) {
			$this->_currentRowCellCt++;

			if (isset($this->_currentHeader) && $this->_currentHeader !== null && ($this->_currentDepth > $this->_currentHeaderDepth)) {
				$this->_startValueList();
				if ($this->_charBuffer) {
					$this->_currentHeader[] = $this->_charBuffer;
				}
			}
			else {
				$this->_tabBuffer[] = $this->_charBuffer;
			}
			$this->_charBuffer = "";
		}
	}

	private function _startValueList() {
		if ($this->_currentRowCellCt == 1) { //first value in a list (they put in a value and hit tab so...)
			$newIdx												 = count($this->_currentHeader);
			$this->_currentHeader[$newIdx]						 = array();
			$this->_currentHeader								 = &$this->_currentHeader[$newIdx];
			$this->_currentHeaderDepth++;
			$this->_currentDepth++; //@todo just wanna double check because we are already incrementing depth in prior method (tab)
			$this->_settingDepth								 = $this->_currentHeaderDepth;
			$this->_lastLevelParents[$this->_currentHeaderDepth] = &$this->_currentHeader;
		}
	}

	private function _handleEol() {
		$this->_inComment = false; //comments can't be more than one line

		if (($this->_charBuffer !== null && $this->_charBuffer !== "") || ($this->_tabBuffer && count($this->_tabBuffer))) {

			$this->_currentRowCellCt++;
			if (isset($this->_currentHeader) && $this->_currentHeader !== null) {
				$this->_handleEolWhenHeaderIsSet();
			}
			else {
				$this->_currentHeader								 = array();
				$this->_resultBuffer								 = array(
					$this->_charBuffer => &$this->_currentHeader);
				$this->_lastLevelParents[$this->_currentDepth]		 = &$this->_resultBuffer; //current depth should always be zero here?
				$this->_lastLevelParents[$this->_currentDepth + 1]	 = &$this->_currentHeader;
				$this->_currentHeaderDepth							 = $this->_currentDepth + 1;
				$this->_charBuffer									 = "";
			}

			$this->_throwExceptionIfTooDeep();
		}

		if ($this->_tabBuffer && count($this->_tabBuffer) > 0) {
			//need to handle buffer contents before leaving
		}

		$this->_tabBuffer		 = array();
		$this->_currentDepth	 = 1;
		$this->_currentRowCellCt = 0;
		$this->_charPos			 = 0;
		$this->_lineNumber++;
	}

	private function _throwExceptionIfTooDeep() {
		if ($this->_currentDepth > 0) {
			if ($this->_currentDepth - $this->_currentHeaderDepth > 1) {
				if (class_exists("\DblEj\Parsing\ParsingException")) {
					throw new \DblEj\Parsing\ParsingException($this->_lineNumber, $this->_charPos,
											   "Tabbed past all ancestors, depth $this->_currentDepth");
				}
				else {
					throw new \Exception("Invalid SyRuP at line: $this->_lineNumber, pos: $this->_charPos: Tabbed past all ancestors. " . $this->_currentElementString . "'s Depth is $this->_currentDepth.  Based on the current Heading Depth, the largest allowed Depth for this Element is " . ($this->_currentHeaderDepth + 1) . ".");
				}
			}
		}
	}

	private function _handleEolWhenHeaderIsSet() {
		if (($this->_currentRowCellCt == 1)) {
			if ($this->_settingDepth) {
				//headers cant go into value lists...
				$this->_currentHeader	 = &$this->_lastLevelParents[$this->_currentHeaderDepth - 1];
				$this->_currentHeaderDepth--;
				$this->_settingDepth	 = 0;
			}
		}
		if ($this->_currentDepth > $this->_currentHeaderDepth) {
			if ($this->_currentRowCellCt == 1) {

//			if (!isset($this->_lastLevelParents[$this->_currentDepth-1])) {
//				throw new \Exception("Invalid SyRuP at line: $this->_lineNumber, pos: $this->_charPos:  You cannot place a header within a content row (Cursor Depth: $this->_currentDepth, Last valid Heading Depth: $this->_currentHeaderDepth)");
//			}

				$uniqueId = "{\${" . uniqid() . "}\$}"; //values in lists need unique id appended prior to preprocessing otherwise they overwrite eachover

				$this->_currentHeader[$this->_charBuffer . $uniqueId]	 = array();
				$this->_currentHeader								 = &$this->_currentHeader[$this->_charBuffer . $uniqueId];
				$this->_lastLevelParents[$this->_currentDepth]		 = &$this->_currentHeader;
				$this->_currentHeaderDepth++;  //= $this->_currentDepth; @todo im pretty sure this is the same as the ++ so we can delete comment
				$this->_currentDepth++;
				$this->_settingDepth								 = 0;
			}
			else {
				$this->_currentHeader[] = $this->_charBuffer;
			}
			$this->_charBuffer = "";
		}
		else {
			$this->_currentHeader = &$this->_lastLevelParents[$this->_currentDepth - 1];
			if ($this->_charBuffer !== null && $this->_charBuffer !== "") {
				$this->_tabBuffer[]	 = $this->_charBuffer;
				$this->_charBuffer	 = "";
			}
			if (count($this->_tabBuffer) == 1) {
				$uniqueId												 = "{\${" . uniqid() . "}\$}"; //values in lists need unique id appended prior to preprocessing otherwise they overwrite eachover
				$this->_currentHeader[$this->_tabBuffer[0] . $uniqueId]	 = array();
				$this->_currentHeader									 = &$this->_currentHeader[$this->_tabBuffer[0] . $uniqueId];
				$this->_lastLevelParents[$this->_currentDepth]			 = &$this->_currentHeader;
				$this->_currentHeaderDepth								 = $this->_currentDepth;

				$this->_settingDepth = 0;
			}
			else {
				$this->_currentHeader[] = $this->_tabBuffer;
			}
		}
	}

// <editor-fold defaultstate="collapsed" desc=" Properties ">
	public function Get_CurrentHeader() {
		return $this->_currentHeader;
	}

	public function Get_CharPos() {
		return $this->_charPos;
	}

	public function Get_LineNumber() {
		return $this->_lineNumber;
	}

	public function Get_Depth() {
		return $this->_currentDepth;
	}

	public function Get_HeadingDepth() {
		return $this->_currentHeaderDepth;
	}

	public function Get_CurrentElementString() {
		return $this->_currentElementString;
	}

// </editor-fold>
}
?>