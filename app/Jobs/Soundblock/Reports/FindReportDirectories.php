<?php

namespace App\Jobs\Soundblock\Reports;

use App\Models\Soundblock\Reports\Music as ProcessedMusicFiles;
use Storage;
use App\Support\Soundblock\MusicReports;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Contracts\Core\Slack as SlackService;

class FindReportDirectories implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private string $diskName;
    private string $directory;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * Create a new job instance.
     *
     * @param string $strDiskName
     * @param string $strDirectory
     */
    public function __construct(string $strDiskName, string $strDirectory = "")
    {
        $this->directory = $strDirectory;
        $this->diskName = $strDiskName;
    }

    /**
     * Execute the job.
     *
     * @param MusicReports $objMusicReportsHelper
     * @return void
     */
    public function handle(MusicReports $objMusicReportsHelper)
    {
        $objFileSystemAdapter = Storage::disk($this->diskName);
        $arrMonths = array_reverse($objFileSystemAdapter->directories($this->directory));

        foreach ($arrMonths as $strMonth) {
            if(!is_numeric(substr($strMonth,0,6))){
                continue;
            }
            foreach ($objFileSystemAdapter->directories($strMonth) as $strDirectory) {
                if ($objMusicReportsHelper->isDirectoryProcessable($strDirectory)){
                    foreach ($objFileSystemAdapter->files($strDirectory) as $strFile) {
//                        $intLastModified = $objFileSystemAdapter->lastModified($strFile);

                        if (!$this->isAlreadyProcessed($strFile) && !empty($objMusicReportsHelper->definePlatformFromFileName($strFile))){
                            dispatch(new ProcessFile($this->diskName, $strFile));
                        }
                    }
                }
            }
        }
    }

    private function isAlreadyProcessed($strFile): bool
    {
//        $objLastModified = Carbon::createFromTimestamp($intLastModified);
//        $objFileAlreadyProcessed = ProcessedMusicFiles::where("file_path",$strFile)
//            ->where("stamp_last_modified",">=", $objLastModified)
//            ->first();
//
//        return (bool) $objFileAlreadyProcessed;

        return (bool) ProcessedMusicFiles::where("file_path",$strFile)->first();
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        $objSlackService = resolve(SlackService::class);
        $objSlackService->reportPlatformNotification(
            $exception->getMessage(),
            config("slack.channels.exceptions"),
            "FindReportDirectories"
        );
    }
}
