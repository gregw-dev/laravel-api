<?php

namespace App\Support\Soundblock;

use App\Helpers\Csv;
use Carbon\Carbon;
use File;

class MusicReports
{
    private array $platformCurrencies = [
        "Akazoo" => "€",
        "Anghami" => "$",
        "AT&T" => "$",
        "Boomplay" => "$",
        "Cricket" => "$",
        "Deezer" => "€",
        "Dubset" => "$",
        "Google Play" => "$ €",
        "Guvera" => "$",
        "iHeart" => "$",
        "Jaxsta" => "$",
        "JB Hi-Fi" => "USD AUD",
        "JOOX" => "$",
        "Pandora" => "$",
        "Resso" => "$",
        "ROXI" => "$",
        "Simfy Africa" => "€ $",
        "Slacker" => "$",
        "Sony" => "$",
        "SoundCloud" => "$",
        "Soundtrack Your Brand" => "$",
        "Spotify" => "$ € £",
        "TikTok" => "$",
        "Triller" => "$",
        "UMA" => "$",
        "Vevo" => "$",
    ];

    private array $platformFolderCores = [
        "spo-spotify"    => "Spotify",
        "dst-dubset"     => "Dubset",
        "iht-iheart"     => "iHeartRadio",
        "pnd-pandora"    => "Pandora",
        "scu-soundcloud" => "Soundcloud",
        "uma-uma"        => "Uma Music (Australia)",
        "slk-slacker"    => "Slacker Radio",
        "ang-anghami"    => "Anghami (United Arab Emirates)",
        "boo-boomplay"   => "Boomplay (Africa)",
        "dzr-deezer"     => "Deezer (France)",
        "tiktok"         => "TikTok (China)",
//        "vvo-vevo"  => "VEVO",
//        "akz-akazoo"  => "Akazoo (UK)",
        "res-resso"      => "Resso (China)",
//        "" => "Peloton",
        "fbk-facebook" => "Facebook/Instagram",
        "jax-jaxsta" => "Jaxsta Music (Australia)",
        "joo-joox" => "Joox (China)",

        /* Platforms Under 7 Digital */
        "trl-triller"                => "Triller",
        "stb-soundtrack-your-brand"  => "Your Band Website",
        "snp-snap"                   => "Snapchat",
//        "" => "Roxi"
//        "" => "Trebel",
    ];

    private array $filePattensPlatform = [
        "#.*/spo-spotify/.*-track-for-(streaming|breakage).*#" => "Spotify",
        "#.*/dst-dubset/Merlin_Dubset_.*_detailed_report_.*#"  => "Dubset",
//        "#.*/ggl-google/GOOGLE_.*_SUBSCRIPTION_.*#"            => "Google Play",
        "#.*/iht-iheart/.*#"                                   => "iHeartRadio",
        "#.*/pnd-pandora/.*#"                                  => "Pandora",
        "#.*/scu-soundcloud/.*#"                               => "Soundcloud",
        "#.*/uma-uma/.*#"                                      => "Uma Music (Australia)",
        "#.*/slk-slacker/.*#"                                  => "Slacker Radio",
        "#.*/ang-anghami/.*#"                                  => "Anghami (United Arab Emirates)",
        "#.*/boo-boomplay/.*#"                                 => "Boomplay (Africa)",
        "#.*/dzr-deezer/.*#"                                   => "Deezer (France)",
        "#.*/tiktok/.*#"                                       => "TikTok (China)",
//        "#.*/vvo-vevo/.*#" => "VEVO",
//        "#.*/akz-akazoo/.*#" => "Akazoo (UK)",
        "#.*/res-resoo/.*#"                                    => "Resso (China)",
//        "#.*_Peloton_.*#" => "Peloton",
        "#.*/jax-jaxsta/.*#"                                   => "Jaxsta Music (Australia)",
        "#.*/joo-joox/.*#"                                     => "Joox (China)",
        "#.*/fbk-facebook/.*#"                                 => "Facebook/Instagram",

        /* Under 7 Digital */
        "#.*/trl-triller/.*#"                                  => "Triller",
        "#.*/stb-soundtrack-your-brand/.*#"                    => "Your Band Website",
        "#.*/snp-snap/.*#"                                     => "Snapchat",
        //        "" => "Roxi"
        //        "" => "Trebel",
    ];

