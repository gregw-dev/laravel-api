<?php

namespace App\Console\Commands\Soundblock;

use DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Util;
use File;
use Storage;
use Carbon\Carbon;
use App\Helpers\Csv;
use Illuminate\Console\Command;
use App\Support\Soundblock\MusicReports;
use App\Services\Core\Slack as SlackService;
use App\Services\Soundblock\Reports as ReportsService;
use App\Models\Soundblock\Reports\RevenueMusicUnmatched;

class UpdateReportsUnmatchedTable extends Command implements ShouldQueue
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:reports_unmatched';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    /**
     * @var mixed
     */
    private $strFile;
    /**
     * @var MusicReports
     */
    private MusicReports $musicReportsSupport;
    /**
     * @var ReportsService
     */
    private ReportsService $reportService;
    private \Illuminate\Filesystem\FilesystemAdapter $fileSystemAdapter;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the job.
     *
     * @param ReportsService $objReports
     * @param MusicReports $objMusicReports
     * @return void
     * @throws \Exception
     */
    public function handle(ReportsService $objReports, MusicReports $objMusicReports)
    {
        $this->reportService       = $objReports;
        $this->musicReportsSupport = $objMusicReports;
        $this->fileSystemAdapter   = Storage::disk("reports-ftp");

        $arrMonths = $this->fileSystemAdapter->directories("");

        foreach ($arrMonths as $strMonth) {
            if(!is_numeric(substr($strMonth,0,6))){
                continue;
            }
            foreach ($this->fileSystemAdapter->directories($strMonth) as $strDirectory) {
                if ($objMusicReports->isDirectoryProcessable($strDirectory)){
                    foreach ($this->fileSystemAdapter->files($strDirectory) as $strFile) {
                        if (RevenueMusicUnmatched::withTrashed()->whereNull("project_upc")->count() == 0) {
                            return;
                        }
                        $strPlatform = $objMusicReports->definePlatformFromFileName($strFile);

                        if ($strPlatform){
//                            dump($strFile);
                            $strFileExtension = Util::file_extension($strFile);
                            $this->strFile = $strFile;

                            if ($strFileExtension == "zip"){
                                $this->extractZip();
                            }else{
                                $this->handleFile($strFileExtension);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function extractZip()
    {
        $strFileNameHash  = md5($this->strFile);
        $strLocalFilePath = storage_path("app/reports/{$strFileNameHash}.zip");
        $strDirectoryPath = storage_path("app/reports/{$strFileNameHash}");

        File::put($strLocalFilePath, $this->fileSystemAdapter->get($this->strFile));

        $zip = new \ZipArchive();
        $isOpened = $zip->open($strLocalFilePath,\ZipArchive::RDONLY);

        if ($isOpened) {
            $zip->extractTo($strDirectoryPath);
        } else {
            throw new \Exception("Unable to open archive {$strLocalFilePath}");
        }

        $zip->close();

        File::delete($strLocalFilePath);
        $arrFiles = File::files($strDirectoryPath);

        foreach ($arrFiles as $strFile) {
            $this->handleFileContent($strFile);
            File::delete($strFile);
        }

        File::deleteDirectory($strDirectoryPath);
    }

    /**
     * @param string $strExtension
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function handleFile(string $strExtension)
    {
        $strFileContent = $this->fileSystemAdapter->get($this->strFile);

        if ($strExtension == "gz"){
            $strExtension = Util::file_extension(substr($this->strFile,0,-3));
            $strFileContent = gzdecode($strFileContent);
        }

        $strLocalFilePath = storage_path("app/reports/".md5($this->strFile).".".$strExtension);
        File::put($strLocalFilePath, $strFileContent);
        $this->handleFileContent($strLocalFilePath);
        File::delete($strLocalFilePath);
    }

    /**
     * @param string $strFilePath
     * @return bool
     * @throws \App\Exceptions\Core\Disaster\SlackException
     */
    private function handleFileContent(string $strFilePath): bool
    {
        $strExtension = Util::file_extension($strFilePath);

        if(in_array($strExtension,["txt","csv","tsv"])){
            $strPlatform = $this->musicReportsSupport->definePlatformFromFileName($this->strFile);
            $this->readFile($strFilePath, $strPlatform, $this->strFile);
        }

        return (true);
    }

    /**
     * @param string $strLocalFile
     * @param string $strPlatformName
     * @param string $strFilePath
     * @return bool
     * @throws \Exception
     */
    private function readFile(string $strLocalFile, string $strPlatformName, string $strFilePath): bool
    {
        $objLinesCollection = File::lines($strLocalFile);
        $strDelimiter = Csv::detectDelimiter($strLocalFile, $objLinesCollection->first());
        $arrFileData = $objLinesCollection->filter()->map(fn($strLine) => str_getcsv($strLine, $strDelimiter))->toArray();

        preg_match("/.+?(?=\/)/", $strFilePath, $arrMatches);
        $strFileDate = $arrMatches[0];

        $arrUpcCodes = $this->getAggregatedData($arrFileData, $strPlatformName, $strFileDate);

        foreach ($arrUpcCodes as $strUpcCode => $arrIsrcCodes) {
            if (!empty($strUpcCode)) {
                $arrIsrcCodes = array_filter($arrIsrcCodes);
                foreach ($arrIsrcCodes as $key => $strIsrcCode) {
                    $arrIsrcCodes[$key] = $this->formatIsrcCode($strIsrcCode);
                }

                RevenueMusicUnmatched::withTrashed()->whereNull("project_upc")->whereIn("track_isrc", $arrIsrcCodes)->update([
                    "project_upc" => $strUpcCode
                ]);
            }
        }

        return (true);
    }

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

//            $arrAllData[strval(substr($arrFileDatum[$arrHeaderMap["upc"]], -12))][] = $strIsrc;
            $arrAllData[strval($arrFileDatum[$arrHeaderMap["upc"]])][] = $strIsrc;
        }

        return ($arrAllData);
    }

    private function getHeaderMapping(string $strPlatform, string $strFileDate)
    {
        /* Values: offset, "isrc", "track", "currency", "quantity", "revenue", "project", "artist", "upc" */
        $arrHeaderMapping =[
            "Spotify"                        => [2,"ISRC","Track name","Payable currency","Quantity","Payable","Album name","Artist name", "UPC"],
            "Dubset"                         => [0,"Track ISRC","Track title","Payment currency","Quantity","Payment owed to label owner in payment currency","Album title","Artist name", "Album UPC"],
            //            "Google Play"                    => [2,"ISRC","Product_Title","Partner_Revenue_Currency","Total_Plays","Partner_Revenue_Paid","Container_Title","Artist"],
            "iHeartRadio"                    => [0,"ISRC","Track Name","Curr","# Streams","Price","Album Name","Artist Name", "UPC"],
            "Pandora"                        => [2,"ISRC","ReleaseTitle","CurrencyCode","NumberOfConsumerSalesGross","EffectiveRoyaltyRate","ResourceTitle","Contributors", "UPC"],
            "Soundcloud"                     => [0,"ISRC","Track Name","Revenue Currency","Total Plays","Total Amount","Album Title","Artist Name", "UPC"],
            "Uma Music (Australia)"          => [0,"ISRC","Track_title","","quantity","Total in USD","release_title","Artist", "release_id"],
            "Slacker Radio"                  => [2,"ISRC","Track Name","Currency","Number of Transactions","Net Royalty Total","Album Name","Artist Name", "EAN UPC"],
            "Anghami (United Arab Emirates)" => [0,"ISRC","Track Title","Currency","Quantity","Total Payable","Release Title","Artist Name", "Release ID"],
            "Boomplay (Africa)"              => [0,"ISRC","Track_Title","","Quantity","Total_Payable_USD","Release_Title","Artist_Name", "Release_ID"],
            "Deezer (France)"                => [0,"ISRC","Title","","Nb of plays","Royalties","Album","Artist", "UPC"],
            "TikTok (China)"                 => [0,"isrc","song_title","statement_currency","video_views","statement_amount","album","artist", "product_code"],
            "Triller"                        => [0,"ISRC","Track_Name","","Quantity_Views","USD_Payable","Release_Title","Artist_Name", "Release_ID"],
            //            "Your Band Website" => [0,"ISRC","Track_Title","Statement_Currency","Number_Of_Transactions","Statement_Amount","Album_Title","Track_Artist"],
            //            "VEVO" => [0,"isrc","song_title","currency","quantity","net_revenue","sub_label","artist_name"],
            //            "Akazoo (UK)" => [0,"ISRC","Title","Currency","TotalStreams","Total","Catalog Number","Artist"],
            "Resso (China)"                  => [0,"isrc","track_title","","number_of_streams","total_payable_usd","release_title","artist_name", ""], // UPC
            "Jaxsta Music (Australia)"       => [0,"ISRC","Track_Title","","Quantity_Views","Total_Payable_USD","Release_Title","Release_Artist_Name", "Release_Id"],
            "Joox (China)"                   => [0,"ISRC","Track_Title","","Quantity","Total Amount","Release_Title","Artist_Name", "Release_ID"],
            //            "Peloton" => [0,"isrc","track_title","","number_of_streams","total_payable_usd","release_title","artist_name"],
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

    private function formatIsrcCode($strIsrc): string
    {
        return implode("-", [
            substr($strIsrc, 0, 2),
            substr($strIsrc, 2, 3),
            substr($strIsrc, 5, 2),
            substr($strIsrc, 7),
        ]);
    }
}
