<?php
class Test
{
    private $inputBuffer = "";
    private $output = []; //we'll have headings and values whee values are strings or arrays of headings and values
    private $indentChar = "";
    private $currentRow = 0;
    private $currentCol = 0;
    
    function p($string)
    {
        foreach (str_split($string) as $char)
        {
            $this->setIndent($char);
            $this->inputBuffer.=$char;
        }
    }

    function processBuffer($nextChar)
    {
        $this->inputBuffer = "";
    }
    function setIndent($char)
    {
        //if the indent hasnt been set yet, we're still in the first column,
        //and the char is not an indent chare (tab or space)
        //and all the chars in the buffer are spaces (at least 2 consecutive) or tabs,
        //then we're at the fist indent.
        if ($this->indentChar=="")
        {
            if (($char != " ") && ($char != "\t"))
            {
                if
                ((strlen($this->inputBuffer)>1 && $this->isAllSpaces($this->inputBuffer))
                  || $this->inputBuffer == "\t")
                {
                    $this->processBuffer($char);
                    $this->indentChar = $this->inputBuffer;
                    $this->currentCol++;
                }
            }
        } 
        elseif (($this->inputBuffer) == $this->indentChar)
        {
            $this->processBuffer($char);
            $this->currentCol++;
        }
        elseif ($this->inputBuffer == "\n")
        {
            $this->processBuffer($char);
            $this->currentCol=0;
            $this->currentRow++;
        }
    }
    function isAllSpaces($str)
    {
        return strlen($str) > 0 && trim($str,"\x20") == "";
    }
    function isBufferFullOfChar($char)
    {
        if (strlen($this->inputBuffer)>0)
        {
            $fullOfChar=true;
            foreach (str_split($this->inputBuffer) as $testChar)
            {
                if ($testChar != $char)
                {
                    $fullOfChar = false;
                    break;
                }
            }
        } else {
            $fullOfChar = false;
        }
        return $fullOfChar;
    }
}
?>