    public function isPlatformTurnedOn(string $strPlatformName){
        return (bool) array_search($strPlatformName, $this->platformFolderCores);
    }

    public function isDirectoryProcessable(string $strDirectory): bool
    {
        $arrDirectory= explode("/", $strDirectory) ?? [$strDirectory];
        return (bool) array_intersect($arrDirectory,array_keys($this->platformFolderCores));
    }

    public function definePlatformFromFileName(string $strFileName): ?string
    {
        foreach ($this->filePattensPlatform as $strFilePattern => $strPlatform) {
            if (preg_match($strFilePattern, $strFileName)){
                return (string) $strPlatform;
            }
        }

        return false;
    }

    /**
     * @param string $strLocalFile
     * @param string $strPlatformName
     * @param string $strFilePath
     * @return array
     * @throws \Exception
     */
    public function readFile(string $strLocalFile, string $strPlatformName, string $strFilePath): array
    {
        $objLinesCollection = File::lines($strLocalFile);
        $strDelimiter = Csv::detectDelimiter($strLocalFile, $objLinesCollection->first());
        $arrFileData = $objLinesCollection->filter()->map(fn($strLine) => str_getcsv($strLine, $strDelimiter))->toArray();

        preg_match("/.+?(?=\/)/", $strFilePath, $arrMatches);
        $strFileDate = $arrMatches[0];

        [$strDateStarts, $strDateEnds] = $this->getDates($arrFileData, $strPlatformName, $strFileDate);
        $arrAggregatedReportData = $this->getAggregatedData($arrFileData, $strPlatformName, $strFileDate);

        return ([$strDateStarts, $strDateEnds, $arrAggregatedReportData]);
    }

    /**
     * @param $arrFileData
     * @param $platformName
     * @param $strFileDate
     * @return array
     * @throws \Exception
     */
    private function getAggregatedData($arrFileData, $platformName, $strFileDate): array
    {
        [$intOffset, $arrHeaderMap] = $this->getHeaderMapping($platformName, $strFileDate);
        $arrFileDataHeader = $arrFileData[$intOffset];
        $arrAllData = [];

        for ($i = $intOffset+1; $i < count($arrFileData); $i++) {
            if ($platformName == "Pandora" && count($arrFileDataHeader) == 24 && count($arrFileData[$i]) == 25) {
                array_pop($arrFileData[$i]);
            }

            $arrFileDatum = array_combine($arrFileDataHeader, $arrFileData[$i]);
            $strIsrc = strtoupper(trim($arrFileDatum[$arrHeaderMap["isrc"]]));

            if (empty($strIsrc)){
                continue;
            }

            $strCurrency = $arrHeaderMap["currency"] ? strtoupper(trim($arrFileDatum[$arrHeaderMap["currency"]])) : "USD";

            /* Akazoo in eur as well */
            if ($platformName == "Deezer (France)") {
                $strCurrency = "EUR";
            }

            if (!in_array(strtolower($strCurrency), ["aud", "gbp", "eur", "usd"])) {
                throw (new \Exception($platformName . " platform has wrong currency for " . $strFileDate . " period.", 400));
            }

            $intCurrentQuantity  = $arrAllData[$strIsrc][$strCurrency]["quantity"] ?? 0;
            $floatCurrentRevenue = $arrAllData[$strIsrc][$strCurrency]["revenue"] ?? 0;

            if (!array_key_exists($arrHeaderMap["track"], $arrFileDatum)) {
                if ($platformName == "Pandora") {
                    $arrHeaderMap["track"] = "Release_Title";
                }
            }

            $arrData = [];
            $arrData["track"]    = trim($arrFileDatum[$arrHeaderMap["track"]]);
            $arrData["quantity"] = intval($arrFileDatum[$arrHeaderMap["quantity"]])+$intCurrentQuantity;
            $arrData["revenue"]  = floatval($arrFileDatum[$arrHeaderMap["revenue"]])+$floatCurrentRevenue;
            $arrData["project"]  = isset($arrFileDatum[$arrHeaderMap["project"]]) ? trim($arrFileDatum[$arrHeaderMap["project"]]) : "";
            $arrData["artist"]   = trim($arrFileDatum[$arrHeaderMap["artist"]]);
//            $arrData["upc"]      = substr($arrFileDatum[$arrHeaderMap["upc"]], -12);
            $arrData["upc"]      = isset($arrFileDatum[$arrHeaderMap["upc"]]) ? $arrFileDatum[$arrHeaderMap["upc"]] : "";

            $arrAllData[$strIsrc][$strCurrency] = $arrData;
        }

        return ($arrAllData);
    }

