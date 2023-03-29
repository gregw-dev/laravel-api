<?php

namespace App\Support\Soundblock;

use File;
use Carbon\Carbon;
use App\Helpers\Csv;
use App\Repositories\Soundblock\Data\AllExchangeRates as AllExchangeRatesRepo;

class MusicAppleReports
{
    public function defineReportType(string $strFileName): ?string
    {
        $arrTypes = [
            "S1" => "Apple Music",
            "S3" => "Apple Music Radio",
            "F1" => "Apple Fitness",
            "S5" => "DJ Mix",
            ""   => "iTunes Store",
        ];

        $regex = "/.+?(?=_)/";
        preg_match($regex, $strFileName, $res);

        try {
            if (array_key_exists($res[0], $arrTypes)) {
                $strType = $arrTypes[$res[0]];
            } elseif (substr(str_replace("." . pathinfo($strFileName, PATHINFO_EXTENSION), "", $strFileName), -2) == "ZZ") {
                $strType = "iTunes Store";
            } else {
                throw new \Exception("Undefined report type.");
            }
        } catch (\Exception $exception) {
            throw new \Exception("Undefined report type.");
        }

        return ($strType);
    }

    /**
     * @param string $strLocalFile
     * @param string $strPlatformName
     * @param string $strReportType
     * @return array
     * @throws \Exception
     */
    public function readAppleFile(string $strLocalFile, string $strReportType): array
    {
        $objAllExchangeRatesRepo = resolve(AllExchangeRatesRepo::class);
        $objLinesCollection = File::lines($strLocalFile);
        $strDelimiter = Csv::detectDelimiter($strLocalFile, $objLinesCollection->first());
        $arrFileData = $objLinesCollection->filter()->map(fn($strLine) => str_getcsv($strLine, $strDelimiter))->toArray();

        [$strDateStarts, $strDateEnds] = $this->getReportDates($arrFileData, $strReportType);
        $arrAvgRates = $objAllExchangeRatesRepo->findAvgByDates($strDateStarts, $strDateEnds);
        $arrAggregatedReportData = $this->getAppleAggregatedData($arrFileData, $strReportType, $arrAvgRates);

        return ([$strDateStarts, $strDateEnds, $arrAggregatedReportData]);
    }

    public function getReportDates($arrFileData, $strReportType){
        $arrFirstRowDates = ["Apple Music", "Apple Music Radio", "Apple Fitness", "DJ Mix"];

        if (in_array($strReportType, $arrFirstRowDates)) {
            $strDateStarts = Carbon::createFromFormat("m/d/Y", $arrFileData[0][1])->toDateString();
            $strDateEnds = Carbon::createFromFormat("m/d/Y", $arrFileData[1][1])->toDateString();
        } else {
            $strDateStarts = Carbon::createFromFormat("m/d/Y", $arrFileData[1][0])->toDateString();
            $strDateEnds = Carbon::createFromFormat("m/d/Y", $arrFileData[1][1])->toDateString();
        }

        return ([$strDateStarts, $strDateEnds]);
    }

    /**
     * @param $arrFileData
     * @param $strReportType
     * @return array
     * @throws \Exception
     */
    private function getAppleAggregatedData($arrFileData, $strReportType, $arrAvgRates): array
    {
        [$intOffset, $arrHeaderMap] = $this->getHeaderMapping($strReportType);
        $arrFileDataHeader = $arrFileData[$intOffset];
        $arrAllData = [];

        for ($i = $intOffset+1; $i < count($arrFileData); $i++) {
            $lastRow = $strReportType == "iTunes Store" ? "Total_Rows" : "Row Count";

            if ($arrFileData[$i][0] == $lastRow) {
                break;
            }

            $arrFileDatum = array_combine($arrFileDataHeader, $arrFileData[$i]);
            $strIsrc = strtoupper(trim($arrFileDatum[$arrHeaderMap["isrc"]]));

            if (empty($strIsrc)){
                continue;
            }

            $strCurrency = strtoupper(trim($arrFileDatum[$arrHeaderMap["currency"]]));

            $intCurrentQuantity     = $arrAllData[$strIsrc][$strCurrency]["quantity"] ?? 0;
            $floatCurrentRevenue    = $arrAllData[$strIsrc][$strCurrency]["revenue"] ?? 0;

            $arrData = [];
            $arrData["track"]    = trim($arrFileDatum[$arrHeaderMap["track"]]);
            $arrData["quantity"] = intval($arrFileDatum[$arrHeaderMap["quantity"]]) + $intCurrentQuantity;
            $arrData["revenue"]  = floatval($arrFileDatum[$arrHeaderMap["revenue"]]) + $floatCurrentRevenue;
            $arrData["project"]  = isset($arrFileDatum[$arrHeaderMap["project"]]) ? trim($arrFileDatum[$arrHeaderMap["project"]]) : "";
            $arrData["artist"]   = trim($arrFileDatum[$arrHeaderMap["artist"]]);
            $arrData["upc"]      = isset($arrFileDatum[$arrHeaderMap["upc"]]) ? $arrFileDatum[$arrHeaderMap["upc"]] : "";

            if ($strCurrency != "USD") {
                if (!array_key_exists($strCurrency, $arrAvgRates)) {
                    throw new \Exception($strCurrency . " currency doesn't exists in db.");
                }

                $floatCurrentRevenueUsd = $arrAllData[$strIsrc][$strCurrency]["revenue_usd"] ?? 0;
                $floatCurrRate = $arrAvgRates[$strCurrency];
                $arrData["revenue_usd"] = ($arrFileDatum[$arrHeaderMap["revenue"]] / $floatCurrRate) + $floatCurrentRevenueUsd;
            }

            $arrAllData[$strIsrc][$strCurrency] = $arrData;
        }

        return ($arrAllData);
    }

    private function getHeaderMapping(string $strReportType)
    {
        /* Values: offset, "isrc", "track", "currency", "quantity", "revenue", "project", "artist", "upc" */
        $arrHeaderMapping = [
            "Apple Music"       => [3, "ISRC", "Item Title", "Currency", "Quantity", "Net Royalty Total", "", "Item Artist", ""],
            "Apple Music Radio" => [3, "ISRC", "Item Title", "Currency", "Quantity", "Net Royalty Total", "", "Item Artist", ""],
            "Apple Fitness"     => [3, "ISRC", "Item Title", "Currency", "Quantity", "Net Royalty Total", "", "Item Artist", ""],
            "DJ Mix"            => [3, "ISRC", "Item Title", "Currency", "Quantity", "Net Royalty Total", "", "Item Artist", ""],
            "iTunes Store"      => [0, "ISRC/ISBN", "Title", "Partner Share Currency", "Quantity", "Extended Partner Share", "", "Artist/Show/Developer/Author", "UPC"],
        ];

        $arrPlatformHeader = $arrHeaderMapping[$strReportType];
        $intDataOffset = $arrPlatformHeader[0];
        unset($arrPlatformHeader[0]);

        return ([
            $intDataOffset,
            array_combine(["isrc", "track", "currency", "quantity", "revenue", "project", "artist", "upc"], $arrPlatformHeader)
        ]);
    }
}
