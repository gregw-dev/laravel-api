<?php

namespace App\Jobs\Soundblock\Reports;

use App\Contracts\Core\Slack as SlackService;
use Storage;
use App\Models\Soundblock\Reports\Music as ProcessedMusicFiles;
use App\Support\Soundblock\MusicReports;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FindUnprocessedFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private string $diskName;
    private string $directory;
    private string $fileName;

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
        $this->fileName = "";
    }


    /**
     * Execute the job.
     *
     * @param MusicReports $objMusicReports
     * @return void
     */
    public function handle(MusicReports $objMusicReports)
    {
        $objFileSystemAdapter = Storage::disk($this->diskName);

        foreach ($objFileSystemAdapter->files($this->directory) as $strFile) {
            $this->fileName = $strFile;
            $strPlatform = $objMusicReports->definePlatformFromFileName($strFile);

            if ($strPlatform){
                $intLastModified = $objFileSystemAdapter->lastModified($strFile);

                if (!$this->isAlreadyProcessed($strFile, $intLastModified)){
                    dispatch(new ProcessFile($this->diskName, $strFile));
                }
            }
        }
    }

    private function isAlreadyProcessed($strFile,$intLastModified): bool
    {
        $objLastModified = Carbon::createFromTimestamp($intLastModified);
        $objFileAlreadyProcessed = ProcessedMusicFiles::where("file_path",$strFile)
            ->where("stamp_last_modified",">=", $objLastModified)
            ->first();

        return (bool) $objFileAlreadyProcessed;
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
            "FindUnprocessedFiles",
            $this->fileName
        );
    }
}
