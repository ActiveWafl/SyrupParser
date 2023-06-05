<?php

use Wafl\Syrup\Utils;

require_once(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "Autoloader.php");

$parseText = isset($_REQUEST["ParseText"]) ? $_REQUEST["ParseText"] : null;
$cursorPosition = isset($_REQUEST["CursorPosition"]) ? $_REQUEST["CursorPosition"] : null;
if ($parseText) {
  try {
    $parser = new \Wafl\Syrup\Parser($parseText);
    $parseResult = $parser->Parse();

    if ($cursorPosition < strlen($parseText)) {
      $textBeforeCursor = substr($parseText, 0, $cursorPosition);
      $parseResultUptoCursor = Utils::ParseString($textBeforeCursor);
    } else {
      $parseResultUptoCursor = $parseResult;
    }
    $resultArray = $parseResult->Get_ResultArray();
    $resultString = print_r($resultArray, true);
    require_once("phar://" . __DIR__ . "/GeSHi.phar/geshi.php");
    $geshi = new GeSHi($resultString, "php", null);
    $geshi->set_header_type(\GESHI_HEADER_NONE);
    $resultString = '<code class="php">' . $geshi->parse_code() . '</code>';
    if ($geshi->error()) {
      throw new \Exception("Error while highlighting code: " . $geshi->error(), E_WARNING, $ex);
    }

    $geshi = new GeSHi(json_encode($resultArray, JSON_PRETTY_PRINT), "javascript", null);
    $geshi->set_header_type(\GESHI_HEADER_NONE);
    $resultStringJson = '<code class="javascript">' . $geshi->parse_code() . '</code>';
    if ($geshi->error()) {
      throw new \Exception("Error while highlighting code: " . $geshi->error(), E_WARNING, $ex);
    }


    //this is an "ajax" call, return the parsed text
    $returnData = array(
      "ParsedResult" => $resultString,
      "ParsedResultJSON" => $resultStringJson,
      "CursorDepth" => $parseResultUptoCursor->Get_EndingCursorDepth(),
      "CursorLine" => $parseResultUptoCursor->Get_EndingCursorLine(),
      "CursorPosition" => $parseResultUptoCursor->Get_EndingCursorPosition(),
      "HeadingDepth" => $parseResultUptoCursor->Get_EndingHeadingDepth(),
      "ParserDepth" => $parseResult->Get_EndingCursorDepth(),
      "ParserLine" => $parseResult->Get_EndingCursorLine(),
      "ParserPosition" => $parseResult->Get_EndingCursorPosition(),
      "ParserHeadingDepth" => $parseResult->Get_EndingHeadingDepth(),
      "ParserCurrentElement" => $parser ? $parseResult->Get_ElementString() : "N/A",
      "CursorCurrentElement" => $parser ? $parseResultUptoCursor->Get_ElementString() : "N/A"

    );

    print json_encode($returnData);
  } catch (Exception $ex) {
    print json_encode(
      array(
        "Message" => $ex->getMessage(),
        "Line" => $ex->getLine(),
        "File" => $ex->getFile(),
        "ParserDepth" => $parser ? $parser->Get_Depth() : 0,
        "ParserLine" => $parser ? $parser->Get_LineNumber() : 0,
        "ParserPosition" => $parser ? $parser->Get_CharPos() : 0,
        "ParserHeadingDepth" => $parser ? $parser->Get_HeadingDepth() : 0,
        "CurrentElement" => $parser ? $parser->Get_CurrentElementString() : 0
      )
    );
  }
} else {
  //show the home page
  require_once(__DIR__ . DIRECTORY_SEPARATOR . "Demo.html");
}
