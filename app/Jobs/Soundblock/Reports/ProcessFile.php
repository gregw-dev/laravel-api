<?php

namespace App\Jobs\Soundblock\Reports;

use File;
use Util;
use Storage;
use App\Models\Soundblock\Reports\Music as ProcessedMusicFiles;
use App\Services\Soundblock\Reports as ReportsService;
use App\Support\Soundblock\MusicReports;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Core\Slack as SlackService;
use App\Repositories\Soundblock\Platform as PlatformRepository;
use App\Repositories\Soundblock\Data\PlatformReport as PlatformReportRepository;

class ProcessFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private FilesystemAdapter $fileSystemAdapter;
    private string $strFile;
    private string $diskName;
    private SlackService $slackService;
    private ReportsService $reportService;
    private MusicReports $musicReportsSupport;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600;

    /**
     * Create a new job instance.
     *
     * @param string $strDiskName
     * @param string $strFile
     */
    public function __construct(string $strDiskName, string $strFile)
    {
        $this->diskName = $strDiskName;
        $this->strFile = $strFile;
    }

    /**
     * Execute the job.
     *
     * @param SlackService $objSlackService
     * @param ReportsService $objReports
     * @param MusicReports $objMusicReports
     * @return void
     * @throws \Exception
     */
    public function handle(SlackService $objSlackService, ReportsService $objReports, MusicReports $objMusicReports)
    {
        $this->reportService       = $objReports;
        $this->slackService        = $objSlackService;
        $this->musicReportsSupport = $objMusicReports;

        $strFileExtension          = Util::file_extension($this->strFile);
        $this->fileSystemAdapter   = Storage::disk($this->diskName);

        if ($strFileExtension == "zip"){
            $this->extractZip();
        }else{
            $this->handleFile($strFileExtension);
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

        $matches = preg_split('/\//', $this->strFile);

        foreach ($arrFiles as $strFile) {
            $strFullFileName = $matches[0] . "/" . $matches[1] . "/" . $strFile->getFilename();
            if (!ProcessedMusicFiles::where("file_path", $strFullFileName)->exists()){
                $this->handleFileContent($strFile);
            }
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
        if (!ProcessedMusicFiles::where("file_path", $this->strFile)->exists()){
            File::put($strLocalFilePath, $strFileContent);
            $this->handleFileContent($strLocalFilePath);
            File::delete($strLocalFilePath);
        }
    }

    /**
     * @param string $strFilePath
     * @return bool
     * @throws \App\Exceptions\Core\Disaster\SlackException
     */
    private function handleFileContent(string $strFilePath): bool
    {
        $strExtension = Util::file_extension($strFilePath);

        if(!in_array($strExtension,["txt","csv","tsv"])){
            $strMessage = "Not processable {$this->strFile}. File extension is not included.";
            logger()->warning($strMessage);
            $this->slackService->reportPlatformNotification($strMessage, "notify-urgent", "ProcessFile");

            return false;
        }

        $strPlatform = $this->musicReportsSupport->definePlatformFromFileName($this->strFile);
        [$boolIsStored, $arrReportSlackData, $dateStarts] = $this->reportService->storeFromFilePath($strFilePath, $strPlatform, $this->strFile);

        if ($boolIsStored){
            $this->updateDatabase(
                $dateStarts,
                $strPlatform
            );
            $this->slackReport(
                $arrReportSlackData,
                $strPlatform,
                [
                    "Month" => Carbon::parse($dateStarts)->format("F"),
                    "Year" => Carbon::parse($dateStarts)->format("Y")
                ]
            );
        }

        return (true);
    }

    private function updateDatabase(string $strDateStarts, string $strPlatform)
    {
        $objPlatformRepository = resolve(PlatformRepository::class);
        $objPlatformReportRepository = resolve(PlatformReportRepository::class);
        $strFile = $this->strFile;
        $objLastModified = Carbon::createFromTimestamp($this->fileSystemAdapter->lastModified($this->strFile));
        $objFileProcessed = ProcessedMusicFiles::firstWhere("file_path", $strFile);

        if ($objFileProcessed) {
            $objFileProcessed->update(["stamp_last_modified" => $objLastModified]);
        } else {
            ProcessedMusicFiles::insert([
                "row_uuid"            => Util::uuid(),
                "file_path"           => $strFile,
                "stamp_last_modified" => $objLastModified,
                "stamp_created"       => time(),
                "stamp_created_at"    => now(),
                "stamp_created_by"    => 1,
            ]);
        }

        $strMonth = Carbon::parse($strDateStarts)->format("m");
        $strYear = Carbon::parse($strDateStarts)->format("Y");
        $objPlatform = $objPlatformRepository->findByName($strPlatform);
        $objPlatformReport = $objPlatformReportRepository->findByPlatformAndDates($objPlatform->platform_uuid, $strYear, $strMonth);

        if (empty($objPlatformReport)) {
            $objPlatformReportRepository->create([
                "platform_id" => $objPlatform->platform_id,
                "platform_uuid" => $objPlatform->platform_uuid,
                "report_month" => $strMonth,
                "report_year" => $strYear
            ]);
        }

        return true;
    }

    private function slackReport(array $arrRevenueData, string $strPlatform, array $arrDate){
        $objSlackService = resolve(\App\Contracts\Core\Slack::class);
        $arrProcessedFile = [
            "Report Processed" => $strPlatform,
            "File" => $this->strFile,
            "Period" => $arrDate["Month"] . " " . $arrDate["Year"],
            "Soundblock Projects" => count($arrRevenueData),
            "Total Royalties" => array_sum($arrRevenueData)
        ];
        $objSlackService->reportProcessedFileNotification($arrProcessedFile, config("slack.channels.reports"));
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        $objSlackService = resolve(\App\Contracts\Core\Slack::class);
        $objSlackService->reportPlatformNotification(
            $exception->getMessage(),
            config("slack.channels.exceptions"),
            "ProcessFile",
            $this->strFile
        );
    }
}