    /**
     * @param $arrFileData
     * @param $strPlatform
     * @param $strFileDate
     * @return array
     */
    private function getDates($arrFileData, $strPlatform, $strFileDate): array
    {
        $arrDataSecondKey = $arrFileData[1];

        if (count($arrFileData[0]) != count($arrFileData[1])) {
            $arrDataSecondKey = array_filter($arrDataSecondKey, fn($value) => trim($value) !== "");
        }

        $arrData = array_combine($arrFileData[0], $arrDataSecondKey);
        $arrDataKeys = $this->getDateHeaderMapping($strPlatform, $strFileDate);

        $strFormat = $arrDataKeys["format"];
        $strStartDate = $arrData[$arrDataKeys["start"]];

        if ($arrDataKeys["end"]) {
            $strEndDate = $arrData[$arrDataKeys["end"]];

            return ([
                Carbon::createFromFormat($strFormat, $strStartDate)->toDateString(),
                Carbon::createFromFormat($strFormat, $strEndDate)->toDateString()
            ]);
        } else {
            return ([
                Carbon::createFromFormat($strFormat, $strStartDate)->firstOfMonth()->toDateString(),
                Carbon::createFromFormat($strFormat, $strStartDate)->lastOfMonth()->toDateString()
            ]);
        }
    }

    private function getHeaderMapping(string $strPlatform, string $strFileDate)
    {
        /* Values: offset, "isrc", "track", "currency", "quantity", "revenue", "project", "artist", "upc" */
        $arrHeaderMapping =[
            "Spotify"                        => [2,"ISRC","Track name","Payable currency","Quantity","Payable","Album name","Artist name", "UPC"],
            "Dubset"                         => [0,"Track ISRC","Track title","Payment currency","Quantity","Payment owed to label owner in payment currency","Album title","Artist name", "Album UPC"],
            "Google Play"                    => [2,"ISRC","Product_Title","Partner_Revenue_Currency","Total_Plays","Partner_Revenue_Paid","Container_Title","Artist"],
            "iHeartRadio"                    => [0,"ISRC","Track Name","Curr","# Streams","Price","Album Name","Artist Name", "UPC"],
            "Pandora"                        => [2,"ISRC","ReleaseTitle","CurrencyCode","NumberOfConsumerSalesGross","EffectiveRoyaltyRate","ResourceTitle","Contributors", "UPC"],
            "Soundcloud"                     => [0,"ISRC","Track Name","Revenue Currency","Total Plays","Total Amount","Album Title","Artist Name", "UPC"],
            "Uma Music (Australia)"          => [0,"ISRC","Track_title","","quantity","Total in USD","release_title","Artist", "release_id"],
            "Slacker Radio"                  => [2,"ISRC","Track Name","Currency","Number of Transactions","Net Royalty Total","Album Name","Artist Name", "EAN UPC"],
            "Anghami (United Arab Emirates)" => [0,"ISRC","Track Title","Currency","Quantity","Total Payable","Release Title","Artist Name", "Release ID"],
            "Boomplay (Africa)"              => [0,"ISRC","Track_Title","","Quantity","Total_Payable_USD","Release_Title","Artist_Name", "Release_ID"],
            "Deezer (France)"                => [0,"ISRC","Title","","Nb of plays","Royalties","Album","Artist", "UPC"],
            "TikTok (China)"                 => [0,"isrc","song_title","statement_currency","video_views","statement_amount","album","artist", "product_code"],
//            "VEVO" => [0,"isrc","song_title","currency","quantity","net_revenue","sub_label","artist_name"],
//            "Akazoo (UK)" => [0,"ISRC","Title","Currency","TotalStreams","Total","Catalog Number","Artist"],
            "Resso (China)"                  => [0,"isrc","track_title","","number_of_streams","total_payable_usd","release_title","artist_name", "upc"],
            "Jaxsta Music (Australia)"       => [0,"ISRC","Track_Title","","Quantity_Views","Total_Payable_USD","Release_Title","Release_Artist_Name", "Release_Id"],
            "Joox (China)"                   => [0,"ISRC","Track_Title","","Quantity","Total Amount","Release_Title","Artist_Name", "Release_ID"],
            //            "Peloton" => [0,"isrc","track_title","","number_of_streams","total_payable_usd","release_title","artist_name"],
            "Facebook/Instagram"             => [0, "elected_isrc", "track_title", "", "event_count_including_estimates", "usd_payable", "", "track_artist", ""],

            /* Under 7 Digital */
            "Triller"           => [0,"ISRC","Track_Name","","Quantity_Views","USD_Payable","Release_Title","Artist_Name", "Release_ID"],
            "Your Band Website" => [0,"ISRC","Track_Title","Statement_Currency","Number_Of_Transactions","Statement_Amount","Album_Title","Track_Artist", "UPC"],
            "Snapchat"          => [0,"ISRC","Track_Title","","Quantity_Views","Total_Payable_USD","Release_Title","Artist_Name", "Release_Id"],
            //        "Roxi" => ""
            //        "Trebel" => "",
        ];

        if ($strFileDate < 202001) {
            $arrHeaderMapping["Spotify"] = [2,"ISRC","Track name","","Quantity","USD Payable","Album name","Artist name", "UPC"];
        }

        if ($strFileDate < 202102) {
            $arrHeaderMapping["Soundcloud"] = [0,"ISRC","Track Name","Revenue Currency","Total Plays","Total Revenue","Album Title","Artist Name", "UPC"];
        }

        $arrPlatformHeader = $arrHeaderMapping[$strPlatform];
        $intDataOffset = $arrPlatformHeader[0];
        unset($arrPlatformHeader[0]);

        return ([
            $intDataOffset,
            array_combine(["isrc", "track", "currency", "quantity", "revenue", "project", "artist", "upc"], $arrPlatformHeader)
        ]);
    }

