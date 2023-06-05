<?php

namespace Wafl\Syrup;

class ParseResult
{
  private $_resultArray;
  private $_endingCursorPosition;
  private $_endingCursorLine;
  private $_endingCursorDepth;
  private $_endingHeadingDepth;
  private $_elementString;

  public function __construct($resultArray, $endingCursorPosition, $endingCursorLine, $endingCursorDepth, $endingHeadingDepth, $elementString)
  {
    $this->_resultArray = $resultArray;
    $this->_endingCursorDepth = $endingCursorDepth;
    $this->_endingCursorLine = $endingCursorLine;
    $this->_endingCursorPosition = $endingCursorPosition;
    $this->_endingHeadingDepth = $endingHeadingDepth;
    $this->_elementString = $elementString;
  }
  public function Get_ResultArray()
  {
    return $this->_resultArray;
  }
  public function Get_EndingCursorDepth()
  {
    return $this->_endingCursorDepth;
  }
  public function Get_EndingCursorLine()
  {
    return $this->_endingCursorLine;
  }
  public function Get_EndingCursorPosition()
  {
    return $this->_endingCursorPosition;
  }
  public function Get_EndingHeadingDepth()
  {
    return $this->_endingHeadingDepth;
  }
  public function Get_ElementString()
  {
    return $this->_elementString;
  }
}
