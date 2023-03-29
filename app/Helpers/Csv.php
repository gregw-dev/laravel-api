<?php


namespace App\Helpers;


class Csv
{
    /**
     * Detect CSV Separator
     */
    public static function detectDelimiter($strFileName, $strSampleText)
    {
        $strFileExtension= substr($strFileName, strrpos($strFileName, '.')+1);
        switch ($strFileExtension){
            case "csv":
                $strSeparator=",";
                break;
            case "tsv":
                $strSeparator="\t";
                break;
            default:
                $arrDelimiters=[
                    ","     => 0,
                    "\t"    => 0,
                    ";"     => 0,
                    "|"     => 0
                ];
                foreach ($arrDelimiters as $strDelimiter => &$intCount) {
                    $intCount = count(str_getcsv($strSampleText, $strDelimiter));
                }
                $strSeparator=array_search(max($arrDelimiters), $arrDelimiters);
                break;
        }
        return $strSeparator;
    }

}