    private function getDateHeaderMapping(string $strPlatform, string $strFileDate): array
    {
        /* Values: Date Format, Start Date, End Date */
        $arrHeaderMapping =[
            "Spotify"                        => ["Y-m-d", "Start date", "End date"],
            "Dubset"                         => ["Ymd", "Reporting start date", "Reporting end date"],
//            "Google Play"                    => ["Y-m-d", "Start_Date", "End_Date"],
            "iHeartRadio"                    => ["m/d/Y", "ReportStartDt", "ReportEndDt"],
            "Pandora"                        => ["Y-m-d", "MessageNotificationStartDate", "MessageNotificationEndDate"],
            "Soundcloud"                     => ["m-Y", "Reporting Period", ""],
            "Uma Music (Australia)"          => ["d/m/Y", "start date", "end date"],
            "Slacker Radio"                  => ["Ym", "Accounted Period", ""],
            "Anghami (United Arab Emirates)" => ["d/m/Y", "Start Date", "End Date"],
            "Boomplay (Africa)"              => ["Ymd", "Start_Date", "End_Date"],
            "Deezer (France)"                => ["d-m-Y", "Start Report", "End Report"],
            "TikTok (China)"                 => ["Ymd","report_start_date","report_end_date"],
            //            "VEVO" => ["Ymd","start_date","end_date"],
            //            "Akazoo (UK)" => ["Ymd","start_date","end_date"],
            "Resso (China)"                  => ["Ymd", "reporting_period_start", "reporting_period_end"],
            "Jaxsta Music (Australia)"       => ["Ymd", "Start_Date", "End_Date"],
            "Joox (China)"                   => ["Ymd", "Start_Date", "End_Date"],
            //            "Peloton" => ["Ymd","Start_Date","End_Date"],
            "Facebook/Instagram"             => ["Y-m-d", "start_date", "end_date"],

            /* Under 7 Digital */
            "Triller"           => ["Y-m-d","Start_Date","End_Date"],
            "Your Band Website" => ["Y-m-d","Start_Date","End_Date"],
            "Snapchat"          => ["Y-m-d","Start_Date","End_Date"],
            //        "Roxi" => ""
            //        "Trebel" => "",
        ];

        if (intval($strFileDate) < 202104) {
            $arrHeaderMapping["Slacker Radio"] = ["Y-m-d", "Accounted Period", ""];
        }

        return (array_combine(["format", "start", "end"], $arrHeaderMapping[$strPlatform]));
    }
}
