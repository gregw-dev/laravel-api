<?php

namespace App\Jobs\Soundblock\Reports\Apple;

use Exception;
use File;
use Util;
use Storage;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Filesystem\FilesystemAdapter;
use App\Services\Core\Slack as SlackService;
use App\Jobs\Soundblock\Reports\UsersMailing;
use App\Support\Soundblock\MusicAppleReports;
use App\Services\Soundblock\Reports as ReportsService;
use App\Models\Soundblock\Reports\Music as ProcessedMusicFiles;
use App\Repositories\Soundblock\Platform as PlatformRepository;
use App\Repositories\Soundblock\ReportRevenueMusic as ReportProjectRepository;
use App\Repositories\Soundblock\Data\PlatformReport as PlatformReportRepository;
use App\Models\Soundblock\Data\PlatformReportMetadata as PlatformReportMetadataModel;
use App\Repositories\Soundblock\Data\PlatformReportMetadata as PlatformReportMetadataRepository;

class ProcessAppleReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private FilesystemAdapter $fileSystemAdapter;
    private string $strFile;
    private SlackService $slackService;
    private ReportsService $reportService;
    private MusicAppleReports $musicAppleReportsSupport;
    private ReportProjectRepository $reportProjectRepo;
    private PlatformRepository $platformRepo;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600;
    private string $strFileName;
    /** @var PlatformReportMetadataRepository */
    private PlatformReportMetadataRepository $platformReportMetadataRepo;
    /** @var PlatformReportMetadataModel|null */
    private $objReportMeta;

    /**
     * Create a new job instance.
     *
     * @param string $strFile
     * @param string $strFileName
     * @param PlatformReportMetadataModel $objReportMeta
     */
    public function __construct(string $strFile, string $strFileName, PlatformReportMetadataModel $objReportMeta)
    {
        $this->strFile = $strFile;
        $this->strFileName = $strFileName;
        $this->objReportMeta = $objReportMeta;
    }

    /**
     * Execute the job.
     *
     * @param SlackService $objSlackService
     * @param ReportsService $objReports
     * @param MusicAppleReports $objMusicAppleReports
     * @param ReportProjectRepository $reportProjectRepo
     * @param PlatformRepository $platformRepo
     * @param PlatformReportMetadataRepository $platformReportMetadataRepo
     * @return void
     * @throws \App\Exceptions\Core\Disaster\SlackException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle(SlackService $objSlackService, ReportsService $objReports, MusicAppleReports $objMusicAppleReports,
                           ReportProjectRepository $reportProjectRepo, PlatformRepository $platformRepo,
                           PlatformReportMetadataRepository $platformReportMetadataRepo)
    {
        $this->reportService              = $objReports;
        $this->slackService               = $objSlackService;
        $this->musicAppleReportsSupport   = $objMusicAppleReports;
        $this->reportProjectRepo          = $reportProjectRepo;
        $this->platformRepo               = $platformRepo;
        $this->platformReportMetadataRepo = $platformReportMetadataRepo;

        $strExtension              = Util::file_extension($this->strFile);
        $this->fileSystemAdapter   = bucket_storage("soundblock");

        if(!in_array($strExtension,["txt","csv","tsv","gz"])){
            $strMessage = "Not processable Apple Music report file: {$this->strFile}. File extension is not included.";
            logger()->warning($strMessage);
            $this->slackService->reportPlatformNotification($strMessage, "notify-urgent", "ProcessFile");

            return;
        }

        $strFileContent = $this->fileSystemAdapter->get($this->strFile);

        if ($strExtension == "gz"){
            $strExtension = Util::file_extension(substr($this->strFile,0,-3));
            $strFileContent = gzdecode($strFileContent);
        }

        $strLocalFilePath = storage_path("app/reports/".md5($this->strFile).".".$strExtension);

        if (!ProcessedMusicFiles::where("file_path", $this->strFileName)->exists()){
            File::put($strLocalFilePath, $strFileContent);
            $this->storeFromFilePath($strLocalFilePath, "Apple Music");
            File::delete($strLocalFilePath);
        } else {
            $this->objReportMeta->update(["status" => "Failed"]);
        }
    }

    /**
     * @param string $strLocalFilePath
     * @param string $strPlatform
     * @return bool
     * @throws \Exception
     */
    public function storeFromFilePath(string $strLocalFilePath, string $strPlatform): bool{
        $strReportType = $this->musicAppleReportsSupport->defineReportType($this->strFileName);
        $objPlatform = $this->platformRepo->findByName($strPlatform);
        [$dateStarts, $dateEnds, $insertData] = $this->musicAppleReportsSupport->readAppleFile($strLocalFilePath, $strReportType);
        [$arrReportSlackData, $arrReportsMeta] = $this->reportProjectRepo->store($insertData, $objPlatform, $dateStarts, $dateEnds, $strReportType);

        $this->reportService->storeMusicUserPayments($dateStarts, $dateEnds);
        $this->reportService->storeMusicAccountPayments($dateStarts, $dateEnds);
        $this->reportService->storeMusicProjectPayments($dateStarts, $dateEnds);

        dispatch(new UsersMailing())->delay(now()->addMinutes(15));

        $this->updateDatabase(
            $dateStarts,
            $dateEnds,
            $strPlatform,
            $arrReportsMeta
        );
        $this->slackReport(
            $arrReportSlackData,
            $strPlatform,
            [
                "Month" => Carbon::parse($dateStarts)->format("F"),
                "Year" => Carbon::parse($dateStarts)->format("Y")
            ]
        );

        return (true);
    }

    private function updateDatabase(string $dateStarts, string $dateEnds, string $strPlatform, array $arrReportsMeta)
    {
        $objPlatformRepository = resolve(PlatformRepository::class);
        $objPlatformReportRepository = resolve(PlatformReportRepository::class);
        $strFile = $this->strFileName;
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

        $strMonth = Carbon::parse($dateEnds)->format("m");
        $strYear = Carbon::parse($dateEnds)->format("Y");
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

        $this->objReportMeta->update([
            "date_starts" => $dateStarts,
            "date_ends" => $dateEnds,
            "report_quantity_matched" => $arrReportsMeta["matched"]["count"],
            "report_revenue_usd_matched" => $arrReportsMeta["matched"]["revenue_usd"],
            "report_quantity_unmatched" => $arrReportsMeta["unmatched"]["count"],
            "report_revenue_usd_unmatched" => $arrReportsMeta["unmatched"]["revenue_usd"],
            "status" => "Completed"
        ]);

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
        $this->objReportMeta->update(["status" => "Failed"]);
        $objSlackService = resolve(\App\Contracts\Core\Slack::class);
        $objSlackService->reportPlatformNotification(
            $exception->getMessage(),
            config("slack.channels.exceptions"),
            "ProcessAppleReport",
            $this->strFile
        );
    }
}
